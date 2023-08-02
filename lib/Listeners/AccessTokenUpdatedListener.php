<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Listeners;

use OCP\EventDispatcher\Event;
use OCP\IUserSession;
use OCP\ISession;
use OCP\App\IAppManager;
use OCA\OIDCLogin\Events\AccessTokenUpdatedEvent;
use OCA\SnappyMail\Util\SnappyMailHelper;
use OCP\EventDispatcher\IEventListener;

class AccessTokenUpdatedListener implements IEventListener {
	private IUserSession $userSession;
	private ISession $session;
	private IAppManager $appManager;

	private const SNAPPYMAIL_APP_ID = 'snappymail';
	private const OIDC_LOGIN_APP_ID = 'oidc_login';


	public function __construct(IUserSession $userSession, ISession $session, IAppManager $appManager) {
		$this->userSession = $userSession;
		$this->session = $session;
		$this->appManager = $appManager;
	}

	public function handle(Event $event): void {
		if (!($event instanceof AccessTokenUpdatedEvent) || !$this->userSession->isLoggedIn() || !$this->session->exists('is_oidc')) {
			return;
		}

		// just-in-case checks(also maybe useful for selfhosters)
		if (!$this->appManager->isEnabledForUser(self::SNAPPYMAIL_APP_ID) || !$this->appManager->isEnabledForUser(self::OIDC_LOGIN_APP_ID)) {
			return;
		}
		
		$accessToken = $event->getAccessToken();
		$username = $this->userSession->getUser()->getUID();

		$this->session->set('snappymail-password', SnappyMailHelper::encodePassword($accessToken, $username));
	}
}
