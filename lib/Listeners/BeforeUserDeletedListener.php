<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Listeners;

use Exception;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IUserManager;
use OCP\User\Events\BeforeUserDeletedEvent;

class BeforeUserDeletedListener implements IEventListener
{
    private $logger;
    private $config;
    private $userManager;

    public function __construct(ILogger $logger, IConfig $config, IUserManager $userManager)
    {
        $this->logger = $logger;
        $this->config = $config;
        $this->userManager = $userManager;
    }


    public function handle(Event $event): void
    {
        if (!($event instanceof BeforeUserDeletedEvent)) {
            return;
        }

        $user = $event->getUser();
        $email = $user->getEMailAddress();
        $uid = $user->getUID();

        try {
            $conn = $this->getLDAPConnection($uid);
            $this->deleteAliasEntries($conn, $email);
            ldap_close($conn);
        }
        catch (Exception $e) {
            $this->logger->error('Error deleting aliases for user '. $uid . ' :' . $e->getMessage());
        }

    }

    private function deleteAliasEntries($conn, string $email)
    {
        $aliasBaseDn = getenv('LDAP_ALIASES_BASE_DN');
        $aliasDns = $this->getAliasEntries($conn, $aliasBaseDn, $email);
        foreach($aliasDns as $aliasDn) {
            $deleted = ldap_delete($conn, $aliasDn);
            if(!$deleted) {
                $this->logger->error('Deleting alias ' . $aliasDn . ' for email ' .  $email . ' failed');
            }
        }
    }

    private function getAliasEntries($conn, string $aliasBaseDn, string $email) : array
    {
        $filter = "(mailAddress=$email)";
        $aliasEntries = ldap_search($conn, $aliasBaseDn, $filter);
        if (!$aliasEntries) {
            return [];
        }

        $aliasEntries =  ldap_get_entries($conn, $aliasEntries);
        $aliasEntries = array_filter($aliasEntries, fn($entry) => is_array($entry));
        $aliasEntries = array_map(
            fn ($entry) => $entry['dn'],
            $aliasEntries
        );

        return $aliasEntries;
    }

    private function getLDAPConnection(string $uid)
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
}
