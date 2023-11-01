<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Service;

require __DIR__ . '/../../vendor/autoload.php';

use OCA\EcloudAccounts\AppInfo\Application;
use OCP\Defaults;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Util;
use Throwable;

use UnexpectedValueException;

class UserService {
	/** @var IUserManager */
	private $userManager;

	/** @var array */
	private $appConfig;

	/** @var IConfig */
	private $config;

	private $curl;
	private Defaults $defaults;
	private ILogger $logger;
	protected $l10nFactory;

	public function __construct($appName, IUserManager $userManager, IConfig $config, CurlService $curlService, ILogger $logger, Defaults $defaults, IFactory $l10nFactory) {
		$this->userManager = $userManager;
		$this->config = $config;
		$this->appConfig = $this->config->getSystemValue($appName);
		$this->curl = $curlService;
		$this->logger = $logger;
		$this->defaults = $defaults;
		$this->l10nFactory = $l10nFactory;
	}


	public function getConfigValue(string $key, mixed $default = false) {
		if (!empty($this->appConfig[$key])) {
			return $this->appConfig[$key];
		}
		return $default;
	}

	public function setAccountData(string $uid, string $email, string $recoveryEmail, string $hmeAlias, string $quota = '1024 MB', bool $tosAccepted = false, string $userLanguage = 'en') : array {
		
		if (!$this->userExists($uid)) {
			return ['error' => 'user_already_exists', 'status' => 404];
		}

		$user = $this->getUser($uid);

		if (is_null($user)) {
			return ['error' => 'user_already_exists', 'status' => 404];
		}

		$user->setEMailAddress($email);
		$user->setQuota($quota);
		if ($this->l10nFactory->languageExists(null, $userLanguage)) {
			$this->config->setUserValue($uid, 'core', 'lang', $userLanguage);
		}
		// $this->sendWelcomeEmail($uid, $email);
		$this->config->setUserValue($uid, 'terms_of_service', 'tosAccepted', intval($tosAccepted));
		$recoveryEmailUpdated = $this->setRecoveryEmail($uid, $recoveryEmail);
		if (!$recoveryEmailUpdated) {
			return ['error' => 'error_setting_recovery', 'status' => 400];
		}
		$hmeAliasAdded = $this->addHMEAliasInConfig($uid, $hmeAlias);
		if (!$hmeAliasAdded) {
			return ['error' => 'error_adding_hme_alias', 'status' => 400];
		}
		return ['status' => 200];
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
	public function sendWelcomeEmail(string $displayname = '', string $uid, string $toEmail, string $language = 'en') : void {
		$sendgridAPIkey = $this->getSendGridAPIKey();
		if (empty($sendgridAPIkey)) {
			$this->logger->warning("sendgrid_api_key is missing or empty.", ['app' => Application::APP_ID]);
			return;
		}
		
		$templateIDs = $this->getSendGridTemplateIDs();
		if (empty($templateIDs)) {
			$this->logger->warning("welcome_sendgrid_template_ids is missing or empty.", ['app' => Application::APP_ID]);
			return;
		}
		$this->setUserLanguage($uid, $language);
		
		$templateID = $templateIDs['en'];
		if (isset($templateIDs[$language])) {
			$templateID = $templateIDs[$language];
		}
		
		$fromEmail = Util::getDefaultEmailAddress('noreply');
		$fromName = $this->defaults->getName();
		
		$mainDomain = $this->getMainDomain();
		try {
			$email = $this->createSendGridEmail($fromEmail, $fromName, $toEmail, $displayname, $templateID, $uid, $mainDomain);
			$this->sendEmailWithSendGrid($email, $sendgridAPIkey);
		} catch (Throwable $e) {
			$this->logger->error('Error sending email to: ' . $toEmail . ': ' . $e->getMessage());
		}
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
	private function setUserLanguage(string $username, string $language) {
		$this->config->setUserValue($username, 'core', 'lang', $language);
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
	private function sendEmailWithSendGrid(\SendGrid\Mail\Mail $email, string $sendgridAPIkey): void {
		$sendgrid = new \SendGrid($sendgridAPIkey);
		$response = $sendgrid->send($email, [ CURLOPT_TIMEOUT => 15 ]);

		if ($response->statusCode() < 200 || $response->statusCode() > 299) {
			throw new \Exception("SendGrid API error - Status Code: " . $response->statusCode());
		}
	}
	public function createContactInSendGrid(string $email, string $displayName, bool $newsletter_eos, bool $newsletter_product):void {
		$sendgridAPIkey = $this->getSendGridAPIKey();
		if (empty($sendgridAPIkey)) {
			$this->logger->warning("sendgrid_api_key is missing or empty.", ['app' => Application::APP_ID]);
			return;
		}
		$sg = new \SendGrid($sendgridAPIkey);
		$requestBody = json_encode([
			'contacts' => [
				[
					'email' => $email,
					'first_name' => $displayName
				]
			]
		]);
		
		try {
			$response = $sg->client->marketing()->contacts()->put($requestBody);
			$this->logger->warning('statusCode:: '.json_encode($response->statusCode()), ['app' => Application::APP_ID]);
			$this->logger->warning('body: '. json_encode($response->body()), ['app' => Application::APP_ID]);
			$body = $response->body();
			$recipient_id = $body['persisted_recipients'][0];
			$this->addContactinSendGridList($recipient_id, $newsletter_eos, $newsletter_product);
		} catch (\Exception $ex) {
			$this->logger->warning("createContactInSendGrid caught exception: ".  $ex->getMessage(), ['app' => Application::APP_ID]);
		}
	}
	private function addContactinSendGridList(string $recipient_id, bool $newsletter_eos, bool $newsletter_product):void {
		$sendgridAPIkey = $this->getSendGridAPIKey();
		if (empty($sendgridAPIkey)) {
			$this->logger->warning("sendgrid_api_key is missing or empty.", ['app' => Application::APP_ID]);
			return;
		}
		$sg = new \SendGrid($sendgridAPIkey);
		if($newsletter_eos) {
			$list_id = 4900;
		}
		if($newsletter_product) {
			$list_id = 4900;
		}
		
		$recipient_id = "ZGkrHSypTsudrGkmdpJJ";

		try {
			$response = $sg->client->contactdb()->lists()->_($list_id)->recipients()->_($recipient_id)->post();
			$this->logger->warning('statusCode:: '.json_encode($response->statusCode()), ['app' => Application::APP_ID]);
			$this->logger->warning('body: '. json_encode($response->body()), ['app' => Application::APP_ID]);
		} catch (\Exception $ex) {
			$this->logger->warning("addContactinSendGridList caught exception: ".  $ex->getMessage(), ['app' => Application::APP_ID]);
		}
		return ;
	}
}
