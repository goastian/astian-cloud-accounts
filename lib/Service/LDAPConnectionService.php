<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Service;

use Exception;
use OCA\User_LDAP\Configuration;
use OCA\User_LDAP\Helper;
use OCP\IConfig;
use OCP\IUserManager;

class LDAPConnectionService {
	/** @var IUserManager */
	private $userManager;
	private $configuration;
	private $ldapEnabled;
	private $access;
	private $ldapConfig;
	private IConfig $config;

	public function __construct(IUserManager $userManager, Helper $ldapBackendHelper, IConfig $config) {
		$this->userManager = $userManager;
		$this->getConfigurationFromBackend();
		$ldapConfigPrefixes = $ldapBackendHelper->getServerConfigurationPrefixes(true);
		$prefix = array_shift($ldapConfigPrefixes);
		$this->ldapConfig = new Configuration($prefix);
		$this->config = $config;
	}


	private function getConfigurationFromBackend() {
		// We don't actually need user id to get access from backend
		$uid = '';
		$backends = $this->userManager->getBackends();
		foreach ($backends as $backend) {
			if ($backend->getBackendName() === 'LDAP') {
				$this->access = $backend->getLDAPAccess($uid);
				$connection = $this->access->getConnection();
				$configuration = $connection->getConfiguration();

				if ($configuration['ldap_configuration_active']) {
					$this->ldapEnabled = true;
					$this->configuration = $configuration;
					break;
				}
			}
		}
	}

	public function isUserOnLDAPBackend($user) {
		$backend = $user->getBackend();
		return $backend->getBackendName() === 'LDAP';
	}

	public function isLDAPEnabled(): bool {
		return $this->ldapEnabled;
	}

	public function username2dn(string $username) {
		return $this->access->username2dn($username);
	}
	public function getLDAPConnection() {
		if (!$this->ldapEnabled) {
			throw new Exception('LDAP backend is not enabled');
		}

		$adminDn = $this->configuration['ldap_dn'];
		$adminPassword = $this->configuration['ldap_agent_password'];
		$host = $this->configuration['ldap_host'];
		$port = intval($this->configuration['ldap_port']);

		$conn = ldap_connect($host, $port);
		ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_bind($conn, $adminDn, $adminPassword);

		if (!$conn) {
			throw new Exception('Could not connect to LDAP server!');
		}
		return $conn;
	}

	public function closeLDAPConnection($conn): void {
		ldap_close($conn);
	}

	public function getLDAPAccess() {
		if (!$this->access) {
			throw new Exception('Access not defined!');
		}
		return $this->access;
	}

	public function getLDAPBaseUsers(): array {
		$bases = $this->ldapConfig->ldapBaseUsers;
		if (empty($bases)) {
			$bases = $this->ldapConfig->ldapBase;
		}
		return $bases;
	}
	public function getDisplayNameAttribute(): string {
		return $this->ldapConfig->ldapUserDisplayName;
	}
	public function getLdapQuota() {
		return $this->config->getSystemValue('default_quota', '1024');
	}
	public function updateAttributesInLDAP(string $username, array $attributes): void {
		if (!$this->isLDAPEnabled()) {
			return;
		}

		$conn = $this->getLDAPConnection();
		$userDn = $this->username2dn($username);

		if ($userDn === false) {
			throw new Exception('Could not find DN for username: ' . $username);
		}

		if (!ldap_modify($conn, $userDn, $attributes)) {
			throw new Exception('Could not modify user ' . $username . ' entry at LDAP server. Attributes: ' . print_r($attributes, true));
		}

		$this->closeLDAPConnection($conn);
	}
	public function getUsersCreatedAfter(string $date): array {
		if (!$this->isLDAPEnabled()) {
			throw new Exception('LDAP backend is not enabled');
		}
	
		// Convert the provided date to LDAP Generalized Time format: YYYYMMDDHHMMSSZ
		$formattedDate = (new \DateTime($date))->format('YmdHis') . 'Z';
	
		$conn = $this->getLDAPConnection();
		$baseDn = implode(',', $this->getLDAPBaseUsers());
		$filter = sprintf('(createTimestamp>=%s)', $formattedDate);
	
		$searchResult = ldap_search($conn, $baseDn, $filter, ['username']);
		if (!$searchResult) {
			$this->closeLDAPConnection($conn);
			throw new Exception('LDAP search failed for createTimestamp after: ' . $date);
		}
	
		$entries = ldap_get_entries($conn, $searchResult);
		$this->closeLDAPConnection($conn);
	
		$users = [];
		if ($entries['count'] > 0) {
			for ($i = 0; $i < $entries['count']; $i++) {
				$users[] = [
					'username' => $entries[$i]['username'][0] ?? null,
				];
			}
		}
	
		return $users;
	}

}
