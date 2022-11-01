<?php

/*
   * Copyright 2022 - Murena SAS - tous droits réservés
   */

namespace OCA\EcloudAccounts\Controller;

use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\IGroupManager;
use OCP\IUserSession;
use OCP\ILogger;
use OCP\Mail\IMailer;
use OCP\Util;
use OCP\Defaults;

class BetaUserController extends Controller
{
	protected $appName;
	protected $request;
	protected $config;
	protected $userManager;
	protected $groupManager;
	protected $mailer;
	private $defaults;
	private $userSession;

	const GROUP_NAME = "beta";
	const GITLAB_EMAIL_ADDRESS = "gitlab+e-backlog-177-ecr4cla0uf6bqrtdsy04zafd3-issue@e.email";

	public function __construct(
		$AppName,
		IRequest $request,
		IConfig $config,
		ILogger $logger,
		IUserManager $userManager,
		IGroupManager $groupManager,
		IUserSession $userSession,
		IMailer $mailer,
		Defaults $defaults
	) {
		parent::__construct($AppName, $request);
		$this->appName = $AppName;
		$this->request = $request;
		$this->config = $config;
		$this->logger = $logger;
		$this->userManager = $userManager;
		$this->userSession = $userSession;
		$this->groupManager = $groupManager;
		$this->mailer = $mailer;
		$this->defaults = $defaults;
	}

	/**
	 * addUserInGroup
	 *
	 * @return boolean
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
	 * @return boolean
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
	 * @return boolean
	 */
	public function submitIssue()
	{
		$msg = $_POST['description'];
		$template = $this->mailer->createEMailTemplate('betauser.SubmitGitIssue', []);
		$template->addHeader();
		$template->setSubject($_POST['title']);
		$template->addBodyText(htmlspecialchars($msg), $msg);

		$message = $this->mailer->createMessage();
		$message->setFrom([Util::getDefaultEmailAddress('noreply')]);
		$message->setTo([self::GITLAB_EMAIL_ADDRESS => 'GITLAB']);
		$message->useTemplate($template);
		
		$this->mailer->send($message);

		return true;
	}
}
