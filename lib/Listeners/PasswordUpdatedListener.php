<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Listeners;

use OCA\EcloudAccounts\Service\SSOService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\PasswordUpdatedEvent;

class PasswordUpdatedListener implements IEventListener {
	private SSOService $ssoService;

	public function __construct(SSOService $ssoService) {
		$this->ssoService = $ssoService;
	}

	public function handle(Event $event): void {
		if (!($event instanceof PasswordUpdatedEvent)) {
			return;
		}

		$user = $event->getUser();
		$username = $user->getUID();

		$this->ssoService->logout($username);
	}
}
