<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\IGroupManager;
use OCP\IUserSession;
use OCP\Settings\ISettings;
use OCP\Util;
use OCP\IConfig;
use OCP\App\IAppManager;
use OCP\ILogger;

class BetaUserSetting implements ISettings {
	/** @var IUserSession */
	private $userSession;

	/** @var IGroupManager */
	protected $groupManager;

	/** @var Util */
	protected $util;

	private $appName;

	private $appManager;

	/** @var ILogger */
	private $logger;

	public function __construct(
		$appName,
		IUserSession $userSession,
		IGroupManager $groupManager,
		Util $util,
		IConfig $config,
		IAppManager $appManager,
		ILogger $logger
	) {
		$this->userSession = $userSession;
		$this->groupManager = $groupManager;
		$this->appName = $appName;
		$this->config = $config;
		$this->util = $util;
		$this->appManager = $appManager;
		$this->logger = $logger;
	}

	public function getForm(): TemplateResponse {
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
