<?php

/*
   * Copyright 2019 - ECORP SAS - tous droits réservés
   */

namespace OCA\EcloudAccounts\Controller;

use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\IGroupManager;
use OCP\IUserSession;
use OCP\ILogger;

class BetaUserController extends Controller
{
	protected $appName;
	protected $request;
	protected $config;
	protected $userManager;
	protected $groupManager;
	private $userSession;
	const GROUP_NAME = "beta";

	public function __construct(
		$AppName,
		IRequest $request,
		IConfig $config,
		ILogger $logger,
		IUserManager $userManager,
		IGroupManager $groupManager,
		IUserSession $userSession
	) {
		parent::__construct($AppName, $request);
		$this->appName = $AppName;
		$this->request = $request;
		$this->config = $config;
		$this->logger = $logger;
		$this->userManager = $userManager;
		$this->userSession = $userSession;
		$this->groupManager = $groupManager;
	}

	/**
	 * addUserInGroup
	 *
	 * @return void
	 */
	public function addUserInGroup()
	{
		$user =  $this->userSession->getUser();
		if (!$this->groupManager->groupExists(self::GROUP_NAME)) {
			return false;
		}
		$group = $this->groupManager->get(self::GROUP_NAME);
		$group->addUser($user);
		return true;
	}
	/**
	 * removeUserInGroup
	 *
	 * @return void
	 */
	public function removeUserInGroup()
	{
		$user =  $this->userSession->getUser();
		if (!$this->groupManager->groupExists(self::GROUP_NAME)) {
			return false;
		}
		$group = $this->groupManager->get(self::GROUP_NAME);
		$group->removeUser($user);
		return true;
	}

	/**
	 * submitIssue
	 *
	 * @return void
	 */
	public function submitIssue()
	{
		$msg = $_POST['description'];
		$msg = wordwrap($msg, 70);
		mail("gitlab+e-backlog-177-ecr4cla0uf6bqrtdsy04zafd3-issue@e.email", $_POST['title'], $msg);
		return true;
	}
}
