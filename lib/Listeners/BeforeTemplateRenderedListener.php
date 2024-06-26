<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Listeners;

use OCP\App\IAppManager;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
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


	public function __construct($appName, IUserSession $userSession, IRequest $request, ISession $session, IConfig $config, IAppManager $appManager, Util $util) {
		$this->appName = $appName;
		$this->userSession = $userSession;
		$this->request = $request;
		$this->session = $session;
		$this->config = $config;
		$this->appManager = $appManager;
		$this->util = $util;
	}

	public function handle(Event $event): void {
		if (!($event instanceof BeforeTemplateRenderedEvent)) {
			return;
		}

		$pathInfo = $this->request->getPathInfo();

		if (strpos($pathInfo, '/apps/ecloud-accounts/accounts') !== false) {
			$this->util->addStyle($this->appName, $this->appName . '-userregistration');
		}

	}
}
