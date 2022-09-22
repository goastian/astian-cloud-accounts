<?php

namespace OCA\EcloudAccounts\Listeners;

use OCP\EventDispatcher\Event;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use \OCP\EventDispatcher\IEventListener;
use OCP\Util;
use OCP\IUserSession;
use OCP\IRequest;

class BeforeTemplateRenderedListener implements IEventListener
{
    private $util;
    private $userSession;
    private $request;
    private $appName;

    public function __construct($appName, Util $util, IUserSession $userSession, IRequest $request)
    {
        $this->appName = $appName;
        $this->util = $util;
        $this->userSession = $userSession;
        $this->request = $request;
    }
    public function handle(Event $event): void
    {
        if (!($event instanceof BeforeTemplateRenderedEvent)) {
            return;
        }
        if ($this->userSession->isLoggedIn() && $this->request->getPathInfo() === '/settings/user/drop_account') {
            $this->util->addScript($this->appName, $this->appName . '-delete-account-listeners');
        }
    }
}
