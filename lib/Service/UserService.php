<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Service;

require_once __DIR__ . '/../../vendor/autoload.php';

use OCP\IUserManager;
use OCP\IUser;
use OCP\IConfig;
use Psr\Log\LoggerInterface;
use OCA\EcloudAccounts\AppInfo\Application;
use OCP\Util;
use OCP\Defaults;
use OCP\IUserSession;

use UnexpectedValueException;

class UserService {
	/** @var IUserManager */
	private $userManager;
	/** @var array */
	private $appConfig;
	/** @var IConfig */
	private $config;
	private $curl;
	private $defaults;
	private $userSession;
	private $logger;

	public function __construct($appName, IUserManager $userManager, IConfig $config, CurlService $curlService, LoggerInterface $logger, Defaults $defaults, IUserSession $userSession) {
		$this->userManager = $userManager;
		$this->config = $config;
		$this->appConfig = $this->config->getSystemValue($appName);
		$this->curl = $curlService;
		$this->logger = $logger;
		$this->defaults = $defaults;
		$this->userSession = $userSession;
	}


	public function getConfigValue(string $key, mixed $default = false) {
		if (!empty($this->appConfig[$key])) {
			return $this->appConfig[$key];
		}
		return $default;
	}


	public function userExists(string $uid): bool {
		$this->logger->warning("userExists called...", ['app' => Application::APP_ID]);
		$exists = $this->userManager->userExists($uid);
		if ($exists) {
			return $exists;
		}

		$backends = $this->userManager->getBackends();
		foreach ($backends as $backend) {
			if ($backend->getBackendName() === 'LDAP') {
				$access = $backend->getLDAPAccess($uid);
				$users = $access->fetchUsersByLoginName($uid);
				if (count($users) > 0) {
					$exists = true;
				}
			}
		}
		return $exists;
	}

	public function getUser(string $uid): ?IUser {
		return $this->userManager->get($uid);
	}

	public function setRecoveryEmail(string $uid, string $recoveryEmail): bool {
		try {
			$this->config->setUserValue($uid, 'email-recovery', 'recovery-email', $recoveryEmail);
			return true;
		} catch (UnexpectedValueException $e) {
			return false;
		}
	}

	public function getHMEAliasesFromConfig($uid): array {
		$aliases = $this->config->getUserValue($uid, 'hide-my-email', 'email-aliases', []);
		if (!empty($aliases)) {
			$aliases = json_decode($aliases, true);
		}
		return $aliases;
	}

	public function addHMEAliasInConfig($uid, $alias): bool {
		$aliases = $this->getHMEAliasesFromConfig($uid);
		$aliases[] = $alias;
		$aliases = json_encode($aliases);
		try {
			$this->config->setUserValue($uid, 'hide-my-email', 'email-aliases', $aliases);
			return true;
		} catch (UnexpectedValueException $e) {
			return false;
		}
	}

	/**
	 * Once NC deleted the account,
	 * perform specific ecloud selfhosting actions
	 * post delete action is delegated to the welcome container
	 *
	 * @param $userID string
	 * @param $welcomeDomain string main NC domain (welcome container)
	 * @param $welcomeSecret string generated at ecloud selfhosting install and added as a custom var in NC's config
	 * @return mixed response of the external endpoint
	 */
	public function ecloudDelete(string $userID, string $welcomeDomain, string $welcomeSecret, string $email, bool $isUserOnLDAP = false) {
		$endpoint = '/postDelete.php';
		if ($isUserOnLDAP) {
			$endpoint = '/postDeleteLDAP.php';
		}
		$postDeleteUrl = "https://" . $welcomeDomain . $endpoint;
		/**
		 * send action to docker_welcome
		 * Handling the non NC part of deletion process
		 */
		try {
			$params = [
				'sec' => $welcomeSecret,
				'uid' => $userID,
				'email' => $email
			];

			$answer = $this->curl->post($postDeleteUrl, $params);

			return json_decode($answer, true);
		} catch (\Exception $e) {
			$this->logger->error('There has been an issue while contacting the external deletion script');
			$this->logger->error($e, ['app' => Application::APP_ID]);
		}

		return null;
	}
	/**
	 * Sends a welcome email to the user upon registration or first login.
	 *
	 * @return bool True if the email was sent successfully, false otherwise.
	 *
	 * @throws \Exception If an error occurs during email sending.
	 */
	public function sendWelcomeEmail() : bool {
		$user = $this->userSession->getUser();
		
		$sendgridAPIkey = $this->getSendGridAPIKey();
		if (empty($sendgridAPIkey)) {
			$this->logger->warning("sendgrid_api_key is missing or empty.", ['app' => Application::APP_ID]);
			return false;
		}
		
		$templateIDs = $this->getSendGridTemplateIDs();
		if (empty($templateIDs)) {
			$this->logger->warning("welcome_sendgrid_template_ids is missing or empty.", ['app' => Application::APP_ID]);
			return false;
		}
			
		$username = $user->getUID();
		$language = $this->getUserLanguage($username);
		$templateID = $templateIDs['en'];
		if (isset($templateIDs[$language])) {
			$templateID = $templateIDs[$language];
		}
		
		$fromEmail = Util::getDefaultEmailAddress('noreply');
		$fromName = $this->defaults->getName();
			
		$toEmail = $user->getEMailAddress();
		$toName = $user->getDisplayName();
			
		$mainDomain = $this->getMainDomain();
		$email = $this->createSendGridEmail($fromEmail, $fromName, $toEmail, $toName, $templateID, $username, $mainDomain);
		
		try {
			return $this->sendEmailWithSendGrid($email, $sendgridAPIkey);
		} catch (\Exception $e) {
			$this->logger->error($e, ['app' => Application::APP_ID]);
			return false;
		}
	}
	/**
	 * Retrieves the SendGrid API key from configuration.
	 *
	 * @return string The SendGrid API key.
	 */
	private function getSendGridAPIKey() : string {
		return $this->config->getSystemValue('sendgrid_api_key', '');
	}
	/**
	 * Retrieves the SendGrid template IDs from configuration.
	 *
	 * @return array The SendGrid template IDs.
	 */
	private function getSendGridTemplateIDs() : array {
		return $this->config->getSystemValue('welcome_sendgrid_template_ids', '');
	}
	/**
	 * Retrieves the main domain from configuration.
	 *
	 * @return string The main domain.
	 */
	private function getMainDomain() : string {
		return $this->config->getSystemValue('main_domain', '');
	}
	/**
	 * Retrieves the user's language based on the username.
	 *
	 * @param string $username The user's username.
	 *
	 * @return string The user's language.
	 */
	private function getUserLanguage($username) : string {
		return $this->config->getUserValue($username, 'core', 'lang', 'en');
	}
	/**
	 * Creates a SendGrid email object.
	 *
	 * @param string $fromEmail    The sender's email address.
	 * @param string $fromName     The sender's name.
	 * @param string $toEmail      The recipient's email address.
	 * @param string $toName       The recipient's name.
	 * @param string $templateID   The SendGrid template ID.
	 * @param string $username     The username.
	 * @param string $mainDomain   The main domain.
	 *
	 * @return \SendGrid\Mail\Mail The SendGrid email object.
	 */
	private function createSendGridEmail($fromEmail, $fromName, $toEmail, $toName, $templateID, $username, $mainDomain) : \SendGrid\Mail\Mail {
		$email = new \SendGrid\Mail\Mail();
		$email->setFrom($fromEmail, $fromName);
		$email->addTo($toEmail, $toName);
		$email->setTemplateId($templateID);
		$email->addDynamicTemplateDatas([
			"username" => $username,
			"mail_domain" => $mainDomain,
			"display_name" => $toName
		]);
		return $email;
	}
	/**
	 * Sends an email using SendGrid.
	 *
	 * @param \SendGrid\Mail\Mail $email         The SendGrid email object.
	 * @param string              $sendgridAPIkey The SendGrid API key.
	 *
	 * @return bool Returns true on successful email sending, false otherwise.
	 */
	private function sendEmailWithSendGrid($email, $sendgridAPIkey) : bool {
		try {
			$sendgrid = new \SendGrid($sendgridAPIkey);
			$sendgrid->send($email);
			return true;
		} catch (\Exception $e) {
			$this->logger->warning("Error while sending sendEmailWithSendGrid", ['app' => Application::APP_ID]);
			$this->logger->error($e, ['app' => Application::APP_ID]);
			return false;
		}
	}
}
