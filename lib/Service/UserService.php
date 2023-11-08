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

	private $curl;
	private Defaults $defaults;
	private ILogger $logger;
	protected $l10nFactory;
	private $apiConfig;
	private $LDAPConnectionService;
	public const USER_CLUSER_ID = 'HEL01';

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
			'main_domain' => $this->config->getSystemValue('main_domain', ''),
			'commonApiUrl' => rtrim($this->config->getSystemValue('common_services_url', ''), '/') . '/',
			'alias_domain' => $this->config->getSystemValue('alias_domain', ''),
			'common_service_token' => $this->config->getSystemValue('common_service_token', ''),
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
		$domain = $this->apiConfig['main_domain'];
		$exists = $this->userManager->userExists($uid.'@'.$domain);
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
	public function sendWelcomeEmail(string $displayname = '', string $uid, string $language = 'en') : void {
		
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
		
		$mainDomain = $this->getMainDomain();
		$toEmail = $uid;
		try {
			$email = $this->createSendGridEmail($fromEmail, $fromName, $uid, $displayname, $templateID, $uid, $mainDomain);
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
	
	public function registerUser(string $displayname, string $email, string $username, string $password, string $userlanguage = 'en'): array {
	
		if($username != '') {
			$userExists = $this->userExists($username);
			if ($userExists) {
				return [
					'success' => false,
					'statusCode' => 409,
					'message' => 'Username is already taken.',
				];
			}
		}
		if($email != '') {
			$emailExits = $this->checkRecoveryEmailAvailable($email);
			if ($emailExits) {
				return [
					'success' => false,
					'statusCode' => 409,
					'message' => 'Recovery email address is already taken.',
				];
			}
		}
		$this->addNewUserToLDAP($displayname, $email, $username, $password);
		
		$domain = $this->apiConfig['main_domain'];
		$this->sendWelcomeEmail($displayname, $username.'@'.$domain, $userlanguage);
		$this->setUserLanguage($username.'@'.$domain, $userlanguage);

		return [
			'success' => true,
			'statusCode' => 200,
			'message' => 'User registered successfully',
		];
	}
	private function addNewUserToLDAP(string $displayname, string  $email, string  $username, string  $password): void {
		$connection = $this->LDAPConnectionService->getLDAPConnection();
		$base = $this->LDAPConnectionService->getLDAPBaseUsers()[0];
	
		$domain = $this->apiConfig['main_domain'];
		$newUserDN = "username=$username@$domain," . $base;
	
		$newUserEntry = [
			'mailAddress' => "$username@$domain",
			'username' => "$username@$domain",
			'usernameWithoutDomain' => $username,
			'userPassword' => $password,
			'displayName' => $displayname,
			'quota' => $this->LDAPConnectionService->getLdapQuota(),
			'recoveryMailAddress' => $email,
			'active' => 'TRUE',
			'mailActive' => 'TRUE',
			'userClusterID' => self::USER_CLUSER_ID,
			'objectClass' => ['murenaUser', 'simpleSecurityObject']
		];
	
		$ret = ldap_add($connection, $newUserDN, $newUserEntry);
	
		if (!$ret) {
			throw new Exception("Error while creating Murena account.");
		}

	}
	
	public function checkRecoveryEmailAvailable(string $email) {
		$users = $this->userManager->getByEmail($email);
		if (count($users) >= 1) {
			return true;
		}
		return false;
	}

	protected function postCreationActions(array $userData, string $commonApiVersion = ''):void {
		$hmeAlias = '';
		$commonApiUrl = $this->commonApiUrl;
		$aliasDomain = $this->config->getSystemValue('alias_domain', '');
		try {
			// Create HME Alias
			$hmeAlias = $this->createHMEAlias($userData['mailAddress'], $commonApiUrl, $commonApiVersion, $aliasDomain);

			// Create alias with same name as email pointing to email to block this alias
			$domain = $this->config->getSystemValue('main_domain', '');
			$this->createNewDomainAlias($userData['username'], $userData['mailAddress'], $commonApiUrl, $commonApiVersion, $domain);
		} catch (Exception $e) {
			$this->logger->error('Error during alias creation for user: ' . $userData['username'] . ' with email: ' . $userData['mailAddress'] . ' : ' . $e->getMessage());
		}
		
		$userData['hmeAlias'] = $hmeAlias;
		sleep(2);
		$userData['quota'] = strval($userData['quota']) . ' MB';
		$this->setAccountDataAtNextcloud($userData);
	}

	private function createHMEAlias(string $resultmail, string $commonApiUrl, string $commonApiVersion, string $domain): string {
		
		$token = $this->config->getSystemValue('common_service_token', '');
		$endpoint = $commonApiVersion . '/aliases/hide-my-email/';
		$url = $commonApiUrl . $endpoint . $resultmail;
		$data = array(
			"domain" => $domain
		);
		$headers = [
			"Authorization: Bearer $token"
		];
		$this->logger->error('### createHMEAlias called.');
		$result = $this->curlRequest('POST', $url, $headers, $data);
		$output = $result['output'];
		if ($result['statusCode'] != 200) {
			$err = $output->message;
			// throw new Exception("createHMEAlias: CURL error: $err");
		}
		$alias = isset($output->emailAlias) ? $output->emailAlias : '';
		return $alias;
	}

	private function createNewDomainAlias(string $alias, string $resultmail, string $commonApiUrl, string $commonApiVersion, string $domain): void {
		$token = $this->config->getSystemValue('common_service_token', '');

		$endpoint = $commonApiVersion . '/aliases/';
		$url = $commonApiUrl . $endpoint . $resultmail;

		$data = array(
			"alias" => $alias,
			"domain" => $domain
		);
		$headers = [
			"Authorization: Bearer $token"
		];
		$this->logger->error('### createNewDomainAlias called.');
		$result = $this->curlRequest('POST', $url, $headers, $data);
		$output = $result['output'];
		if ($result['statusCode'] != 200) {
			$err = $output->message;
			// throw new Exception("createNewDomainAlias: CURL error: $err");
		}
	}


	private function setAccountDataAtNextcloud(array $userData): void {
		
		$data = $this->setAccountData($userData['mailAddress'], $userData['mailAddress'], $userData['recoveryMailAddress'], $userData['hmeAlias'], $userData['quota'], $userData['tosAccepted'], $userData['userlanguage']);
		
		if ($data['status'] != 200) {
			$this->logger->error('## setAccountDataAtNextcloud: Error creating account with status code '.$data['status'].' : ' . $data['error']);
		}

	}
}
