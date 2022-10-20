<?php

  /*
   * Copyright 2019 - ECORP SAS - tous droits réservés
   */

  namespace OCA\EcloudAccounts\Controller;

  use OCP\AppFramework\ApiController;
  use OCP\IRequest;
  use OCP\IConfig;
  use OCP\IUserManager;
  use OCP\IGroupManager;
  use OCP\IUserSession;
  use OCP\AppFramework\Http\Response;
  use OCP\ILogger;

  class UpdateBetaUserController extends ApiController
  {
      protected $appName;
      protected $request;
      protected $config;
      protected $userManager;
      protected $groupManager;
	 private $userSession;

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

      public function addUserToGroup()
      {
		$response = new Response();
		$gid = 'beta';
		$uid =  $this->userSession->getUser()->getUID();
		$user = $this->userManager->get($uid);
		
		$group = null;
		$groupExists = $this->groupManager->groupExists($gid);
		
		if (!$groupExists) {
			$group = $this->groupManager->createGroup($gid);
		} else {
			$group = $this->groupManager->get($gid);
		}
		
		$inGroup = $this->groupManager->isInGroup($uid, $gid);
        $group->addUser($user);
        return true;

	}

      /**
        * @CORS
        * @PublicPage
        * @NoCSRFRequired
        */

      public function removeUserFromGroup()
      {
		$response = new Response();
		$gid = 'beta';
		$uid =  $this->userSession->getUser()->getUID();
		$user = $this->userManager->get($uid);
		
		$group = null;
		$groupExists = $this->groupManager->groupExists($gid);
		
		if (!$groupExists) {
			$group = $this->groupManager->createGroup($gid);
		} else {
			$group = $this->groupManager->get($gid);
		}
		
		$inGroup = $this->groupManager->isInGroup($uid, $gid);
        $group->removeUser($user);
        return true;
      }
  }
