<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Service;

use OC\User\Manager;
use OC_Util;
use OCP\Files\NotPermittedException;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\ILogger;
use OCP\IUserManager;

class FilesystemService {
	/** @var IUserManager */
	private $userManager;
	/** @var IConfig */
	private $config;
	/** @var ILogger */
	private $logger;
	/** @var IGroupManager */
	private $groupManager;
	/** @var Manager */
	private $manager;
	public function __construct(IUserManager $userManager, IConfig $config, ILogger $logger, Manager $manager, IGroupManager $groupManager) {
		$this->userManager = $userManager;
		$this->config = $config;
		$this->logger = $logger;
		$this->groupManager = $groupManager;
		$this->manager = $manager;
	}


	public function callSetupFS(string $user) {

		OC_Util::setupFS($user);
		//trigger creation of user home and /files folder
		$userFolder = \OC::$server->getUserFolder($user);

		try {
			// copy skeleton
			OC_Util::copySkeleton($user, $userFolder);
			return true;
		} catch (NotPermittedException $ex) {
			// read only uses
			$this->logger->error("Exception for user $user. Error: ".$ex->getMessage());
			return false;
		}
	}
	public function addUserInFilesEnabledGroup($username) {
		$user = $this->userManager->get($username);
		if (!$user) {
			return false;
		}
		$groupName = $this->config->getSystemValue("files_access_group_name");
		if (!$this->groupManager->groupExists($groupName)) {
			$this->logger->error("$groupName group not exist.");
			return false;
		}
		$group = $this->groupManager->get($groupName);
		$group->addUser($user);
		return true;
	}
	public function checkFilesGroupAccess($username) {
		$groupName = $this->config->getSystemValue("files_access_group_name");
		
		if (!$this->groupManager->groupExists($groupName)) {
			$this->logger->error("$groupName group not exist.");
			return false;
		}
		if ($this->groupManager->isInGroup($username, $groupName)) {
			return true;
		}
		return false;
	}

}
