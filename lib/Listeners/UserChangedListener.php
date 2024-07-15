<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Listeners;

use Exception;
use OCA\EcloudAccounts\Db\MailboxMapper;
use OCA\EcloudAccounts\Service\LDAPConnectionService;
use OCA\EcloudAccounts\Service\UserService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\ILogger;
use OCP\User\Events\UserChangedEvent;
use OCP\Util;

class UserChangedListener implements IEventListener {
	private const QUOTA_FEATURE = 'quota';
	private const ENABLED_FEATURE = 'enabled';

	private $util;

	private $logger;

	private $mailboxMapper;

	private $userService;
	private $LDAPConnectionService;

	public function __construct(Util $util, ILogger $logger, MailboxMapper $mailboxMapper, UserService $userService, LDAPConnectionService $LDAPConnectionService) {
		$this->util = $util;
		$this->mailboxMapper = $mailboxMapper;
		$this->logger = $logger;
		$this->userService = $userService;
		$this->LDAPConnectionService = $LDAPConnectionService;
	}

	public function handle(Event $event): void {
		if (!($event instanceof UserChangedEvent)) {
			return;
		}

		$feature = $event->getFeature();
		$user = $event->getUser();
		$username = $user->getUID();
		$newValue = $event->getValue();
		
		if ($feature === self::QUOTA_FEATURE) {
			$updatedQuota = $event->getValue();
			$quotaInBytes = (int) $this->util->computerFileSize($updatedQuota);
			$backend = $user->getBackend()->getBackendName();

			$this->updateQuota($username, $backend, $quotaInBytes);
		}

		if ($feature === self::ENABLED_FEATURE) {
			try {
				$this->userService->mapActiveAttributesInLDAP($username, $newValue);
			} catch (Exception $e) {
				$this->logger->logException('Failed to update LDAP attributes for user: ' . $username, ['exception' => $e]);
			}
		}
	}

	private function updateQuota(string $username, string $backend, int $quotaInBytes) {
		try {
			if ($backend === 'SQL raw') {
				$this->mailboxMapper->updateMailboxQuota($username, $quotaInBytes);
			}
			if ($backend === 'LDAP') {
				$quotaAttribute = [
					'quota' => $quotaInBytes
				];
				$this->LDAPConnectionService->updateAttributesInLDAP($username, $quotaAttribute);
			}
		} catch (Exception $e) {
			$this->logger->error("Error setting quota for user $username " . $e->getMessage());
		}
	}
}
