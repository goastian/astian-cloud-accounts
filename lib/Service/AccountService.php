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
		return;
	}

	private function createHMEAlias(string $resultmail, string $commonApiUrl, string $commonApiVersion, string $domain): string {
		$token = $this->config->getSystemValue('common_service_token', '');
		
		$endpoint = $commonApiVersion . '/aliases/hide-my-email/';
		$url = $commonApiUrl . $endpoint . $resultmail;

		$this->logger->error('### createHMEAlias: URL: '.$url);
		$this->logger->error('### createHMEAlias: domain: '.$domain);

		$data = json_encode(["domain" => $domain]);
		$headers = [
			"Authorization: Bearer $token",
			"Content-Type: application/json"
		];
	
		$ch = curl_init();
	
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	
		$response = curl_exec($ch);
		$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	
		if ($response === false) {
			$error = curl_error($ch);
			curl_close($ch);
			throw new Exception("CURL error: $error");
		}
	
		curl_close($ch);
	
		if ($statusCode != 200) {
			try {
				$output = json_decode($response);
				$err = isset($output->message) ? $output->message : "Unknown error occurred";
				throw new Exception($err);
			} catch (Exception $e) {
				$this->logger->error('Error creating createHMEAlias : ' . $e->getMessage());
			}
		}
	
		$output = json_decode($response);
		$alias = isset($output->emailAlias) ? $output->emailAlias : '';
		return $alias;
	}

	private function createNewDomainAlias(string $alias, string $resultmail, string $commonApiUrl, string $commonApiVersion, string $domain): void {
		$token = $this->config->getSystemValue('common_service_token', '');
		
		$endpoint = $commonApiVersion . '/aliases/';
		$url = $commonApiUrl . $endpoint . $resultmail;

		$this->logger->error('### createNewDomainAlias: URL: '.$url);
		$this->logger->error('### createNewDomainAlias: alias: '.$alias);
		$this->logger->error('### createNewDomainAlias: domain: '.$domain);

		$data = json_encode([
			"alias" => $alias,
			"domain" => $domain
		]);

		$headers = [
			"Authorization: Bearer $token",
			"Content-Type: application/json"
		];

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$response = curl_exec($ch);
		$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	
		if ($response === false) {
			$error = curl_error($ch);
			curl_close($ch);
			throw new Exception("CURL error: $error");
		}

		curl_close($ch);

		if ($statusCode != 200) {
			$output = json_decode($response);
			$err = isset($output->message) ? $output->message : "Unknown error occurred";
			throw new Exception($err);
		}
	}


	private function setAccountDataAtNextcloud(array $userData): void {
		$token = $this->config->getSystemValue('ecloud_accounts_secret', '');
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");

		$data = [
			"token" => $token,
			"email" => $userData['mailAddress'],
			"quota" => $userData['quota'],
			"recoveryEmail" => $userData['recoveryMailAddress'],
			"hmeAlias" => $userData['hmeAlias'],
			"tosAccepted" => true,
			"uid" => $userData['mailAddress'] // Adjusted this assignment
		];
		$this->logger->error('### setAccountDataAtNextcloud: URL: '.$this->ecloudAccountsApiUrl . 'set_account_data');
		$this->logger->error('### setAccountDataAtNextcloud: data: '. json_encode($data));

		curl_setopt($ch, CURLOPT_URL, $this->ecloudAccountsApiUrl . 'set_account_data');
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		
		$output = curl_exec($ch);
		$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	
		if ($output === false) {
			$error = curl_error($ch);
			curl_close($ch);
			throw new Exception("CURL error: $error");
		}
	
		curl_close($ch);
		
		$output = json_decode($output, false);
		$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if ($statusCode !== 200) {
			$error = !empty($output->error) ? $output->error : 'Unknown error';
		
			if ($error === 'error_setting_recovery') {
				$this->handleError($statusCode, 'recovery', $userData['mailAddress'], $userData['recoveryMailAddress']);
			} elseif ($error === 'error_adding_hme_alias') {
				$this->handleError($statusCode, 'HME alias', $userData['mailAddress'], $userData['hmeAlias']);
			} else {
				$this->logger->error('Error creating account: ' . $error);
			}
		}
	}

	private function handleError(int $statusCode, string $errorType, string $email, string $value): void {
		$message = "Setting {$errorType} of user {$email} failed with status code: {$statusCode} ({$errorType}: {$value})" . PHP_EOL;
		$this->logger->error($message);
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
}
