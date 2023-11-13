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
			'common_service_token' => $this->config->getSystemValue('common_service_token', ''),
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
		try {
			$this->config->setUserValue($uid, 'email-recovery', 'recovery-email', $recoveryEmail);
		} catch (Exception $e) {
			throw new Exception("setRecoveryEmail error: " . $e->getMessage());
		}
	}
	public function setTOS(string $uid, bool $tosAccepted): void {
		try {
			$this->config->setUserValue($uid, 'terms_of_service', 'tosAccepted', intval($tosAccepted));
		} catch (Exception $e) {
			throw new Exception("setTOS error: " . $e->getMessage());
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
	public function sendWelcomeEmail(string $displayname, string $username, string $userEmail, string $language) : void {
		
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
			$email = $this->createSendGridEmail($fromEmail, $fromName, $username, $displayname, $templateID);
			$this->sendEmailWithSendGrid($email, $sendgridAPIkey);
		} catch (Throwable $e) {
			$this->logger->error('Error sending username: ' . $username . ': ' . $e->getMessage());
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
	private function setUserLanguage(string $username, string $language) {
		$this->config->setUserValue($username, 'core', 'lang', $language);
	}
	private function createSendGridEmail(string $fromEmail, string $fromName, string $username, string $displayname, string $templateID) : \SendGrid\Mail\Mail {
		$mainDomain = $this->getMainDomain();
		$userEmail = $username.'@'.$mainDomain;

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
			throw new \Exception("SendGrid API error - Status Code: " . $response->statusCode());
		}
	}
	
	public function registerUser(string $displayname, string $recoveryemail, string $username, string $userEmail, string $password, string $userlanguage = 'en'): void {

		$userExists = $this->userExists($username);
		if ($userExists) {
			throw new Exception("Username is already taken.");
		}
		if($recoveryemail !== '') {
			$emailExists = $this->checkRecoveryEmailAvailable($recoveryemail);
			if ($emailExists) {
				throw new Exception("Recovery email address is already taken.");
			}
		}
		
		$newUserEntry = $this->addNewUserToLDAP($displayname, $recoveryemail, $username, $userEmail, $password);
		
		$this->createHMEAlias($username, $userEmail);
		$this->createNewDomainAlias($username, $userEmail);
		
		$newUserEntry['userlanguage'] = $userlanguage;
		$newUserEntry['tosAccepted'] = true;
		$newUserEntry['quota'] = strval($newUserEntry['quota']) . ' MB';
		$this->setAccountDataLocally($newUserEntry);

		$this->setUserLanguage($username, $userlanguage);
	}
	private function addNewUserToLDAP(string $displayname, string $recoveryEmail, string $username, string $userEmail, string $password): array {
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
	}
	
	public function checkRecoveryEmailAvailable(string $recoveryEmail): bool {
		$connection = $this->LDAPConnectionService->getLDAPConnection();
		$base = $this->LDAPConnectionService->getLDAPBaseUsers()[0];
	
		// Check if the recoveryMailAddress already exists
		$filter = "(recoveryMailAddress=$recoveryEmail)";
		$searchResult = ldap_search($connection, $base, $filter);
	
		if (!$searchResult) {
			throw new Exception("Error while searching Murena recovery email address.");
		}
	
		$entries = ldap_get_entries($connection, $searchResult);
		if ($entries['count'] > 0) {
			return true;
		}
		return false;

	}

	private function createHMEAlias(string $username, string $resultmail): void {
		$commonApiUrl = $this->apiConfig['commonApiUrl'];
		$aliasDomain = $this->apiConfig['aliasDomain'];
		$token = $this->apiConfig['common_service_token'];
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

	private function createNewDomainAlias(string $username, string $userEmail): mixed {
		$commonApiUrl = $this->apiConfig['commonApiUrl'];
		$commonApiVersion = $this->config->getSystemValue('commonApiVersion', '');
		$domain = $this->apiConfig['mainDomain'];
		$token = $this->apiConfig['common_service_token'];
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
	private function setAccountDataLocally(array $userData): void {
		$uid = $userData['username'];
		$user = $this->getUser($uid);
		if (is_null($user)) {
			$this->logger->error('User not found');
			return;
		}
		
		$mailAddress = $userData['mailAddress'];
		$user->setEMailAddress($mailAddress);
		
		$recoveryEmail = $userData['recoveryMailAddress'];
		if($recoveryEmail !== '') {
			$this->setRecoveryEmail($uid, $recoveryEmail);
		}
		
		$quota = $userData['quota'];
		$user->setQuota($quota);
		
		$tosAccepted = $userData['tosAccepted'];
		$this->setTOS($uid, $tosAccepted);
	}

	public function getRandomCharacter(): string {
		$numbers = '123456789';
		$randomNumber = rand(0, strlen($numbers) - 1);
		return $numbers[$randomNumber];
	}
	public function getOperator(): string {
		$operator = '+-';
		$operatorNumber = rand(0, strlen($operator) - 1);
		return $operator[$operatorNumber];
	}
	
	public function calculateResult($operand1, $operand2, $operator): mixed {
		$operand1 = floatval($operand1);
		$operand2 = floatval($operand2);
	
		switch ($operator) {
			case '+':
				return $operand1 + $operand2;
			case '-':
				return $operand1 - $operand2;
			default:
				return null;
		}
	}
	
	public function checkAnswer($operand1, $operand2, $operator, $humanverificationCode): bool {
		$result = $this->calculateResult($operand1, $operand2, $operator);
		$captchaResult = intval($result, 10);
	
		if (intval($humanverificationCode, 10) !== $captchaResult) {
			return true;
		} else {
			return false;
		}
	}
}
