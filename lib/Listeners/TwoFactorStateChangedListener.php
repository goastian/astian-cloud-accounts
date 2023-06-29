<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Listeners;

use Exception;
use OCA\TwoFactorTOTP\Event\StateChanged;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\App\IAppManager;
use OCA\EcloudAccounts\Db\SSOMapper;
use OCA\EcloudAccounts\Db\TwoFactorMapper;
use OCP\ILogger;

class TwoFactorStateChangedListener implements IEventListener {
	private IAppManager $appManager;
	private TwoFactorMapper $twoFactorMapper;
	private SSOMapper $ssoMapper;
	private ILogger $logger;

	private const TWOFACTOR_APP_ID = 'twofactor_totp';


	public function __construct(IAppManager $appManager, SSOMapper $ssoMapper, TwoFactorMapper $twoFactorMapper, ILogger $logger) {
		$this->appManager = $appManager;
		$this->ssoMapper = $ssoMapper;
		$this->twoFactorMapper = $twoFactorMapper;
		$this->logger = $logger;
	}	


	public function handle(Event $event): void {
		if (!($event instanceof StateChanged) || !$this->appManager->isEnabledForUser(self::TWOFACTOR_APP_ID)) {
			return;
		}

		$user = $event->getUser();
		$username = $user->getUID();
		try {
			// Delete old secret as 2FA secret state has changed
			$this->ssoMapper->deleteSecret($username);
		
			// When state change event is fired by user disabling 2FA, just return
			if (!$event->isEnabled()) {
				return;
			}

			$secret = $this->twoFactorMapper->getSecret($username);
			$this->ssoMapper->migrateSecret($username, $secret);
		}
		catch (Exception $e) {
			$stateText = $event->isEnabled() ? 'new secret enabled' : 'disabled';
			$this->logger->error('Error updating secret state(' . $stateText  .') for user: ' . $username . ': ' . $e->getMessage());
		}
	}
	
}
