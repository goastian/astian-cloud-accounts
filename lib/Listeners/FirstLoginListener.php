<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Listeners;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\ILogger;

class FirstLoginListener implements IEventListener {
	private $logger;
	public function __construct(ILogger $logger) {
		$this->logger = $logger;
	}

	public function handle(Event $event): void {
		$this->logger->error("FIRST TIME LOGIN LISTENER CALLED");
	}
}
