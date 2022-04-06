<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Listeners;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;
use OCP\User\Events\UserChangedEvent;
use OCA\EcloudAccounts\Db\MailboxMapper;

class UserChangedListener implements IEventListener
{

    private const QUOTA_FEATURE = 'quota';

    private $util;

    private $mailboxMapper;

    public function __construct(Util $util, MailboxMapper $mailboxMapper)
    {
        $this->util = $util;
        $this->mailboxMapper = $mailboxMapper;
    }

    public function handle(Event $event): void
    {
        if (!($event instanceof UserChangedEvent)) {
            return;
        }

        $feature = $event->getFeature();

        if ($feature !== self::QUOTA_FEATURE) {
            return;
        }

        $user = $event->getUser();
        $username = $user->getUID();
        $updatedQuota = $event->getValue();

        $quotaInBytes = (int) $this->util->computerFileSize($updatedQuota);
        $this->mailboxMapper->updateMailboxQuota($username, $quotaInBytes);
    }
}
