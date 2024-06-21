<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Listeners;

use Exception;
use OCA\EcloudAccounts\AppInfo\Application;
use OCA\EcloudAccounts\Service\SSOService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\ILogger;
use OCP\ISession;
use OCP\User\Events\PasswordUpdatedEvent;

class PasswordUpdatedListener implements IEventListener {
	
	private SSOService $ssoService;

	private ILogger $logger;
	private ISession $session;

	public function __construct(SSOService $ssoService, ILogger $logger, ISession $session) {
		$this->ssoService = $ssoService;
		$this->logger = $logger;
		$this->session = $session;
	}

	public function handle(Event $event): void {
		if (!($event instanceof PasswordUpdatedEvent)) {
			return;
		}

		$user = $event->getUser();
		$username = $user->getUID();

		try {
			if (!$this->session->exists('is_oidc')) {
				return;
			}

			$this->ssoService->logout($username);
		} catch (Exception $e) {
			$this->logger->logException($e, ['app' => Application::APP_ID]);
		}
	}
}
