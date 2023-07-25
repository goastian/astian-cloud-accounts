<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Listeners;

use Exception;
use OCP\EventDispatcher\Event;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use \OCP\EventDispatcher\IEventListener;
use OCP\IUserSession;
use OCP\ISession;
use OCP\IConfig;
use OCP\IRequest;
use OCP\ILogger;
use OCP\App\IAppManager;

class BeforeTemplateRenderedListener implements IEventListener {
	private $userSession;
	private $request;
	private $appName;
	private $session;
	private $config;
	private $appManager;
	private ILogger $logger;

	private const SNAPPYMAIL_APP_ID = 'snappymail';
	private const SNAPPYMAIL_URL = '/apps/snappymail/';
	private const SNAPPYMAIL_AUTOLOGIN_PWD = '1';

	public function __construct($appName, ILogger $logger, IUserSession $userSession, IRequest $request, ISession $session, IConfig $config, IAppManager $appManager) {
		$this->appName = $appName;
		$this->logger = $logger;
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
		$accountId = $this->getAccountId();
		try {
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
		} catch(Exception $e) {
			$this->logger->logException($e);
			return;
		}
	}

	private function getAccountId() : string {
		$username = $this->userSession->getUser()->getUID();
		if ($this->config->getAppValue('snappymail', 'snappymail-autologin', false)) {
			return $username;
		}
		if ($this->config->getAppValue('snappymail', 'snappymail-autologin-with-email', false)) {
			return $this->config->getUserValue($username, 'settings', 'email', '');
		}
	}
}
