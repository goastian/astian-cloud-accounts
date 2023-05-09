<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\IGroupManager;
use OCP\Settings\ISettings;
use OCP\Util;
use OCP\IConfig;
use OCP\ILogger;
use OCP\AppFramework\Services\IInitialState;
use OCA\EcloudAccounts\Service\BetaUserService;

class BetaUserSetting implements ISettings {
	protected $groupManager;
	protected $util;
	private $logger;
	private $initialState;
	private $config;
	private $appName;
	private $betaUserService;

	public function __construct(
		$appName,
		IGroupManager $groupManager,
		Util $util,
		IConfig $config,
		ILogger $logger,
		IInitialState $initialState,
		BetaUserService $betaUserService
	) {
		$this->groupManager = $groupManager;
		$this->appName = $appName;
		$this->config = $config;
		$this->util = $util;
		$this->logger = $logger;
		$this->initialState = $initialState;
		$this->betaUserService = $betaUserService;
	}

	public function getForm(): TemplateResponse {
		$betaDetails = $this->betaUserService->getBetaUserStatusAndApps();
		$this->initialState->provideInitialState('is_beta_user', $betaDetails['isBetaUser']);
		$this->initialState->provideInitialState('beta_apps', $betaDetails['betaApps']);
		$this->util->addScript($this->appName, $this->appName . '-beta-user-setting');
		return new TemplateResponse($this->appName, 'beta');
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
