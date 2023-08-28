<?php

/*
   * Copyright 2022 - Murena SAS - tous droits réservés
   */

namespace OCA\EcloudAccounts\Controller;

use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\AppFramework\Http\TemplateResponse;
use OCA\EcloudAccounts\AppInfo\Application;
use OCA\EcloudAccounts\Service\LDAPConnectionService;
use OCP\AppFramework\Http\DataResponse;

class AccountController extends Controller {
	protected $appName;
	protected $request;
	// private ISession $session;
	private $LDAPConnectionService;

	public function __construct(
		$AppName,
		IRequest $request,
		LDAPConnectionService $LDAPConnectionService,
	) {
		parent::__construct($AppName, $request);
		$this->appName = $AppName;
		$this->LDAPConnectionService = $LDAPConnectionService;
	}

	/**
	 * @NoAdminRequired
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 */
	public function index() {
		return new TemplateResponse(
			Application::APP_ID,
			'signup',
			['appName' => Application::APP_ID],
			TemplateResponse::RENDER_AS_GUEST
		);
	}
	/**
	 * @NoAdminRequired
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 */
	public function create(string $displayname, string $email, string $username, string $password) {
		$response = new DataResponse();
		try {
			$this->LDAPConnectionService->registerUser($displayname, $email, $username, $password);
			$response->setStatus(200);
			$msg = "Congratulations! You've successfully created a Murena account.";
		} catch (Exception $e) {
			$msg = $e->getMessage();
			$response->setStatus(403);
		}
		$response->setData(['message' => $msg]);
		return $response;
	}
}
