<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Listeners;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\ILogger;
use OCA\EcloudAccounts\Service\UserService;
use OCP\Server;

class FirstLoginListener implements IEventListener {
	private $logger;
	private $userService;
	public function __construct(ILogger $logger, UserService $userService) {
		$this->logger = $logger;
		$this->userService = $userService;
	}
	public function handle(Event $event): void {
		$this->logger->info("FIRST TIME LOGIN LISTENER CALLED");
	}
	public static function firstLogin($userId) {
		/** @var self $listener */
		$listener = Server::get(self::class);
		$listener->handleFirstLogin($userId);
		return;
	}
	public function handleFirstLogin($userId) {
		$this->userService->sendWelcomeEmail($userId);
	}
}
