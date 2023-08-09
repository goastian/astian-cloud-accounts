<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Service;

use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUserSession;
use OCP\App\IAppManager;

class AccountService {
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
}
