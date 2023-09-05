<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Listeners;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\ILogger;
use OCA\EcloudAccounts\Service\UserService;
use OCP\Server;
use OCP\IUserSession;

class FirstLoginListener implements IEventListener {
	private $logger;
	private $userService;
	private $userSession;
	public function __construct(ILogger $logger, UserService $userService, IUserSession $userSession) {
		$this->logger = $logger;
		$this->userService = $userService;
		$this->userSession = $userSession;
	}
	public function handle(Event $event): void {
		$this->logger->info("FIRST TIME LOGIN LISTENER CALLED");
	}
	public static function firstLogin() {
		/** @var self $listener */
		$listener = Server::get(self::class);
		$listener->sendWelcomeEmail();
		return;
	}
	public function sendWelcomeEmail() {
		$user = $this->userSession->getUser();
		$email = $user->getEMailAddress();
		$displayname = $user->getDisplayName();
		$this->logger->info("SENDING EMAIL TO ".$email. "(".$displayname.")");
		$this->userService->sendWelcomeEmail($displayname, $email);
		return;
	}
}
