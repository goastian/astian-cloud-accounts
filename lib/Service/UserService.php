<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Service;

require __DIR__ . '/../../vendor/autoload.php';

use Exception;
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
	/** @var CurlService */
	private $curl;
	/** @var Defaults */
	private $defaults;
	/** @var ILogger */
	private $logger;
	/** @var IFactory */
	protected $l10nFactory;
	/** @var array */
	private $apiConfig;
	/** @var LDAPConnectionService */
	private $LDAPConnectionService;

	public function __construct($appName, IUserManager $userManager, IConfig $config, CurlService $curlService, ILogger $logger, Defaults $defaults, IFactory $l10nFactory, LDAPConnectionService $LDAPConnectionService) {
		$this->userManager = $userManager;
		$this->config = $config;
		$this->appConfig = $this->config->getSystemValue($appName);
		$this->curl = $curlService;
		$this->logger = $logger;
		$this->defaults = $defaults;
		$this->l10nFactory = $l10nFactory;
		$this->LDAPConnectionService = $LDAPConnectionService;
		$this->apiConfig = [
			'mainDomain' => $this->config->getSystemValue('main_domain', ''),
			'commonApiUrl' => rtrim($this->config->getSystemValue('common_services_url', ''), '/') . '/',
			'commonServiceToken' => $this->config->getSystemValue('common_service_token', ''),
			'aliasDomain' => $this->config->getSystemValue('alias_domain', ''),
			'commonApiVersion' => $this->config->getSystemValue('common_api_version', ''),
			'userCluserId' => $this->config->getSystemValue('user_cluser_id', ''),
			'objectClass' => $this->config->getSystemValue('ldap_object_class', []),
		];
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
		if($this->userExists($uid)) {
			return $this->userManager->get($uid);
		}
	}

	public function setRecoveryEmail(string $uid, string $recoveryEmail): void {
		$this->config->setUserValue($uid, 'email-recovery', 'recovery-email', $recoveryEmail);
	}
	public function setTOS(string $uid, bool $tosAccepted): void {
		$this->config->setUserValue($uid, 'terms_of_service', 'tosAccepted', intval($tosAccepted));
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
	public function sendWelcomeEmail(string $displayname, string $username, string $userEmail, string $language = 'en') : void {
		
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
		
		$templateID = $templateIDs['en'];
		if (isset($templateIDs[$language])) {
			$templateID = $templateIDs[$language];
		}
		
		$fromEmail = Util::getDefaultEmailAddress('noreply');
		$fromName = $this->defaults->getName();
		
		try {
			$email = $this->createSendGridEmail($fromEmail, $fromName, $username, $displayname, $userEmail, $templateID);
			$this->sendEmailWithSendGrid($email, $sendgridAPIkey);
		} catch (Throwable $e) {
			$this->logger->error('Error sending welcome email to user: ' . $username . ': ' . $e->getMessage());
		}
	}
	private function getSendGridAPIKey() : string {
		return $this->config->getSystemValue('sendgrid_api_key', '');
	}
	private function getSendGridTemplateIDs() : array {
		return $this->config->getSystemValue('welcome_sendgrid_template_ids', '');
	}
	public function getMainDomain() : string {
		return $this->config->getSystemValue('main_domain', '');
	}
	public function setUserLanguage(string $username, string $language = 'en') {
		$this->config->setUserValue($username, 'core', 'lang', $language);
	}
	public function setRecoveryEmailVerificationStatus(string $username, string $value = 'false') {
		$this->config->setUserValue($username, 'email-recovery', 'recovery-email-verified', $value);
	}
	
	private function createSendGridEmail(string $fromEmail, string $fromName, string $username, string $displayname, string $userEmail, string $templateID) : \SendGrid\Mail\Mail {
		$mainDomain = $this->getMainDomain();

		$email = new \SendGrid\Mail\Mail();
		$email->setFrom($fromEmail, $fromName);
		$email->addTo($userEmail, $displayname);
		$email->setTemplateId($templateID);
		$email->addDynamicTemplateDatas([
			"username" => $username,
			"mail_domain" => $mainDomain,
			"display_name" => $displayname
		]);
		return $email;
	}
	private function sendEmailWithSendGrid(\SendGrid\Mail\Mail $email, string $sendgridAPIkey): void {
		$sendgrid = new \SendGrid($sendgridAPIkey);
		$response = $sendgrid->send($email, [ CURLOPT_TIMEOUT => 15 ]);

		if ($response->statusCode() < 200 || $response->statusCode() > 299) {
			$this->logger->error("SendGrid API error - Status Code: " . $response->statusCode());
		}
	}
	/**
	 * Register a new user.
	 *
	 * @param string $displayname The display name of the user.
	 * @param string $recoveryemail The recovery email address for the user.
	 * @param string $username The chosen username for the user.
	 * @param string $userEmail The email address of the user.
	 * @param string $password The password chosen by the user.
	 *
	 * @return array An array containing information about the registered user.
	 * @throws Exception If the username or recovery email is already taken.
	 */
	public function registerUser(string $displayname, string $recoveryEmail, string $username, string $userEmail, string $password): array {

		if ($this->userExists($username)) {
			throw new Exception("Username is already taken.");
		}
		if (!empty($recoveryEmail) && $this->checkRecoveryEmailAvailable($recoveryEmail)) {
			throw new Exception("Recovery email address is already taken.");
		}
		return $this->addNewUserToLDAP($displayname, $recoveryEmail, $username, $userEmail, $password);
		
	}
	/**
	 * Add a new user to the LDAP directory.
	 *
	 * @param string $displayname The display name of the new user.
	 * @param string $recoveryEmail The recovery email address of the new user.
	 * @param string $username The username of the new user.
	 * @param string $userEmail The email address of the new user.
	 * @param string $password The password of the new user.
	 *
	 * @return array Information about the added user.
	 * @throws Exception If there is an error while creating the Murena account.
	 */
	private function addNewUserToLDAP(string $displayname, string $recoveryEmail, string $username, string $userEmail, string $password): ?array {
		try {
			$connection = $this->LDAPConnectionService->getLDAPConnection();
			$base = $this->LDAPConnectionService->getLDAPBaseUsers()[0];
		
			$newUserDN = "username=$username," . $base;
		
			$newUserEntry = [
				'mailAddress' => $userEmail,
				'username' => $username,
				'usernameWithoutDomain' => $username,
				'userPassword' => $password,
				'displayName' => $displayname,
				'quota' => $this->LDAPConnectionService->getLdapQuota(),
				'recoveryMailAddress' => $recoveryEmail,
				'active' => 'TRUE',
				'mailActive' => 'TRUE',
				'userClusterID' => $this->apiConfig['userCluserId'],
				'objectClass' => $this->apiConfig['objectClass']
			];

			$ret = ldap_add($connection, $newUserDN, $newUserEntry);
		
			if (!$ret) {
				throw new Exception("Error while creating Murena account.");
			}
			return $newUserEntry;
		} catch (Exception $e) {
			$this->logger->error('Error adding adding new user to LDAP: ' . $username . ': ' . $e->getMessage());
			return null;
		}
	}
	/**
	 * Check if a recovery email address is available (not already taken by another user).
	 *
	 * @param string $recoveryEmail The recovery email address to check.
	 *
	 * @return bool True if the recovery email address is available, false otherwise.
	 */
	public function checkRecoveryEmailAvailable(string $recoveryEmail): bool {
		$recoveryEmail = strtolower($recoveryEmail);
		$users = $this->config->getUsersForUserValue('email-recovery', 'recovery-email', $recoveryEmail);
		if(count($users)) {
			return true;
		}
		return false;
	}

	/**
	 * Create a Hide My Email (HME) alias for a user.
	 *
	 * @param string $username The username for which to create the HME alias.
	 * @param string $resultmail The email address associated with the HME alias.
	 *
	 * @return void
	 */
	public function createHMEAlias(string $username, string $resultmail): void {
		$commonApiUrl = $this->apiConfig['commonApiUrl'];
		$aliasDomain = $this->apiConfig['aliasDomain'];
		$token = $this->apiConfig['commonServiceToken'];
		$commonApiVersion = $this->apiConfig['commonApiVersion'];
		
		$endpoint = $commonApiVersion . '/aliases/hide-my-email/';
		$url = $commonApiUrl . $endpoint . $resultmail;
		$data = array(
			"domain" => $aliasDomain
		);
		$headers = [
			"Authorization: Bearer $token"
		];
		$result = $this->curl->post($url, $data, $headers);
		$result = json_decode($result, true);

		$hmeAlias = isset($result['emailAlias']) ? $result['emailAlias'] : '';
		if($hmeAlias != '') {
			$hmeAliasAdded = $this->addHMEAliasInConfig($username, $hmeAlias);
			if (!$hmeAliasAdded) {
				$this->logger->error('Error adding HME Alias in config.');
			}
		}
	}
	/**
	 * Create a new domain alias for a user.
	 *
	 * @param string $username The username for which to create the domain alias.
	 * @param string $userEmail The email address associated with the domain alias.
	 *
	 * @return mixed The result of the domain alias creation request, decoded from JSON.
	 */
	public function createNewDomainAlias(string $username, string $userEmail): mixed {
		$commonApiUrl = $this->apiConfig['commonApiUrl'];
		$commonApiVersion = $this->config->getSystemValue('commonApiVersion', '');
		$domain = $this->apiConfig['mainDomain'];
		$token = $this->apiConfig['commonServiceToken'];
		$commonApiVersion = $this->apiConfig['commonApiVersion'];

		$endpoint = $commonApiVersion . '/aliases/';
		$url = $commonApiUrl . $endpoint . $userEmail;

		$data = array(
			"alias" => $username,
			"domain" => $domain
		);
		$headers = [
			"Authorization: Bearer $token"
		];
		
		$result = $this->curl->post($url, $data, $headers);
		$result = json_decode($result, true);
		return $result;
	}
	/**
	 * Set account data locally for a user.
	 *
	 * @param string $uid The unique identifier of the user.
	 * @param string $mailAddress The email address to set for the user.
	 * @param string $quota The quota to set for the user (in megabytes).
	 *
	 * @return void
	 */
	public function setAccountDataLocally(string $uid, string $mailAddress, string $quota): void {
		
		$user = $this->getUser($uid);
		if (is_null($user)) {
			$this->logger->error('User not found');
			return;
		}
		
		// Set the email address for the user
		$user->setEMailAddress($mailAddress);
		
		// Format and set the quota for the user (in megabytes)
		$quota = strval($quota) . ' MB';
		$user->setQuota($quota);
	}
}
