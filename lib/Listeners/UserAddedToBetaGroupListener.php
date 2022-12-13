<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Listeners;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\UserAddedEvent;
use OCP\IUser;
use OCP\IConfig;

class UserAddedToBetaGroupListener implements IEventListener {
	private $config;

	public function __construct(IConfig $config) {
		$this->config = $config;
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

		$this->migrateRainloopData($user);
	}

	private function migrateRainloopData(IUser $user): void {
		$username = $user->getUID();
		$dir_data = substr($username, 0, 2);
		$email = $user->getEMailAddress();
		$dir = rtrim(trim($this->config->getSystemValue('datadirectory', '')), '\\/');
		$dir_snappy = $dir . '/appdata_snappymail/_data_/_default_/storage/cfg/' . $dir_data . "/$email/";
		$dir_rainloop = $dir . '/rainloop-storage/_data_/_default_/storage/cfg/' . $dir_data . "/$email";
		
		if (file_exists($dir_snappy)) {
			\OC::$server->getLogger()->debug("$dir_snappy folder already exists");
			return;
		}
		mkdir($dir_snappy, 0755, true);
		shell_exec("cp -ar $dir_rainloop/* $dir_snappy");
		return;
	}
}
