<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Listeners;

use Exception;
use OCA\EcloudAccounts\Db\TwoFactorMapper;
use OCA\EcloudAccounts\Service\SSOService;
use OCA\TwoFactorTOTP\Event\StateChanged;
use OCP\App\IAppManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\ILogger;

class TwoFactorStateChangedListener implements IEventListener {
	private IAppManager $appManager;
	private TwoFactorMapper $twoFactorMapper;
	private SSOService $ssoService;
	private ILogger $logger;

	private const TWOFACTOR_APP_ID = 'twofactor_totp';


	public function __construct(IAppManager $appManager, SSOService $ssoService, TwoFactorMapper $twoFactorMapper, ILogger $logger) {
		$this->appManager = $appManager;
		$this->ssoService = $ssoService;
		$this->twoFactorMapper = $twoFactorMapper;
		$this->logger = $logger;
	}


	public function handle(Event $event): void {
		if (!($event instanceof StateChanged) || !$this->appManager->isEnabledForUser(self::TWOFACTOR_APP_ID) || !$this->ssoService->shouldSync2FA()) {
			return;
		}

		$user = $event->getUser();
		$username = $user->getUID();
		try {
			// When state change event is fired by user disabling 2FA, delete existing 2FA credentials and return
			// i.e. disable 2FA for user at SSO
			if (!$event->isEnabled()) {
				$this->ssoService->deleteCredentials($username);
				return;
			}

			$secret = $this->twoFactorMapper->getSecret($username);
			$this->ssoService->migrateCredential($username, $secret);
		} catch (Exception $e) {
			$stateText = $event->isEnabled() ? 'new secret enabled' : 'disabled';
			$this->logger->error('Error updating secret state(' . $stateText  .') for user: ' . $username . ': ' . $e->getMessage());
		}
	}
}
