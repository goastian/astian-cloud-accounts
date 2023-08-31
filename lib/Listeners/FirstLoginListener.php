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

class FirstLoginListener implements IEventListener {
	private $logger;
	private $mailer;
	private $config;
	private $userService;
	public function __construct(ILogger $logger, IMailer $mailer, IConfig $config, UserService $userService) {
		$this->logger = $logger;
		$this->mailer = $mailer;
		$this->config = $config;
		$this->userService = $userService;
	}

	public function handle(Event $event): void {
		$this->logger->error("FIRST TIME LOGIN LISTENER CALLED");
	}

	/**
	 * Summary of sendWelcomeEmail
	 * @param string $displayname
	 * @param string $username
	 * @return bool
	 */
	public static function sendWelcomeEmail() {
		$displayname = 'Ronak Patel';
		$username = 'rptest46';
		/** @var self $listener */
		$listener = Server::get(self::class);
		$listener->sendEmail($displayname, $username);
		return true;
	}
	public function sendEmail($displayname, $username) {
		$this->userService->sendWelcomeEmail($displayname, $username);
	}
}
