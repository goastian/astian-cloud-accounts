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

	public function __construct(
        IConfig $config
	) {
        $this->config = $config;
	}

	public function handle(Event $event): void {
		if (!($event instanceof UserAddedEvent)) {
			return;
		}

		$user = $event->getUser();
		$group = $event->getGroup();

        $betaGroup = $this->config->getSystemValue("beta_group_name");

        if ($group->GID() !== $betaGroup) return;
		
        $this->migrateRainloopData($user);

	}

    private function migrateRainloopData(IUser $user): void {

        $dir = \rtrim(\trim(\OC::$server->getSystemConfig()->getValue('datadirectory', '')), '\\/');
		$dir_snappy = $dir . '/appdata_snappymail/';
		$dir_rainloop = $dir . '/rainloop-storage/_data_/_default_/storage/cfg/';
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($dir_rainloop, \RecursiveDirectoryIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::SELF_FIRST
		);
		foreach ($iterator as $item) {
			$target = $dir_snappy . $iterator->getSubPathname();
			if (\preg_match('@/plugins/([^/])@', $target, $match)) {
				$rainloop_plugins[$match[1]] = $match[1];
			} else if (!\strpos($target, '/cache/')) {
				if ($item->isDir()) {
					\is_dir($target) || \mkdir($target, 0755, true);
				} else if (\file_exists($target)) {
					$result[] = "skipped: {$target}";
				} else {
					\copy($item, $target);
					$result[] = "copied : {$target}";
				}
			}
		}
    }
}
