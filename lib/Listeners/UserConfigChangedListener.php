<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Listeners;

use OCA\EcloudAccounts\Service\UserService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\ILogger;
use OCP\User\Events\UserConfigChangedEvent;

class UserConfigChangedListener implements IEventListener {
	private $logger;
	private $userService;

	public function __construct(ILogger $logger, UserService $userService) {
		$this->logger = $logger;
		$this->userService = $userService;
	}

	public function handle(Event $event): void {
		if (!($event instanceof UserConfigChangedEvent)) {
			return;
		}
		if ($event->getKey() === 'recovery-email') {
			$uid = $event->getUserId();
			$newRecoveryEmail = $event->getValue();
			$recoveryMailAddressAttribute = [
				'recoveryMailAddress' => $newRecoveryEmail
			];
			$this->userService->updateAttributesInLDAP($uid, $recoveryMailAddressAttribute);
		}
	}
}
