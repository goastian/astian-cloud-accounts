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

	private const RECOVERY_EMAIL_FEATURE = 'recovery-email';

	private $util;

	private $logger;

	private $ldapConnectionService;

	private $mailboxMapper;

	private $userService;

	public function __construct(Util $util, LDAPConnectionService $LDAPConnectionService, ILogger $logger, MailboxMapper $mailboxMapper, UserService $userService) {
		$this->util = $util;
		$this->ldapConnectionService = $LDAPConnectionService;
		$this->mailboxMapper = $mailboxMapper;
		$this->logger = $logger;
		$this->userService = $userService;
	}

	public function handle(Event $event): void {
		if (!($event instanceof UserChangedEvent)) {
			return;
		}

		$feature = $event->getFeature();
		$user = $event->getUser();
		$username = $user->getUID();
		
		if ($feature === self::QUOTA_FEATURE) {
			$updatedQuota = $event->getValue();
			$quotaInBytes = (int) $this->util->computerFileSize($updatedQuota);
			$backend = $user->getBackend()->getBackendName();

			$this->updateQuota($username, $backend, $quotaInBytes);
		}

		if ($feature === self::RECOVERY_EMAIL_FEATURE) {
			$recoveryEmail = $event->getValue();
			$recoveryEmailAttribute = [
				'recoveryMailAddress' => $recoveryEmail
			];

			$this->userService->updateAttributesInLDAP($username, $recoveryEmailAttribute);
		}

		/** @var mixed $oldValue */
		$oldValue = $event->getOldValue();
		/** @var mixed $value */
		$value = $event->getValue();
		if ($feature === 'enabled') {
			if($value === true && $oldValue === false) {
				$this->logger->info('Enabling an user', ['event' => $event]);
				$userActiveAttributes = [
					'active' => 'TRUE',
					'mailActive' => 'TRUE',
				];
				$this->userService->updateAttributesInLDAP($username, $userActiveAttributes);
			}
			if($value === false && $oldValue === true) {
				$this->logger->info('Disabling an user', ['event' => $event]);
				$userActiveAttributes = [
					'active' => 'FALSE',
					'mailActive' => 'FALSE',
				];
				$this->userService->updateAttributesInLDAP($username, $userActiveAttributes);
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
				$this->userService->updateAttributesInLDAP($username, $quotaAttribute);
			}
		} catch (Exception $e) {
			$this->logger->error("Error setting quota for user $username " . $e->getMessage());
		}
	}
}
