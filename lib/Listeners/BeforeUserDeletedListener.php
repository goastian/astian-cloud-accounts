<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Listeners;

use Exception;
use OCA\EcloudAccounts\Exception\DeletingUserWithActiveSubscriptionException;
use OCA\EcloudAccounts\Service\LDAPConnectionService;
use OCA\EcloudAccounts\Service\ShopAccountService;
use OCA\EcloudAccounts\Service\UserService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IConfig;
use OCP\ILogger;
use OCP\User\Events\BeforeUserDeletedEvent;

class BeforeUserDeletedListener implements IEventListener {
	private $logger;
	private $config;
	private $LDAPConnectionService;
	private $shopAccountService;
	private $userService;

	public function __construct(ILogger $logger, IConfig $config, LDAPConnectionService $LDAPConnectionService, UserService $userService, ShopAccountService $shopAccountService) {
		$this->logger = $logger;
		$this->config = $config;
		$this->LDAPConnectionService = $LDAPConnectionService;
		$this->shopAccountService = $shopAccountService;
		$this->userService = $userService;
	}


	public function handle(Event $event): void {
		if (!($event instanceof BeforeUserDeletedEvent)) {
			return;
		}

		$user = $event->getUser();
		$email = $user->getEMailAddress();
		$uid = $user->getUID();

		// Handle shop accounts first; none of the other things should happen before the active subscription check
		$this->handleShopAccounts($uid, $email);

		$isUserOnLDAP = $this->LDAPConnectionService->isUserOnLDAPBackend($user);

		$this->logger->info("PostDelete user {user}", array('user' => $uid));
		$this->userService->ecloudDelete($email);

		try {
			if ($this->LDAPConnectionService->isLDAPEnabled() && $isUserOnLDAP) {
				$conn = $this->LDAPConnectionService->getLDAPConnection();
				$this->deleteAliasEntries($conn, $email);
				$this->LDAPConnectionService->closeLDAPConnection($conn);
			}
		} catch (Exception $e) {
			$this->logger->error('Error deleting aliases for user '. $uid . ' :' . $e->getMessage());
		}
	}

	private function handleShopAccounts(string $uid, string $email) {
		$deleteShopAccount = $this->shopAccountService->getShopDeletePreference($uid);
		$shopUsers = $this->shopAccountService->getUsers($email);

		if (!empty($shopUsers)) {
			foreach ($shopUsers as $shopUser) {
				if ($shopUser['has_active_subscription']) {
					throw new DeletingUserWithActiveSubscriptionException('Cannot delete user ' . $uid . ' as an active subscription exists');
				}

				if (!isset($shopUser['id'])) {
					continue;
				}

				if ($deleteShopAccount) {
					$this->shopAccountService->deleteUser($shopUser['shop_url'], $shopUser['id']);
				} else {
					$newEmail = $this->shopAccountService->getShopEmailPostDeletePreference($uid);
					$newEmail = $this->shopAccountService->updateUserEmailAndEmptyOIDC($shopUser['shop_url'], $shopUser['id'], $newEmail);
				}
			}
		}
	}

	private function deleteAliasEntries($conn, string $email) {
		$aliasBaseDn = getenv('LDAP_ALIASES_BASE_DN');
		$aliasDns = $this->getAliasEntries($conn, $aliasBaseDn, $email);
		foreach ($aliasDns as $aliasDn) {
			$deleted = ldap_delete($conn, $aliasDn);
			if (!$deleted) {
				$this->logger->error('Deleting alias ' . $aliasDn . ' for email ' .  $email . ' failed');
			}
		}
	}

	private function getAliasEntries($conn, string $aliasBaseDn, string $email) : array {
		$filter = "(mailAddress=$email)";
		$aliasEntries = ldap_search($conn, $aliasBaseDn, $filter);
		if (!$aliasEntries) {
			return [];
		}

		$aliasEntries = ldap_get_entries($conn, $aliasEntries);
		$aliasEntries = array_filter($aliasEntries, fn ($entry) => is_array($entry));
		$aliasEntries = array_map(
			fn ($entry) => $entry['dn'],
			$aliasEntries
		);

		return $aliasEntries;
	}
}
