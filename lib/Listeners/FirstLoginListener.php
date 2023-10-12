<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Listeners;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

class FirstLoginListener implements IEventListener {
	public function __construct() {
	}

	public function handle(Event $event): void {
	}
}
