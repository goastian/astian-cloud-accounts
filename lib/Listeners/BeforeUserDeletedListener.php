<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Listeners;

use Exception;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\ILogger;
use OCP\User\Events\BeforeUserDeletedEvent;
use OCA\EcloudAccounts\Service\LDAPConnectionService;

class BeforeUserDeletedListener implements IEventListener
{
    private $logger;
    private $LDAPConnectionService;

    public function __construct(ILogger $logger, LDAPConnectionService $LDAPConnectionService)
    {
        $this->logger = $logger;
        $this->LDAPConnectionService = $LDAPConnectionService;
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
            $conn = $this->LDAPConnectionService->getLDAPConnection($uid);
            $this->deleteAliasEntries($conn, $email);
            $this->LDAPConnectionService->closeLDAPConnection($conn);
        } catch (Exception $e) {
            $this->logger->error('Error deleting aliases for user '. $uid . ' :' . $e->getMessage());
        }
    }

    private function deleteAliasEntries($conn, string $email)
    {
        $aliasBaseDn = getenv('LDAP_ALIASES_BASE_DN');
        $aliasDns = $this->getAliasEntries($conn, $aliasBaseDn, $email);
        foreach ($aliasDns as $aliasDn) {
            $deleted = ldap_delete($conn, $aliasDn);
            if (!$deleted) {
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
        $aliasEntries = array_filter($aliasEntries, fn ($entry) => is_array($entry));
        $aliasEntries = array_map(
            fn ($entry) => $entry['dn'],
            $aliasEntries
        );

        return $aliasEntries;
    }
}
