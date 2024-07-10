<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Listeners;

use OCA\EcloudAccounts\Db\MailboxMapper;
use OCA\EcloudAccounts\Service\UserService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\ILogger;
use OCP\User\Events\BeforeUserCreatedEvent;
use OCP\Util;

class UserCreatedListener implements IEventListener {

	private $util;

	private $logger;

	private $mailboxMapper;

	private $userService;

	public function __construct(Util $util, ILogger $logger, MailboxMapper $mailboxMapper, UserService $userService) {
		$this->util = $util;
		$this->mailboxMapper = $mailboxMapper;
		$this->logger = $logger;
		$this->userService = $userService;
	}

	public function handle(Event $event): void {
		if (!($event instanceof BeforeUserCreatedEvent)) {
			return;
		}
		$this->logger->error("Before user created listener called");
		$uid = $event->getUid();
		$this->logger->error("UID: ".$uid);
		// $user = $event->getUser();
		// $username = $user->getUID();
		// $newValue = $event->getValue();
		
	}
}
