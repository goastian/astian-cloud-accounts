<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\IGroupManager;
use OCP\IUserSession;
use OCP\Settings\ISettings;
use OCP\Util;
use OCP\IConfig;

class BetaUserSetting implements ISettings {
	/** @var IUserSession */
	private $userSession;

	/** @var IGroupManager */
	protected $groupManager;

	private $appName;

	public function __construct(
		$appName,
		IUserSession $userSession,
		IGroupManager $groupManager,
		IConfig $config
	) {
		$this->userSession = $userSession;
		$this->groupManager = $groupManager;
		$this->appName = $appName;
		$this->config = $config;
	}

	public function getForm(): TemplateResponse {
		$uid = $this->userSession->getUser()->getUID();
		$isBeta = 0;

		$groupExists = $this->groupManager->groupExists($this->config->getSystemValue("beta_group_name"));
		if ($groupExists) {
			$isBeta = $this->groupManager->isInGroup($uid, $this->config->getSystemValue("beta_group_name"));
		}
		Util::addScript($this->appName, 'ecloud-accounts-beta-user-setting');
		$parameters = ['isBeta' => $isBeta, 'groupExists' => $groupExists];
		return new TemplateResponse($this->appName, 'beta_user_setting', $parameters, '');
	}

	public function getSection(): ?string {
		return 'beta-user';
	}

	public function getPriority(): int {
		return 0;
	}
}
