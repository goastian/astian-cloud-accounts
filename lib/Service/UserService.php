<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Service;

require __DIR__ . '/../../vendor/autoload.php';

use OCP\IUserManager;
use OCP\IUser;
use OCP\IConfig;
use OCP\ILogger;
use OCA\EcloudAccounts\AppInfo\Application;
use OCP\Defaults;
use OCP\Util;

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

	public function __construct($appName, IUserManager $userManager, IConfig $config, CurlService $curlService, ILogger $logger, Defaults $defaults) {
		$this->userManager = $userManager;
		$this->config = $config;
		$this->appConfig = $this->config->getSystemValue($appName);
		$this->curl = $curlService;
		$this->logger = $logger;
		$this->defaults = $defaults;
	}


	public function getConfigValue(string $key, mixed $default = false) {
		if (!empty($this->appConfig[$key])) {
			return $this->appConfig[$key];
		}
		return $default;
	}


	public function userExists(string $uid): bool {
		$exists = $this->userManager->userExists($uid);
		if ($exists) {
			return $exists;
		}

		$backends = $this->userManager->getBackends();
		foreach ($backends as $backend) {
			if ($backend->getBackendName() === 'LDAP') {
				$access = $backend->getLDAPAccess($uid);
				$users = $access->fetchUsersByLoginName($uid) ;
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

	public function getHMEAliasesFromConfig($uid) : array {
		$aliases = $this->config->getUserValue($uid, 'hide-my-email', 'email-aliases', []);
		if (!empty($aliases)) {
			$aliases = json_decode($aliases, true);
		}
		return $aliases;
	}

	public function addHMEAliasInConfig($uid, $alias) : bool {
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
			$this->logger->logException($e, ['app' => Application::APP_ID]);
		}

		return null;
	}


	public function sendWelcomeEmail(string $uid, string $email) : bool {
		$this->logger->warning("sendWelcomeEmail called...", ['app' => 'ecloud-accounts']);
		$user = $this->userManager->get($uid);
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
			
		$language = $this->getUserLanguage($uid);
		$templateID = $templateIDs['en'];
		if (isset($templateIDs[$language])) {
			$templateID = $templateIDs[$language];
		}
		
		$fromEmail = Util::getDefaultEmailAddress('no-reply');
		$fromName = $this->defaults->getName();
			
		$toEmail = $email;
		$toName = $user->getDisplayName();
			
		$mainDomain = $this->getMainDomain();
		$this->logger->warning("templateID: ".$templateID, ['app' => Application::APP_ID]);
		$this->logger->warning("fromEmail: ".$fromEmail, ['app' => Application::APP_ID]);
		$this->logger->warning("fromName: ".$fromName, ['app' => Application::APP_ID]);
		$this->logger->warning("toEmail: ".$toEmail, ['app' => Application::APP_ID]);
		$this->logger->warning("toName: ".$toName, ['app' => Application::APP_ID]);
		$this->logger->warning("mainDomain: ".$mainDomain, ['app' => Application::APP_ID]);


		$email = $this->createSendGridEmail($fromEmail, $fromName, $toEmail, $toName, $templateID, $uid, $mainDomain);
		
		try {
			return $this->sendEmailWithSendGrid($email, $sendgridAPIkey);
		} catch (\Exception $e) {
			$this->logger->error($e, ['app' => Application::APP_ID]);
			return false;
		}


		return true;
	}

	private function getSendGridAPIKey() : string {
		return $this->config->getSystemValue('sendgrid_api_key', '');
	}

	private function getSendGridTemplateIDs() : array {
		return $this->config->getSystemValue('welcome_sendgrid_template_ids', '');
	}

	private function getMainDomain() : string {
		return $this->config->getSystemValue('main_domain', '');
	}

	private function getUserLanguage(string $username) : string {
		return $this->config->getUserValue($username, 'core', 'lang', 'en');
	}

	private function createSendGridEmail(string $fromEmail, string  $fromName, string $toEmail, string  $toName, string  $templateID, string  $username, string  $mainDomain) : \SendGrid\Mail\Mail {
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

	private function sendEmailWithSendGrid(\SendGrid\Mail\Mail $email, string  $sendgridAPIkey) : bool {
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
