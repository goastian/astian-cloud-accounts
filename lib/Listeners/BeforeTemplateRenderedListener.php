<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Listeners;

use OCP\EventDispatcher\Event;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use \OCP\EventDispatcher\IEventListener;
use OCP\IUserSession;
use OCP\ISession;
use OCP\IConfig;
use OCP\IRequest;
use OCP\App\IAppManager;

class BeforeTemplateRenderedListener implements IEventListener {
	private $userSession;
	private $request;
	private $appName;
	private $session;
	private $config;
	private $appManager;

	private const SNAPPYMAIL_APP_ID = 'snappymail';
	private const SNAPPYMAIL_URL = '/apps/snappymail/';

	public function __construct($appName, IUserSession $userSession, IRequest $request, ISession $session, IConfig $config, IAppManager $appManager) {
		$this->appName = $appName;
		$this->userSession = $userSession;
		$this->request = $request;
		$this->session = $session;
		$this->config = $config;
		$this->appManager = $appManager;
	}

	public function handle(Event $event): void {
		if (!($event instanceof BeforeTemplateRenderedEvent)) {
			return;
		}
		if ($this->userSession->isLoggedIn() && $this->appManager->isEnabledForUser(self::SNAPPYMAIL_APP_ID) && strpos($this->request->getPathInfo(), self::SNAPPYMAIL_URL) !== false) {
			$this->autoLoginWebmail();
		}
	}


	private function autoLoginWebmail() {
		$isOidcLogin = $this->session->get('is_oidc');
		if (!$isOidcLogin) {
			return;
		}
		$email = $this->getEmail();
		$actions = \RainLoop\Api::Actions();
		
		if (empty($email) || $actions->getMainAccountFromToken(false)) {
			return;
		}
		
		$password = $this->session->get('oidc_access_token');
		if(empty($password)) {
			return;
		}

		$account = $actions->LoginProcess($email, $password, false);
		if ($account) {
			$actions->Plugins()->RunHook('login.success', array($account));
			$actions->SetAuthToken($account);
		}
	}

	private function getEmail() : string {
		$username = $this->userSession->getUser()->getUID();
		if ($this->config->getAppValue('snappymail', 'snappymail-autologin', false)) {
			return $username;
		}
		if ($this->config->getAppValue('snappymail', 'snappymail-autologin-with-email', false)) {
			return $this->config->getUserValue($username, 'settings', 'email', '');
		}
	}
}
