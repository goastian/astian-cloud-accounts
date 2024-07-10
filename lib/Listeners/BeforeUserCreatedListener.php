<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Listeners;

use InvalidArgumentException;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\BeforeUserCreatedEvent;
use OCP\User\Events\CreateUserEvent;

/**
 * Class BeforeUserCreatedListener
 *
 * @package OCA\EcloudAccounts\EventListener\User
 */
class BeforeUserCreatedListener implements IEventListener {

	public function __construct() {
	}

	/**
	 * @param Event $event
	 */
	public function handle(Event $event): void {
		if($event instanceof BeforeUserCreatedEvent || $event instanceof CreateUserEvent) {
			$this->preventCreationIfDeleted($event->getUid());
		}
	}

	/**
	 * @param string $userId
	 */
	protected function preventCreationIfDeleted(string $userId): void {
		throw new InvalidArgumentException("The username {$userId} is queued for deletion");
	}
}
