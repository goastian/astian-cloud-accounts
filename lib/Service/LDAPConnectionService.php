<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Service;

use Exception;
use OCP\IUserManager;

class LDAPConnectionService
{

    /** @var IUserManager */
    private $userManager;

    private $configuration;

    private $ldapEnabled;

    private $access;

    public function __construct(IUserManager $userManager)
    {
        $this->userManager = $userManager;
        $this->getConfigurationFromBackend();
    }


    private function getConfigurationFromBackend()
    {
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

    public function isLDAPEnabled() : bool {
        return $this->ldapEnabled;
    }

    public function username2dn(string $username) {
        return $this->access->username2dn($username);
    }
    

    public function getUserBaseDn() : string
    {
        if (isset($this->configuration['ldap_base_users'])) {
            return $this->configuration['ldap_base_users'];
        }
        throw new Exception('User Base Dn not set!');
    }

    public function getLDAPConnection()
    {
        if(!$this->ldapEnabled) {
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

    public function closeLDAPConnection($conn) : void
    {
        ldap_close($conn);
    }

    public function getLDAPAccess()
    {
        if (!$this->access) {
            throw new Exception('Access not defined!');
        }
        return $this->access;
    }
}
