<?php

/*
   * Copyright 2022 - Murena SAS - tous droits réservés
   */

namespace OCA\EcloudAccounts\Controller;

use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\AppFramework\Http\TemplateResponse;
use OCA\EcloudAccounts\AppInfo\Application;
use OCA\EcloudAccounts\Service\AccountService;
use OCP\AppFramework\Http\DataResponse;

class AccountController extends Controller {
	protected $appName;
	protected $request;
	// private ISession $session;
	private $accountService;


	public function __construct(
		$AppName,
		IRequest $request,
		AccountService $accountService,
	) {
		parent::__construct($AppName, $request);
		$this->appName = $AppName;
		$this->accountService = $accountService;
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
			$result = $this->accountService->registerUser($displayname, $email, $username, $password);
			$result = $this->accountService->sendWelcomeEmail($displayname, $username);
			$response->setStatus($result ? 200 : 409);
		} catch (Exception $e) {
			$response->setStatus(500);
		}
		return $response;
	}
}
