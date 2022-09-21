<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Listeners;

use Exception;
use Curl;
use OCA\EcloudAccounts\AppInfo\Application;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\ILogger;
use OCP\IConfig;
use OCP\User\Events\BeforeUserDeletedEvent;
use OCA\EcloudAccounts\Service\LDAPConnectionService;
use OCA\EcloudAccounts\Service\ShopAccountService;
use OCA\EcloudAccounts\Service\UserService;


class BeforeUserDeletedListener implements IEventListener
{
    private $logger;
    private $config;
    private $LDAPConnectionService;
    private $shopAccountService;
    private $userService;

    public function __construct(ILogger $logger, IConfig $config, LDAPConnectionService $LDAPConnectionService, UserService $userService, ShopAccountService $shopAccountService)
    {
        $this->logger = $logger;
        $this->config = $config;
        $this->LDAPConnectionService = $LDAPConnectionService;
        $this->shopAccountService = $shopAccountService;
        $this->userService = $userService;
    }


    public function handle(Event $event): void
    {
        if (!($event instanceof BeforeUserDeletedEvent)) {
            return;
        }

        $user = $event->getUser();
        $email = $user->getEMailAddress();
        $uid = $user->getUID();
        $isUserOnLDAP = $this->LDAPConnectionService->isUserOnLDAPBackend($user);

        $this->logger->info("PostDelete user {user}", array('user' => $uid));
        $this->userService->ecloudDelete(
            $uid,
            $this->config->getSystemValue('e_welcome_domain'),
            $this->config->getSystemValue('e_welcome_secret'),
            $email,
            $isUserOnLDAP
        );

        try {
            if ($this->LDAPConnectionService->isLDAPEnabled() && $isUserOnLDAP) {
                $conn = $this->LDAPConnectionService->getLDAPConnection();
                $this->deleteAliasEntries($conn, $email);
                $this->LDAPConnectionService->closeLDAPConnection($conn);
            }
        } catch (Exception $e) {
            $this->logger->error('Error deleting aliases for user '. $uid . ' :' . $e->getMessage());
        }

        $this->shopAccountService->deleteUserFromShop($email);
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
