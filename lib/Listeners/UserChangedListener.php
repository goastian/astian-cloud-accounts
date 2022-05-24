<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Listeners;

use Exception;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;
use OCP\ILogger;
use OCP\User\Events\UserChangedEvent;
use OCA\EcloudAccounts\Db\MailboxMapper;
use OCA\EcloudAccounts\Service\LDAPConnectionService;

class UserChangedListener implements IEventListener
{
    private const QUOTA_FEATURE = 'quota';

    private $util;

    private $logger;

    private $ldapConnectionService;

    private $mailboxMapper;

    public function __construct(Util $util, LDAPConnectionService $LDAPConnectionService, ILogger $logger, MailboxMapper $mailboxMapper)
    {
        $this->util = $util;
        $this->ldapConnectionService = $LDAPConnectionService;
        $this->mailboxMapper = $mailboxMapper;
        $this->logger = $logger;
    }

    public function handle(Event $event): void
    {
        if (!($event instanceof UserChangedEvent)) {
            return;
        }

        $feature = $event->getFeature();

        if ($feature !== self::QUOTA_FEATURE) {
            return;
        }
        $user = $event->getUser();
        $username = $user->getUID();
        $updatedQuota = $event->getValue();
        $quotaInBytes = (int) $this->util->computerFileSize($updatedQuota);
        $backend = $user->getBackend()->getBackendName();

        try {
            if ($backend === 'SQL raw') {
                $this->mailboxMapper->updateMailboxQuota($username, $quotaInBytes);
            }
            if ($backend === 'LDAP') {
                $this->updateQuotaInLDAP($username, $quotaInBytes);
            }
        } catch (Exception $e) {
            $this->logger->error("Error setting quota for user $username " . $e->getMessage());
        }
    }

    private function updateQuotaInLDAP(string $username, int $quota)
    {
        if ($this->ldapConnectionService->isLDAPEnabled()) {
            $conn = $this->ldapConnectionService->getLDAPConnection();
            $userDn = $this->ldapConnectionService->username2dn($username);

            $entry = [
              'quota' => $quota
            ];

            if (!ldap_modify($conn, $userDn, $entry)) {
                throw new Exception('Could not modify user entry at LDAP server!');
            }
            $this->ldapConnectionService->closeLDAPConnection($conn);
        }
    }
}
