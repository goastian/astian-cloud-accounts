<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Listeners;

use InvalidArgumentException;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\ILogger;
use OCP\User\Events\BeforeUserCreatedEvent;
use OCP\User\Events\CreateUserEvent;

/**
 * Class BeforeUserCreatedListener
 *
 * @package OCA\EcloudAccounts\EventListener\User
 */
class BeforeUserCreatedListener implements IEventListener {
	private $logger;
	public function __construct(ILogger $logger) {
		$this->logger = $logger;
	}

	/**
	 * @param Event $event
	 */
	public function handle(Event $event): void {
		$this->logger->info('=====> INSIDE BeforeUserCreatedListener');
		if($event instanceof BeforeUserCreatedEvent || $event instanceof CreateUserEvent) {
			$this->preventRecoveryEmailifNotValid($event->getUid());
		}
	}

	/**
	 * @param string $userId
	 */
	protected function preventRecoveryEmailifNotValid(string $userId): void {
		$this->logger->info('=====> INSIDE preventRecoveryEmailifNotValid');
		throw new InvalidArgumentException("The username {$userId} is queued for deletion");
	}
}
