<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Listeners;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\ILogger;
use OCA\EcloudAccounts\Service\UserService;
use OCP\IUserSession;

class FirstLoginListener implements IEventListener {
	private $logger;
	private $userService;
	private $userSession;
	private $emailService;

	public function __construct(
		ILogger $logger,
		IUserSession $userSession,
		UserService $userService
	) {
		$this->logger = $logger;
		$this->userService = $userService;
		$this->userSession = $userSession;
	}

	public function handle(Event $event): void {
		if ($event instanceof FirstLoginEvent) {
			$this->logger->info("First time login detected for user: " . $event->getUserId());

			// Send a welcome email to the user
			$user = $this->userSession->getUser();
			$email = $user->getEMailAddress();
			$displayName = $user->getDisplayName();

			try {
				$this->userService->sendWelcomeEmail($displayName, $email);
			} catch (\Exception $e) {
				// Handle email sending errors gracefully
				$this->logger->error("Failed to send welcome email: " . $e->getMessage());
			}
		}
	}
}
