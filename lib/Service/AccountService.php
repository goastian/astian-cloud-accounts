<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Service;

use OCP\IConfig;
use Exception;

class AccountService {
	private $config;
	private $LDAPConnectionService;

	public function __construct(
		IConfig $config,
		LDAPConnectionService $LDAPConnectionService,
	) {
		$this->config = $config;
		$this->LDAPConnectionService = $LDAPConnectionService;
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
		$domain = $this->config->getSystemValue('main_domain', '');
		if ($entries['count'] > 0) {
			return false;
		} else {
			$newUserDN = "username=$username@$domain," . $base;
			$userClusterID = 'HEL01';
			$newUserEntry = [
				'mailAddress' => $username . '@' . $domain,
				'username' => $username,
				'usernameWithoutDomain' => $username,
				'userPassword' => $password,
				'displayName' => $displayname,
				'quota' => $this->LDAPConnectionService->getLdapQuota(),
				'mailAlternate' => '',
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
			return true;
		}
	}
}
