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

class BetaUserSetting implements ISettings {
	/** @var IUserSession */
	private $userSession;

	/** @var IGroupManager */
	protected $groupManager;

	/** @var Util */
	protected $util;

	private $appName;

	private $appManager;

	public function __construct(
		$appName,
		IUserSession $userSession,
		IGroupManager $groupManager,
		Util $util,
		IConfig $config,
		IAppManager $appManager
	) {
		$this->userSession = $userSession;
		$this->groupManager = $groupManager;
		$this->appName = $appName;
		$this->config = $config;
		$this->util = $util;
		$this->appManager = $appManager;
	}

	public function getForm(): TemplateResponse {
		$uid = $this->userSession->getUser()->getUID();
		$betaGroupName = $this->config->getSystemValue("beta_group_name");
		$groupExists = $this->groupManager->groupExists($betaGroupName);
		if (! $groupExists) {
			return new TemplateResponse($this->appName, 'no_group_exists', [], '');
		}
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
		return 'beta-user';
	}

	public function getPriority(): int {
		return 0;
	}
}
