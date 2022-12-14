<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Listeners;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\UserAddedEvent;
use OCP\IUser;
use OCP\IConfig;
use OCP\ILogger;

class UserAddedToBetaGroupListener implements IEventListener {
	private $config;
	private $logger;

	public function __construct(IConfig $config, ILogger $logger) {
		$this->config = $config;
		$this->logger = $logger;
	}

	public function handle(Event $event): void {
		if (!($event instanceof UserAddedEvent)) {
			return;
		}

		$user = $event->getUser();
		$group = $event->getGroup();

		$betaGroup = $this->config->getSystemValue("beta_group_name");

		if ($group->getGID() !== $betaGroup) {
			return;
		}
		try {
			$this->migrateRainloopData($user);
		} catch (Exception $e) {
			$errorMessage = 'Error while migrating user rainloop data to snappymail';
			$this->logger->error($errorMessage . ': ' . $e->getMessage());
		}
	}

	private function migrateRainloopData(IUser $user): void {
		$username = $user->getUID();
		$userDir = substr($username, 0, 2);
		$email = $user->getEMailAddress();
		$dataDir = rtrim(trim($this->config->getSystemValue('datadirectory', '')), '\\/');
		$snappyDir = "$dataDir/appdata_snappymail/_data_/_default_/storage/cfg/$userDir/$email/";
		$rainloopDir = "$dataDir/rainloop-storage/_data_/_default_/storage/cfg/$userDir/$email";
		
		if (file_exists($snappyDir)) {
			$this->logger->debug("$snappyDir already exists");
			return;
		}
		mkdir($snappyDir, 0755, true);
		shell_exec("cp -ar $rainloopDir/* $snappyDir");
		return;
	}
}
