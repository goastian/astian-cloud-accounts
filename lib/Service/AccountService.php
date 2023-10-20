<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Service;

use Exception;
use OCP\IConfig;

class AccountService {
	private $config;
	private $LDAPConnectionService;
	private $userService;

	public function __construct(
		IConfig $config,
		LDAPConnectionService $LDAPConnectionService,
		UserService $userService
	) {
		$this->config = $config;
		$this->LDAPConnectionService = $LDAPConnectionService;
		$this->userService = $userService;
	}
	public function registerUser(string $displayname, string $email, string $username, string $password) {
		$connection = $this->LDAPConnectionService->getLDAPConnection();
		$base = $this->LDAPConnectionService->getLDAPBaseUsers()[0];
	
		// Check if the username already exists
		$filter = "(usernameWithoutDomain=$username)";
		$searchResult = ldap_search($connection, $base, $filter);
	
		if (!$searchResult) {
			throw new Exception("Error while searching Murena username.");
		}
	
		$entries = ldap_get_entries($connection, $searchResult);
		if ($entries['count'] > 0) {
			return false;
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
		if($email != '') {
			$this->userService->sendWelcomeEmail($username, $email);
		}
		return true;
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
