<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Listeners;

use OCP\EventDispatcher\Event;
use OCP\ISession;
use OCP\App\IAppManager;
use OCP\User\Events\PostLoginEvent;
use OCA\SnappyMail\Util\SnappyMailHelper;
use OCP\EventDispatcher\IEventListener;

class PostLoginEventListener implements IEventListener {
	private ISession $session;
	private IAppManager $appManager;

	private const SNAPPYMAIL_APP_ID = 'snappymail';
	private const OIDC_LOGIN_APP_ID = 'oidc_login';
	private const ACCESS_TOKEN_KEY = 'oidc_access_token';


	public function __construct(ISession $session, IAppManager $appManager) {
		$this->session = $session;
		$this->appManager = $appManager;
	}

	public function handle(Event $event): void {
		if (!($event instanceof PostLoginEvent) || !$this->session->exists('is_oidc')) {
			return;
		}

		// just-in-case checks(also maybe useful for selfhosters)
		if (!$this->appManager->isEnabledForUser(self::SNAPPYMAIL_APP_ID) || !$this->appManager->isEnabledForUser(self::OIDC_LOGIN_APP_ID)) {
			return;
		}
		
		$accessToken = (string) $this->session->get(self::ACCESS_TOKEN_KEY);
		if (!$accessToken) {
			return;
		}

		$username = $event->getUser()->getUID();
		$this->session->set('snappymail-password', SnappyMailHelper::encodePassword($accessToken, $username));
	}
}
