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

class BetaUserController extends Controller {
	protected $appName;
	protected $request;
	protected $config;
	protected $userManager;
	protected $groupManager;
	protected $mailer;
	private $userSession;

	public function __construct(
		$AppName,
		IRequest $request,
		IConfig $config,
		ILogger $logger,
		IUserManager $userManager,
		IGroupManager $groupManager,
		IUserSession $userSession,
		IMailer $mailer
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
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function addUserInGroup() {
		$user = $this->userSession->getUser();
		$groupName = $this->config->getSystemValue("beta_group_name");
		if (!$this->groupManager->groupExists($groupName)) {
			return false;
		}
		$group = $this->groupManager->get($groupName);
		$group->addUser($user);
		return true;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function removeUserInGroup() {
		$user = $this->userSession->getUser();
		$groupName = $this->config->getSystemValue("beta_group_name");
		if (!$this->groupManager->groupExists($groupName)) {
			return false;
		}
		$group = $this->groupManager->get($groupName);
		$group->removeUser($user);
		return true;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function submitIssue(string $title, string $description) {
		$user = $this->userSession->getUser();
		$fromEmail = $user->getEMailAddress();
		$fromName = $user->getDisplayName();


		$template = $this->mailer->createEMailTemplate('betauser.SubmitGitIssue', []);
		$template->addHeader();
		$template->setSubject($title);
		$template->addBodyText(htmlspecialchars($description), $description);

		$message = $this->mailer->createMessage();
		$message->setFrom([Util::getDefaultEmailAddress('noreply')]);
		$message->setReplyTo([$fromEmail => $fromName]);
		$message->setTo([$this->config->getSystemValue("beta_gitlab_email_id")]);
		$message->useTemplate($template);

		$this->mailer->send($message);

		return true;
	}
}
