<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Service;

use OCP\IUserManager;

class LDAPConnectionService
{

    /** @var IUserManager */
    private $userManager;

    public function __construct(IUserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    public function getLDAPConnection(string $uid)
    {
        $backends = $this->userManager->getBackends();
        $conn = null;
        foreach ($backends as $backend) {
            if ($backend->getBackendName() === 'LDAP') {
                $access = $backend->getLDAPAccess($uid);
                $connection = $access->getConnection();
                $configuration = $connection->getConfiguration();

                if (!$configuration['ldap_configuration_active']) {
                    continue;
                }

                $adminDn = $configuration['ldap_dn'];
                $adminPassword = $configuration['ldap_agent_password'];
                $host = $configuration['ldap_host'];
                $port = intval($configuration['ldap_port']);

                $conn = ldap_connect($host, $port);
                ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
                ldap_bind($conn, $adminDn, $adminPassword);

                if (!$conn) {
                    continue;
                }

                return $conn;
            }
        }

        if (!$conn) {
            throw new Exception('Could not connect to LDAP server to delete aliases for user ' . $uid);
        }
    }

    public function closeLDAPConnection($conn) : void {
        ldap_close($conn);
    }
}
