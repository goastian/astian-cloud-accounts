<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Listeners;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\ILogger;
use OCP\Mail\IMailer;
use OCP\IConfig;
use OCA\EcloudAccounts\Service\UserService;
use OCP\Server;
use OCP\IUserSession;

class FirstLoginListener implements IEventListener {
	private $logger;
	private $mailer;
	private $config;
	private $userService;
	private $userSession;
	public function __construct(ILogger $logger, IMailer $mailer, IConfig $config, UserService $userService, IUserSession $userSession) {
		$this->logger = $logger;
		$this->mailer = $mailer;
		$this->config = $config;
		$this->userService = $userService;
		$this->userSession = $userSession;
	}

	public function handle(Event $event): void {
		$this->logger->error("FIRST TIME LOGIN LISTENER CALLED");
	}

	/**
	 * Summary of sendWelcomeEmail
	 */
	public static function sendWelcomeEmail() {
		/** @var self $listener */
		$listener = Server::get(self::class);
		$listener->sendEmail();
		return;
	}
	public function sendEmail() {
		$user = $this->userSession->getUser();
		$email = $user->getEMailAddress();
		$displayname = $user->getDisplayName();
		$this->userService->sendWelcomeEmail($displayname, $email);
		return;
	}
}
