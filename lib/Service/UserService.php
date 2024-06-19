<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Service;

require __DIR__ . '/../../vendor/autoload.php';

use Exception;
use OCA\EcloudAccounts\AppInfo\Application;
use OCA\EcloudAccounts\Exception\AddUsernameToCommonStoreException;
use OCA\EcloudAccounts\Exception\BlacklistedEmailException;
use OCA\EcloudAccounts\Exception\LDAPUserCreationException;
use OCP\Defaults;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
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
	/**
	 * @var IAppData
	 */
	private $appData;
	private const BLACKLISTED_DOMAINS_FOLDER_NAME = 'ecloud-accounts';
	private const BLACKLISTED_DOMAINS_FILE_NAME = 'blacklisted_domains.json';
	private const BLACKLISTED_DOMAINS_URL = 'https://raw.githubusercontent.com/disposable/disposable-email-domains/master/domains.json';

	public function __construct($appName, IUserManager $userManager, IConfig $config, CurlService $curlService, ILogger $logger, Defaults $defaults, IFactory $l10nFactory, LDAPConnectionService $LDAPConnectionService, IAppData $appData) {
		$this->userManager = $userManager;
		$this->config = $config;
		$this->appConfig = $this->config->getSystemValue($appName);
		$this->curl = $curlService;
		$this->logger = $logger;
		$this->defaults = $defaults;
		$this->l10nFactory = $l10nFactory;
		$this->LDAPConnectionService = $LDAPConnectionService;
		$this->appData = $appData;
		$commonServiceURL = $this->config->getSystemValue('common_services_url', '');

		if (!empty($commonServiceURL)) {
			$commonServiceURL = rtrim($commonServiceURL, '/') . '/';
		}

		$this->apiConfig = [
			'mainDomain' => $this->config->getSystemValue('main_domain', ''),
			'commonServicesURL' => $commonServiceURL,
			'commonServicesToken' => $this->config->getSystemValue('common_services_token', ''),
			'aliasDomain' => $this->config->getSystemValue('alias_domain', ''),
			'commonApiVersion' => $this->config->getSystemValue('common_api_version', ''),
			'userClusterID' => $this->config->getSystemValue('user_cluster_id', ''),
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
		return null;
	}

	public function setRecoveryEmail(string $uid, string $recoveryEmail): void {
		$this->config->setUserValue($uid, 'email-recovery', 'recovery-email', $recoveryEmail);
	}
	public function setUnverifiedRecoveryEmail(string $uid, string $recoveryEmail): void {
		$this->config->setUserValue($uid, 'email-recovery', 'unverified-recovery-email', $recoveryEmail);
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
			$this->logger->error("Error adding HME alias '$alias' to config for user with UID: $uid. Error: " . $e->getMessage());
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
	public function deleteEmailAccount(string $email) {
		$commonServicesURL = $this->apiConfig['commonServicesURL'];
		$commonApiVersion = $this->apiConfig['commonApiVersion'];

		if (!isset($commonServicesURL) || empty($commonServicesURL)) {
			return;
		}

		$endpoint = $commonApiVersion . '/emails/' . $email;
		$url = $commonServicesURL . $endpoint; // DELETE /v2/emails/@email
		
		$params = [];

		$token = $this->apiConfig['commonServicesToken'];
		$headers = [
			"Authorization: Bearer $token"
		];
		
		$this->curl->delete($url, $params, $headers);

		if ($this->curl->getLastStatusCode() !== 200) {
			throw new Exception('Error deleting mail folder of' . $email . '. Status Code: '.$this->curl->getLastStatusCode());
		}
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
	public function getLegacyDomain() : string {
		return $this->config->getSystemValue('legacy_domain', '');
	}
	public function setUserLanguage(string $username, string $language = 'en') {
		$this->config->setUserValue($username, 'core', 'lang', $language);
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
	 * @return void
	 * @throws Exception If the username or recovery email is already taken.
	 * @throws LDAPUserCreationException If there is an error adding new entry to LDAP store
	 */
	public function registerUser(string $displayname, string $recoveryEmail, string $username, string $userEmail, string $password): void {
		
		if ($this->userExists($username) || $this->isUsernameTaken($username)) {
			throw new Exception("Username '$username' is already taken.");
		}
		if (!empty($recoveryEmail)) {
			$this->validateRecoveryEmail($recoveryEmail);
		}
		$this->addNewUserToLDAP($displayname, $username, $userEmail, $password);
	}
	/**
	 * Validates the recovery email address.
	 *
	 * @param string $recoveryEmail The recovery email address to be validated.
	 * @throws Exception If the recovery email address has an incorrect format, is already taken, or if the domain is disallowed.
	 * @return void
	 */
	public function validateRecoveryEmail(string $recoveryEmail): void {
		if (!$this->isValidEmailFormat($recoveryEmail)) {
			throw new Exception('Recovery email address has an incorrect format.');
		}
		if ($this->checkRecoveryEmailAvailable($recoveryEmail)) {
			throw new Exception('Recovery email address is already taken.');
		}
		if ($this->isRecoveryEmailDomainDisallowed($recoveryEmail)) {
			throw new Exception('You cannot set an email address with a Murena domain as recovery email address.');
		}
		if ($this->isBlacklistedEmail($recoveryEmail)) {
			throw new BlacklistedEmailException('The domain of this email address is blacklisted. Please provide another recovery address.');
		}
	}
	/**
	 * Check if an email domain is blacklisted against a JSON list of disposable email domains.
	 *
	 * @param string $email The email address to check.
	 * @return bool True if the email domain is blacklisted, false otherwise.
	 */
	public function isBlacklistedEmail(string $email): bool {
		// Get the blacklisted domains from configuration
		if (!$this->ensureDocumentsFolder()) {
			return false;
		}
		$blacklistedDomainsInJson = $this->getBlacklistedDomainData();
		if (empty($blacklistedDomainsInJson)) {
			return false;
		}
		$blacklistedDomains = json_decode($blacklistedDomainsInJson, true);

		if (empty($blacklistedDomains)) {
			return false;
		}

		// Split the email address into parts using explode
		$emailParts = explode('@', $email);
		
		// Extract the domain part
		$emailDomain = strtolower(end($emailParts));
		
		// Check if the email domain is in the blacklisted domains array
		return in_array($emailDomain, $blacklistedDomains);
	}
	/**
	 * Add a new user to the LDAP directory.
	 *
	 * @param string $displayname The display name of the new user.
	 * @param string $username The username of the new user.
	 * @param string $userEmail The email address of the new user.
	 * @param string $password The password of the new user.
	 *
	 * @return void
	 * @throws LDAPUserCreationException If there is an error adding new entry to LDAP store
	 */
	private function addNewUserToLDAP(string $displayName, string $username, string $userEmail, string $password): void {
		$connection = $this->LDAPConnectionService->getLDAPConnection();
		$base = $this->LDAPConnectionService->getLDAPBaseUsers()[0];
		$newUserDN = "username=$username," . $base;
		
		$displayName = htmlspecialchars($displayName);
		$quota = $this->getDefaultQuota() * 1024 * 1024;
		
		$newUserEntry = [
			'mailAddress' => $userEmail,
			'username' => $username,
			'usernameWithoutDomain' => $username,
			'userPassword' => $password,
			'displayName' => $displayName,
			'quota' => $quota,
			'recoveryMailAddress' => '',
			'active' => 'TRUE',
			'mailActive' => 'TRUE',
			'userClusterID' => $this->apiConfig['userClusterID'],
			'objectClass' => $this->apiConfig['objectClass']
		];
		
		$ret = ldap_add($connection, $newUserDN, $newUserEntry);
		
		if (!$ret) {
			throw new LDAPUserCreationException("Error while adding entry to LDAP for username: " .  $username . ' Error: ' . ldap_error($connection), ldap_errno($connection));
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
		$users = $this->config->getUsersForUserValue('email-recovery', 'unverified-recovery-email', $recoveryEmail);
		if(count($users)) {
			return true;
		}
		return false;
	}

	/**
	 * Check if a recovery email address domain is restricted for some domains
	 *
	 * @param string $recoveryEmail The recovery email address to check.
	 *
	 * @return bool True if the recovery email address is disallowed, false otherwise.
	 */
	public function isRecoveryEmailDomainDisallowed(string $recoveryEmail): bool {
		
		$recoveryEmail = strtolower($recoveryEmail);
		
		$emailParts = explode('@', $recoveryEmail);
		$domain = $emailParts[1] ?? '';
		
		$legacyDomain = $this->getLegacyDomain();
		$mainDomain = $this->getMainDomain();
		
		$restrictedDomains = [ $legacyDomain, $mainDomain ];

		return in_array($domain, $restrictedDomains);
	}

	/**
	 * Check if a recovery email address is in valid format
	 *
	 * @param string $recoveryEmail The recovery email address to check.
	 *
	 * @return bool True if the recovery email address is valid, false otherwise.
	 */
	public function isValidEmailFormat(string $recoveryEmail): bool {
		return filter_var($recoveryEmail, FILTER_VALIDATE_EMAIL) !== false;
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
		$commonServicesURL = $this->apiConfig['commonServicesURL'];
		$aliasDomain = $this->apiConfig['aliasDomain'];
		$token = $this->apiConfig['commonServicesToken'];
		$commonApiVersion = $this->apiConfig['commonApiVersion'];
		
		$endpoint = $commonApiVersion . '/aliases/hide-my-email/';
		$url = $commonServicesURL . $endpoint . $resultmail;
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
				$this->logger->error("Failed to add HME Alias '$hmeAlias' for username '$username' in config.");
			}
		} else {
			$this->logger->error("Failed to create HME Alias for username '$username'. Response: " . json_encode($result));
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
		$commonServicesURL = $this->apiConfig['commonServicesURL'];
		$commonApiVersion = $this->config->getSystemValue('commonApiVersion', '');
		$domain = $this->apiConfig['mainDomain'];
		$token = $this->apiConfig['commonServicesToken'];
		$commonApiVersion = $this->apiConfig['commonApiVersion'];

		$endpoint = $commonApiVersion . '/aliases/';
		$url = $commonServicesURL . $endpoint . $userEmail;

		$data = array(
			"alias" => $username,
			"domain" => $domain
		);
		$headers = [
			"Authorization: Bearer $token"
		];
		
		$result = $this->curl->post($url, $data, $headers);
		$result = json_decode($result, true);
		if ($this->curl->getLastStatusCode() !== 200) {
			$this->logger->error("Failed to create new domain alias '$username' for email '$userEmail'.");
		}
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
	public function setAccountDataLocally(string $uid, string $mailAddress): void {
		$user = $this->getUser($uid);
		if (is_null($user)) {
			throw new Exception("User with username '$uid' not found.");
		}
		// Set the email address for the user
		$user->setEMailAddress($mailAddress);
		$quota = $this->getDefaultQuota();
		// Format and set the quota for the user (in megabytes)
		$quota = strval($quota) . ' MB';
		$user->setQuota($quota);
	}

	public function isUsernameTaken(string $username) : bool {
		$commonServicesURL = $this->apiConfig['commonServicesURL'];
		$commonApiVersion = $this->apiConfig['commonApiVersion'];

		if (!isset($commonServicesURL) || empty($commonServicesURL)) {
			return false;
		}
		$endpoint = $commonApiVersion . '/users/';
		$url = $commonServicesURL . $endpoint . $username;

		$token = $this->apiConfig['commonServicesToken'];
		$headers = [
			"Authorization: Bearer $token"
		];

		$this->curl->get($url, [], $headers);

		$statusCode = $this->curl->getLastStatusCode();
		if ($statusCode === 404) {
			return false;
		}

		if ($statusCode === 200) {
			return true;
		}
		throw new Exception("Error checking if username '$username' is taken at common source, status code: " . (string) $statusCode);
	}
	/**
	 * Adds a username to the common data store.
	 *
	 * This method sends a POST request to the common data store API endpoint to add a username.
	 * If the operation is successful, the username will be added to the data store.
	 * If the operation fails, an exception will be thrown.
	 *
	 * @param string $username The username to add to the common data store.
	 *
	 * @throws AddUsernameToCommonStoreException If an error occurs while adding the username to the common data store.
	 */
	public function addUsernameToCommonDataStore(string $username) : void {
		$commonServicesURL = $this->apiConfig['commonServicesURL'];
		$commonApiVersion = $this->apiConfig['commonApiVersion'];

		if (!isset($commonServicesURL) || empty($commonServicesURL)) {
			return;
		}
		$endpoint = $commonApiVersion . '/users/';
		$url = $commonServicesURL . $endpoint ;
		
		$params = [
			'username' => $username
		];

		$token = $this->apiConfig['commonServicesToken'];
		$headers = [
			"Authorization: Bearer $token"
		];
		
		$this->curl->post($url, $params, $headers);

		if ($this->curl->getLastStatusCode() !== 200) {
			throw new AddUsernameToCommonStoreException("Error adding username '$username' to common data store.");
		}
	}

	public function mapActiveAttributesInLDAP(string $username, bool $isEnabled): void {
		$userActiveAttributes = $this->getActiveAttributes($isEnabled);
		$this->updateAttributesInLDAP($username, $userActiveAttributes);
	}

	private function getActiveAttributes(bool $isEnabled): array {
		return [
			'active' => $isEnabled ? 'TRUE' : 'FALSE',
			'mailActive' => $isEnabled ? 'TRUE' : 'FALSE',
		];
	}

	public function updateAttributesInLDAP(string $username, array $attributes): void {
		if (!$this->LDAPConnectionService->isLDAPEnabled()) {
			return;
		}
	
		$conn = $this->LDAPConnectionService->getLDAPConnection();
		$userDn = $this->LDAPConnectionService->username2dn($username);
	
		if ($userDn === false) {
			throw new Exception('Could not find DN for username: ' . $username);
		}
	
		if (!ldap_modify($conn, $userDn, $attributes)) {
			throw new Exception('Could not modify user ' . $username . ' entry at LDAP server. Attributes: ' . print_r($attributes, true));
		}
	
		$this->LDAPConnectionService->closeLDAPConnection($conn);
	}
	
	private function getDefaultQuota() {
		return $this->config->getSystemValueInt('default_quota_in_megabytes', 1024);
	}
	/**
	 * update the blacklisted domains file.
	 *
	 */
	public function updateBlacklistedDomains(): void {
		$blacklisted_domain_url = self::BLACKLISTED_DOMAINS_URL;
		$json_data = file_get_contents($blacklisted_domain_url);
		$json_data = '[{\'test\':\'test1\'}]';
		$this->setBlacklistedDomainsData($json_data);
	}
	/**
	 * Store blacklisted domain data in a file within AppData.
	 *
	 * @param string $data The data to be stored in the file.
	 */
	private function setBlacklistedDomainsData(string $data) {
		$file = $this->getBlacklistedDomainsFilePath();
		$file->putContent($data);
		return $file;
	}
	/**
	 * Retrieve the blacklisted domain file path
	 *
	 */
	private function getBlacklistedDomainsFilePath() {
		$foldername = self::BLACKLISTED_DOMAINS_FOLDER_NAME;
		try {
			$currentFolder = $this->appData->getFolder($foldername);
		} catch (NotFoundException $e) {
			$currentFolder = $this->appData->newFolder($foldername);
		}
		$filename = self::BLACKLISTED_DOMAINS_FILE_NAME;
		if ($currentFolder->fileExists($filename)) {
			$currentFolder->getFile($filename)->delete();
		}
		return $currentFolder->newFile($filename);
	}
	/**
	 * Retrieve the blacklisted domain data.
	 *
	 */
	public function getBlacklistedDomainData() {
		$foldername = self::BLACKLISTED_DOMAINS_FOLDER_NAME;
		$document = self::BLACKLISTED_DOMAINS_FILE_NAME;
		return $this->appData->getFolder($foldername)->getFile((string) $document);
	}
	/**
	 * Ensure the specified folder exists within AppData.
	 *
	 */
	private function ensureDocumentsFolder(): bool {
		$foldername = self::BLACKLISTED_DOMAINS_FOLDER_NAME;
		try {
			$this->appData->getFolder($foldername);
		} catch (NotFoundException $e) {
			$this->logger->logException('Blacklisted domains file not found!');
			return false;
		} catch (\RuntimeException $e) {
			$this->logger->logException($e);
			return false;
		}
		return true;
	}
}
