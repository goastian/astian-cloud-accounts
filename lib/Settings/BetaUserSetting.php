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
		$uid = $this->userSession->getUser()->getUID();
		$betaGroupName = $this->config->getSystemValue("beta_group_name");
		$this->util->addScript($this->appName, $this->appName . '-beta-user-setting');
		$group = $this->groupManager->get($betaGroupName);
		$betaGroupApps = $this->appManager->getEnabledAppsForGroup($group);
		$betaApps = [];
		foreach ($betaGroupApps as $app) {
			$appEnabledGroups = $this->config->getAppValue($app, 'enabled', 'no');
			if (str_contains($appEnabledGroups, $betaGroupName)) {
				$info = $this->appManager->getAppInfo($app);
				$betaApps[] = $info['name'];
			}
		}
		if ($this->groupManager->isInGroup($uid, $betaGroupName)) {
			return new TemplateResponse($this->appName, 'opt_out_beta_user', ['betaApps' => $betaApps], '');
		}
		return new TemplateResponse($this->appName, 'become_beta_user', ['betaApps' => $betaApps], '');
	}

	public function getSection(): ?string {
		$betaGroupName = $this->config->getSystemValue("beta_group_name");
		if (empty($betaGroupName)) {
			$this->logger->error('Beta group name is not configured.', ['app' => 'ecloud-accounts']);
			return null;
		}
		$groupExists = $this->groupManager->groupExists($betaGroupName);
		if (! $groupExists) {
			$this->logger->error('Beta group is not available.', ['app' => 'ecloud-accounts']);
			return null;
		}
		return 'beta-user';
	}

	public function getPriority(): int {
		return 0;
	}
}
