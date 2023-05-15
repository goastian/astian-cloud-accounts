<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\IGroupManager;
use OCP\Settings\ISettings;
use OCP\IConfig;
use OCP\ILogger;
use OCP\AppFramework\Services\IInitialState;
use OCA\EcloudAccounts\Service\BetaUserService;

class BetaUserSetting implements ISettings {
	protected $groupManager;
	private $logger;
	private $initialState;
	private $config;
	private $appName;
	private $betaUserService;

	public function __construct(
		$appName,
		IGroupManager $groupManager,
		IConfig $config,
		ILogger $logger,
		IInitialState $initialState,
		BetaUserService $betaUserService
	) {
		$this->groupManager = $groupManager;
		$this->appName = $appName;
		$this->config = $config;
		$this->logger = $logger;
		$this->initialState = $initialState;
		$this->betaUserService = $betaUserService;
	}

	public function getForm(): TemplateResponse {
		$betaUserStatus = $this->betaUserService->getBetaUserStatus();
		$betaApps = $this->betaUserService->getBetaApps();
		$this->initialState->provideInitialState('is_beta_user', $betaUserStatus);
		$this->initialState->provideInitialState('beta_apps', $betaApps);
		return new TemplateResponse($this->appName, 'beta', ['appName' => $this->appName], '');
	}

	public function getSection(): ?string {
		$betaGroupName = $this->config->getSystemValue("beta_group_name");
		if (empty($betaGroupName)) {
			$this->logger->warning('Beta group name is not set in config.php', ['app' => 'ecloud-accounts']);
			return null;
		}
		$groupExists = $this->groupManager->groupExists($betaGroupName);
		if (! $groupExists) {
			$this->logger->warning('Beta group does not exist!', ['app' => 'ecloud-accounts']);
			return null;
		}
		return 'beta-user';
	}

	public function getPriority(): int {
		return 0;
	}
}
