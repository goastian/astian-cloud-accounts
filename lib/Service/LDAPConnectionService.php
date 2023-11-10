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
	private int $quotaInBytes = 1000;
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
		$quota = $this->config->getSystemValue('default_quota', '');
		if ($quota) {
			return intval($quota);
		} else {
			return $this->quotaInBytes;
		}
		
	}
}
