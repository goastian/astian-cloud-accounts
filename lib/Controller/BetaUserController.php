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
use OCP\AppFramework\Http\Response;
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
	 * @CORS
	 * @PublicPage
	 * @NoCSRFRequired
	 */
	public function addRemoveUserInGroup()
	{
		$user =  $this->userSession->getUser();
		$action = isset($_POST['beta']) ? $_POST['beta'] : '';

		if (!$this->groupManager->groupExists(self::GROUP_NAME)) {
			return false;
		}
		$group = $this->groupManager->get(self::GROUP_NAME);
		if ($action == 'register') {
			$group->addUser($user);
			return true;
		}
		if ($action == 'deregister') {
			$group->removeUser($user);
			return true;
		}
		return false;
	}
}
