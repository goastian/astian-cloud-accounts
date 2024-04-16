<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Listeners;

use OCA\OIDCLogin\Events\AccessTokenUpdatedEvent;
use OCA\SnappyMail\Util\SnappyMailHelper;
use OCP\App\IAppManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\ISession;
use OCP\IUserSession;

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
		\OC::$server->getLogger()->error("call1");
		if (!($event instanceof AccessTokenUpdatedEvent) || !$this->userSession->isLoggedIn() || !$this->session->exists('is_oidc')) {
			return;
		}
		\OC::$server->getLogger()->error("call2");
		// just-in-case checks(also maybe useful for selfhosters)
		if (!$this->appManager->isEnabledForUser(self::SNAPPYMAIL_APP_ID) || !$this->appManager->isEnabledForUser(self::OIDC_LOGIN_APP_ID)) {
			return;
		}
		\OC::$server->getLogger()->error("call3");
		$accessToken = $event->getAccessToken();
		if (!$accessToken) {
			return;
		}
		\OC::$server->getLogger()->error($accessToken);

		$username = $this->userSession->getUser()->getUID();
		\OC::$server->getLogger()->error($username);
		
		//\OC::$server->getSession()['snappymail-nc-uid'] = $username;
		//OC::$server->getSession()['snappymail-passphrase'] = SnappyMailHelper::encodePassword($accessToken, $username);
		//$this->session->set('snappymail-nc-uid', SnappyMailHelper::encodePassword($accessToken, $username));
		$this->session->set('snappymail-nc-uid', $username);
		$this->session->set('snappymail-passphrase', SnappyMailHelper::encodePassword($accessToken, $username));
		$this->session->set('oidc_access_token', $accessToken);
		$this->session->set('is_oidc', 1);
		$this->session->set('snappymail-password', SnappyMailHelper::encodePassword($accessToken, $username));
	}
}
