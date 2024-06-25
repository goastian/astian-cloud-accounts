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
use OCP\IUserSession;
use OCP\User\Events\PasswordUpdatedEvent;

class PasswordUpdatedListener implements IEventListener {
	
	private SSOService $ssoService;

	private ILogger $logger;
	private ISession $session;
	private IUserSession $userSession;

	public function __construct(SSOService $ssoService, ILogger $logger, ISession $session, IUserSession $userSession) {
		$this->ssoService = $ssoService;
		$this->logger = $logger;
		$this->session = $session;
		$this->userSession = $userSession;
	}

	public function handle(Event $event): void {
		if (!($event instanceof PasswordUpdatedEvent)) {
			return;
		}

		if (!$this->userSession->isLoggedIn() || !$this->session->exists('is_oidc')) {
			return;
		}

		$user = $event->getUser();
		$username = $user->getUID();

		try {
			$this->ssoService->logout($username);
		} catch (Exception $e) {
			$this->logger->logException($e, ['app' => Application::APP_ID]);
		}
	}
}
