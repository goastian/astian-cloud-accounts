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
use phpseclib\Net\SSH2;
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
			'mainDomain' => $this->config->getSystemValue('main_domain', ''),
			'commonApiUrl' => rtrim($this->config->getSystemValue('common_services_url', ''), '/') . '/',
			'common_service_token' => $this->config->getSystemValue('common_service_token', ''),
			'aliasDomain' => $this->config->getSystemValue('alias_domain', ''),
			'commonApiVersion' => 'v2',
			'postfixHostname' => "postfixadmin",
			'postfixUser' => "pfexec",
			'postfixadminSSHPassword' => 'wpzfLPEPV5xWDmEijI0b'
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
		$domain = $this->apiConfig['mainDomain'];
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
	public function setTOS(string $uid, bool $tosAccepted): bool {
		try {
			$this->config->setUserValue($uid, 'terms_of_service', 'tosAccepted', intval($tosAccepted));
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
	public function sendWelcomeEmail(string $displayname, string $uid, string $language) : void {
		
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
		$newUserEntry = $this->addNewUserToLDAP($displayname, $email, $username, $password);
		$newUserEntry['userlanguage'] = $userlanguage;
		$newUserEntry['tosAccepted'] = true;
		$domain = $this->apiConfig['mainDomain'];
		$newEmailAddress = $username.'@'.$domain;
		
		$this->createUserAtNextCloud($newEmailAddress, $password);
		$this->addUserToMailbox($newUserEntry);
		$this->postCreationActions($newUserEntry);
		$this->sendWelcomeEmail($displayname, $newEmailAddress, $userlanguage);
		$this->setUserLanguage($newEmailAddress, $userlanguage);
		
		return [
			'success' => true,
			'statusCode' => 200,
			'message' => 'User registered successfully',
		];
	}
	private function addNewUserToLDAP(string $displayname, string  $email, string  $username, string  $password): array {
		$connection = $this->LDAPConnectionService->getLDAPConnection();
		$base = $this->LDAPConnectionService->getLDAPBaseUsers()[0];
	
		$domain = $this->apiConfig['mainDomain'];
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
		return $newUserEntry;
	}
	
	public function checkRecoveryEmailAvailable(string $email) {
		$users = $this->userManager->getByEmail($email);
		if (count($users) >= 1) {
			return true;
		}
		return false;
	}

	protected function postCreationActions(array $userData):void {
		$hmeAlias = '';
		try {
			// Create HME Alias
			$hmeAlias = $this->createHMEAlias($userData['mailAddress']);
			// Create alias with same name as email pointing to email to block this alias
			$this->createNewDomainAlias($userData['username'], $userData['mailAddress']);
		} catch (Exception $e) {
			$this->logger->error('Error during alias creation for user: ' . $userData['username'] . ' with email: ' . $userData['mailAddress'] . ' : ' . $e->getMessage());
		}
		$userData['hmeAlias'] = $hmeAlias;
		sleep(2);
		$userData['quota'] = strval($userData['quota']) . ' MB';
		$this->setAccountDataAtNextcloud($userData);
	}

	private function createHMEAlias(string $resultmail): string {
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
		$this->logger->error('### createHMEAlias called.');
		$result = $this->curl->post($url, $data, $headers);
		$result = json_decode($result, true);
		$alias = isset($result->emailAlias) ? $result->emailAlias : '';
		return $alias;
	}

	private function createNewDomainAlias(string $alias, string $resultmail): mixed {
		$commonApiUrl = $this->apiConfig['commonApiUrl'];
		$commonApiVersion = $this->config->getSystemValue('commonApiVersion', '');
		$domain = $this->apiConfig['mainDomain'];
		$token = $this->apiConfig['common_service_token'];
		$commonApiVersion = $this->apiConfig['commonApiVersion'];

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
		
		$result = $this->curl->post($url, $data, $headers);
		$result = json_decode($result, true);
		return $result;
	}
	private function createUserAtNextCloud(string $username, string $password): void {

		$user = $this->getUser($username);
		if (is_null($user)) {

			try {
				$this->logger->error('Creating new user');
				$user = $this->userManager->createUser(
					$username,
					$password
				);
			} catch (\Exception $e) {
				$this->logger->error('error: ' . $e->getMessage());
				return;
			}

			if ($user instanceof IUser) {
				$this->logger->error('Info: The user "' . $user->getUID() . '" was created successfully');
			} else {
				$this->logger->error('Error: An error occurred while creating the user.');
			}
		}
	}
	private function addUserToMailbox(array $userData): void {
		$PF_HOSTNAME = $this->apiConfig['postfixHostname'];
		$PF_USER = $this->apiConfig['postfixUser'];
		$PF_PWD = $this->apiConfig['postfixadminSSHPassword'];

		$ssh = new SSH2($PF_HOSTNAME);
		if (!$ssh->login($PF_USER, $PF_PWD)) {
			$this->logger->error('### addUserToMailbox Error Login to SSH.');
		}
		$mailAddress = $userData['mailAddress'];
		$password = $userData['userPassword'];
		$displayName = $userData['displayName'];
		$quota = $userData['quota'];
		$creationFeedBack = explode("\n", $ssh->exec('/postfixadmin/scripts/postfixadmin-cli mailbox add ' . escapeshellarg($mailAddress) . ' --password ' . escapeshellarg($password) . ' --password2 ' . escapeshellarg($password) . ' --name ' . escapeshellarg($displayName) . ' --quota ' . $quota . ' --active 1 --welcome-mail 0 2>&1'));
		$isCreated = preg_grep('/added/', $creationFeedBack);
		$this->logger->error('### addUserToMailbox isCreated::'.json_encode($isCreated));
		if (empty($isCreated)) {
			$this->logger->error('### addUserToMailbox Error creating account.');
		}
	}
	private function setAccountDataAtNextcloud(array $userData): void {
		$uid = $userData['mailAddress'];
		$recoveryEmail = $userData['recoveryMailAddress'];
		$hmeAlias = $userData['hmeAlias'];
		$quota = $userData['quota'];
		$tosAccepted = $userData['tosAccepted'];
		$user = $this->getUser($uid);
		if (is_null($user)) {
			$this->logger->error('## setAccountDataAtNextcloud: User not found');
		}
		$mailAddress = $uid;
		$user->setEMailAddress($mailAddress);
		$user->setQuota($quota);
		
		$tos = $this->setTOS($uid, $tosAccepted);
		if (!$tos) {
			$this->logger->error('## Error adding TOS value in config.');
		}
		if($recoveryEmail != '') {
			$recoveryEmailUpdated = $this->setRecoveryEmail($uid, $recoveryEmail);
			if (!$recoveryEmailUpdated) {
				$this->logger->error('## Error adding recoveryEmail in config.');
			}
		}
		if($hmeAlias != '') {
			$hmeAliasAdded = $this->addHMEAliasInConfig($uid, $hmeAlias);
			if (!$hmeAliasAdded) {
				$this->logger->error('## Error adding HME Alias in config.');
			}
		}
	}
}
