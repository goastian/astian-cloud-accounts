<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Service;

use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUserSession;
use OCP\App\IAppManager;

class BetaUserService {
	private $config;
	private $appManager;
	protected $groupManager;
	private $userSession;

	public function __construct(
		IConfig $config,
		IGroupManager $groupManager,
		IUserSession $userSession,
		IAppManager $appManager
	) {
		$this->config = $config;
		$this->groupManager = $groupManager;
		$this->userSession = $userSession;
		$this->appManager = $appManager;
	}
		
	/**
	 * Method getBetaUserStatus used to get beta user status
	 *
	 * @return void
	 */
	public function getBetaUserStatus() {
		$uid = $this->userSession->getUser()->getUID();
		$betaGroupName = $this->config->getSystemValue("beta_group_name");
		if ($this->groupManager->isInGroup($uid, $betaGroupName)) {
			return true;
		}
		return false;
	}
	
	/**
	 * Method getBetaApps used to get beta apps
	 *
	 * @return void
	 */
	public function getBetaApps() {
		$betaGroupName = $this->config->getSystemValue("beta_group_name");
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
		return $betaApps;
	}
}
