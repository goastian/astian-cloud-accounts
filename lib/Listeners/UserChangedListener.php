<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Listeners;

use Exception;
use OCA\EcloudAccounts\Db\MailboxMapper;
use OCA\EcloudAccounts\Service\LDAPConnectionService;
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

	public function __construct(Util $util, LDAPConnectionService $LDAPConnectionService, ILogger $logger, MailboxMapper $mailboxMapper) {
		$this->util = $util;
		$this->ldapConnectionService = $LDAPConnectionService;
		$this->mailboxMapper = $mailboxMapper;
		$this->logger = $logger;
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

			$this->updateAttributesInLDAP($username, $recoveryEmailAttribute);
		}

		/** @var mixed $oldValue */
		$oldValue = $event->getOldValue();
		/** @var mixed $value */
		$value = $event->getValue();
		if ($feature === 'enabled') {
			if($value === true && $oldValue === false) {
				$this->logger->info('Enabling an user', ['event' => $event]);
				$userEnableAttributes = [
					'active' => 'TRUE',
					'mailActive' => 'TRUE',
				];
				$this->updateAttributesInLDAP($username, $userEnableAttributes);
			}
			if($value === false && $oldValue === true) {
				$this->logger->info('Disabling an user', ['event' => $event]);
				$userEnableAttributes = [
					'active' => 'FALSE',
					'mailActive' => 'FALSE',
				];
				$this->updateAttributesInLDAP($username, $userEnableAttributes);
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
				$this->updateAttributesInLDAP($username, $quotaAttribute);
			}
		} catch (Exception $e) {
			$this->logger->error("Error setting quota for user $username " . $e->getMessage());
		}
	}
	
	private function updateAttributesInLDAP(string $username, array $attributes) {
		if ($this->ldapConnectionService->isLDAPEnabled()) {
			$conn = $this->ldapConnectionService->getLDAPConnection();
			$userDn = $this->ldapConnectionService->username2dn($username);
			
			if (!ldap_modify($conn, $userDn, $attributes)) {
				throw new Exception('Could not modify user entry at LDAP server!');
			}
			$this->ldapConnectionService->closeLDAPConnection($conn);
		}
	}
}
