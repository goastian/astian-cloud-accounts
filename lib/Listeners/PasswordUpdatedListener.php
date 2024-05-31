<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Listeners;

use Exception;
use OCA\EcloudAccounts\Service\SSOService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\ILogger;
use OCP\User\Events\PasswordUpdatedEvent;

class PasswordUpdatedListener implements IEventListener {
	
	private SSOService $ssoService;

	private $logger;

	public function __construct(SSOService $ssoService, ILogger $logger) {
		$this->ssoService = $ssoService;
		$this->logger = $logger;
	}

	public function handle(Event $event): void {
		if (!($event instanceof PasswordUpdatedEvent)) {
			return;
		}

		$user = $event->getUser();
		$username = $user->getUID();

		try {
			$this->ssoService->logout($username);
		} catch (Exception $e) {
			$this->logger->logException('Failed to logout from ssoService for user: ' . $username, ['exception' => $e]);
		}
	}
}
