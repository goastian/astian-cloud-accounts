<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Listeners;

use OCP\App\IAppManager;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IConfig;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUserSession;
use OCP\Util;

class BeforeTemplateRenderedListener implements IEventListener {
	private $userSession;
	private $request;
	private $appName;
	private $session;
	private $config;
	private $appManager;
	private Util $util;

	private const SNAPPYMAIL_APP_ID = 'snappymail';
	private const SNAPPYMAIL_URL = '/apps/snappymail/';
	private const SNAPPYMAIL_AUTOLOGIN_PWD = '1';
	private IInitialState $initialState;
	private $userId;
	public function __construct($appName, IUserSession $userSession, IRequest $request, ISession $session, IConfig $config, IAppManager $appManager, Util $util, $userId, IInitialState $initialState) {
		$this->appName = $appName;
		$this->userSession = $userSession;
		$this->request = $request;
		$this->session = $session;
		$this->config = $config;
		$this->appManager = $appManager;
		$this->util = $util;
		$this->initialState = $initialState;
		$this->userId = $userId;
	}

	public function handle(Event $event): void {
		if (!($event instanceof BeforeTemplateRenderedEvent)) {
			return;
		}
		if ($this->userSession->isLoggedIn() && $this->appManager->isEnabledForUser(self::SNAPPYMAIL_APP_ID) && strpos($this->request->getPathInfo(), self::SNAPPYMAIL_URL) !== false) {
			$this->autoLoginWebmail();
		}
		$pathInfo = $this->request->getPathInfo();

		if (strpos($pathInfo, '/apps/ecloud-accounts/accounts') !== false) {
			$this->util->addStyle($this->appName, $this->appName . '-userregistration');
		}
		if (($event->getResponse()->getRenderAs() === TemplateResponse::RENDER_AS_USER) && $event->isLoggedIn()) {
			// $recoveryEmail = $this->recoveryEmailService->getRecoveryEmail($this->userId);
			// if ($recoveryEmail === '') {
			// $unverifiedRecoveryEmail = $this->recoveryEmailService->getUnverifiedRecoveryEmail($this->userId);
			$userLocation = 'USA';
			$this->initialState->provideInitialState('userLocation', $userLocation);
			$this->util->addStyle($this->appName, 'business-banner');
			$this->util->addScript($this->appName, $this->appName . '-business-banner');
			// }
		}
	}


	private function autoLoginWebmail() {
		$isOidcLogin = $this->session->get('is_oidc');
		if (!$isOidcLogin) {
			return;
		}
		$accountId = $this->getAccountId();
		$actions = \RainLoop\Api::Actions();

		if (empty($accountId) || $actions->getMainAccountFromToken(false)) {
			return;
		}

		// Just send over '1' as password to trigger login as the plugin will set the correct access token
		$password = self::SNAPPYMAIL_AUTOLOGIN_PWD; // As we cannot pass by reference to LoginProcess
		$account = $actions->LoginProcess($accountId, $password, false);
		if ($account) {
			$actions->Plugins()->RunHook('login.success', array($account));
			$actions->SetAuthToken($account);
		}
	}

	private function getAccountId(): string {
		$username = $this->userSession->getUser()->getUID();
		if ($this->config->getAppValue('snappymail', 'snappymail-autologin', false)) {
			return $username;
		}
		if ($this->config->getAppValue('snappymail', 'snappymail-autologin-with-email', false)) {
			return $this->config->getUserValue($username, 'settings', 'email', '');
		}
	}
}
