<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Listeners;

use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IRequest;
use OCP\Util;

class BeforeTemplateRenderedListener implements IEventListener {
	private $request;
	private $appName;
	private Util $util;

	public function __construct($appName, IRequest $request, Util $util) {
		$this->appName = $appName;
		$this->request = $request;
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
