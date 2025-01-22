<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Service;

use OC\User\Manager;
use OC_Util;
use OCP\EventDispatcher\GenericEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\NotPermittedException;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\ILogger;
use OCP\IUser;
use OCP\IUserManager;
use OCP\User\Events\UserFirstTimeLoggedInEvent;

class FSService {
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


	public function callSetupFS(string $user): void {

		$this->logger->error("OC_Util::setupFS called for user: $user");
		OC_Util::setupFS($user);
		$this->logger->error("OC_Util::setupFS Done for user: $user");
		
		//trigger creation of user home and /files folder
		$userFolder = \OC::$server->getUserFolder($user);

		try {
			$this->logger->error("getUserFolder for user: $user");
			// copy skeleton
			OC_Util::copySkeleton($user, $userFolder);
		} catch (NotPermittedException $ex) {
			// read only uses
			$this->logger->error("NotPermittedException exception for user: $user");
		}
		$userDetails = $this->manager->get($user);

		// trigger any other initialization
		\OC::$server->get(IEventDispatcher::class)->dispatch(IUser::class . '::firstLogin', new GenericEvent($userDetails));
		\OC::$server->get(IEventDispatcher::class)->dispatchTyped(new UserFirstTimeLoggedInEvent($userDetails));
	}
	public function addUserInGroup($username) {
		$user = $this->userManager->get($username);
		if (!$user) {
			$this->logger->error("addUserInGroup for user: $username . User not found.");
			return false;
		}
		$groupName = $this->config->getSystemValue("temporary_group_name");
		if (!$this->groupManager->groupExists($groupName)) {
			$this->logger->error("addUserInGroup for user: $username . Group not found.");
			return false;
		}
		$group = $this->groupManager->get($groupName);
		$group->addUser($user);
		$this->logger->error("addUserInGroup for user: $username . SUCCESS.");
		return true;
	}
}
