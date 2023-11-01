<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Service;

use Exception;
use OCP\IConfig;
use OCP\ILogger;

class AccountService {
	private $config;
	private $LDAPConnectionService;
	private $userService;
	private $logger;
	private $ecloudAccountsApiUrl;
	private $commonApiUrl;
	public function __construct(
		IConfig $config,
		LDAPConnectionService $LDAPConnectionService,
		UserService $userService,
		ILogger $logger
	) {
		$this->config = $config;
		$this->LDAPConnectionService = $LDAPConnectionService;
		$this->userService = $userService;
		$this->logger = $logger;

		$domain = $this->config->getSystemValue('main_domain', '');
		$this->ecloudAccountsApiUrl = $domain . '/apps/ecloud-accounts/api/';

		$this->commonApiUrl = $this->config->getSystemValue('common_services_url', '');
		$this->commonApiUrl = substr($this->commonApiUrl, -1) === '/' ? $this->commonApiUrl : $this->commonApiUrl . '/';
	}
	public function registerUser(string $displayname, string $email, string $username, string $password, string $userlanguage = 'en', bool $newsletter_eos, bool $newsletter_product) {
		$connection = $this->LDAPConnectionService->getLDAPConnection();
		$base = $this->LDAPConnectionService->getLDAPBaseUsers()[0];
	
		if($email != '') {
			// Check if the recovery Email Address already exists
			$filter = "(recoveryMailAddress=$email)";
			$searchResult = ldap_search($connection, $base, $filter);
		
			if (!$searchResult) {
				throw new Exception("Error while searching Murena recovery Email address.");
			}

			$entries = ldap_get_entries($connection, $searchResult);
			if ($entries['count'] > 0) {
				return false;
			}
		}
		
		$domain = $this->config->getSystemValue('main_domain', '');
		$newUserDN = "username=$username@$domain," . $base;
		$userClusterID = 'HEL01';
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
			'userClusterID' => $userClusterID,
			'objectClass' => ['murenaUser', 'simpleSecurityObject']
		];
	
		$ret = ldap_add($connection, $newUserDN, $newUserEntry);
	
		if (!$ret) {
			throw new Exception("Error while creating Murena account.");
		}
		$this->userService->sendWelcomeEmail($displayname, $username.'@'.$domain, $username.'@'.$domain, $userlanguage);
		
		$newUserEntry['userlanguage'] = $userlanguage;
		$newUserEntry['tosAccepted'] = true;
		
		$this->postCreationActions($newUserEntry, 'v2');
		if($newsletter_eos || $newsletter_product) {
			// $this->userService->createContactInSendGrid($username.'@'.$domain, $displayname, $newsletter_eos, $newsletter_product);
		}
		return true;
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
		
		$data = $this->userService->setAccountData($userData['mailAddress'], $userData['mailAddress'], $userData['recoveryMailAddress'], $userData['hmeAlias'], $userData['quota'], $userData['tosAccepted'], $userData['userlanguage']);
		
		if ($data['status'] != 200) {
			$this->logger->error('## setAccountDataAtNextcloud: Error creating account with status code '.$data['status'].' : ' . $data['error']);
		}

	}


	public function checkUsernameAvailable(string $username) {
		$connection = $this->LDAPConnectionService->getLDAPConnection();
		$base = $this->LDAPConnectionService->getLDAPBaseUsers()[0];
	
		// Check if the username already exists
		$filter = "(usernameWithoutDomain=$username)";
		$searchResult = ldap_search($connection, $base, $filter);
	
		if (!$searchResult) {
			throw new Exception("Error while searching Murena username.");
		}
	
		$entries = ldap_get_entries($connection, $searchResult);
		if ($entries['count'] == 0) {
			return true;
		}
		return false;
	}

	public function curlRequest(string $method, string $url, array $headers = [], array $data = []) : array {

		$this->logger->error('### curlRequest: URL: '.$url);
		$this->logger->error('### curlRequest: METHOD: '.$method);
		$this->logger->error('### curlRequest: ALIAS: '.json_encode($headers));
		$this->logger->error('### curlRequest: DOMAIN: '.json_encode($data));

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

		curl_setopt($ch, CURLOPT_URL, $url);
		if (!empty($headers)) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}

		if ('POST' === $method && !empty($data)) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		}
		
		$output = curl_exec($ch);
		$output = json_decode($output, false);
		$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		$result = array(
			'statusCode' => $statusCode,
			'output' => $output
		);
		$this->logger->error('### curlRequest: OUTPUT: '.json_encode($result));
		return $result;
	}
}
