<?php

/*
   * Copyright 2022 - Murena SAS - tous droits réservés
   */

namespace OCA\EcloudAccounts\Controller;

use Exception;
use OCA\EcloudAccounts\AppInfo\Application;
use OCA\EcloudAccounts\Service\UserService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\L10N\IFactory;

class AccountController extends Controller {
	protected $appName;
	protected $request;
	private $userService;
	protected $l10nFactory;
	public function __construct(
		$AppName,
		IRequest $request,
		UserService $userService,
		IFactory $l10nFactory
	) {
		parent::__construct($AppName, $request);
		$this->appName = $AppName;
		$this->userService = $userService;
		$this->l10nFactory = $l10nFactory;
	}

	/**
	 * @NoAdminRequired
	 * @PublicPage
	 * @NoCSRFRequired
	 */
	public function index(string $lang = 'en') {
		return new TemplateResponse(
			Application::APP_ID,
			'signup',
			['appName' => Application::APP_ID, 'lang' => $lang],
			TemplateResponse::RENDER_AS_GUEST
		);
	}
	/**
	 * @NoAdminRequired
	 * @PublicPage
	 * @NoCSRFRequired
	 */
	public function create(string $displayname = '', string $recoveryEmail = '', string $username = '', string $password = '', string $language = ''): DataResponse {
		$response = new DataResponse();

		if ($displayname === '' || $username === '' || $password === '' || $language === '') {
			$response->setData(['message' => 'Some fields are missing.', 'success' => false]);
			$response->setStatus(500);
			return $response;
		}
		if (strlen($username) > 30 || strlen($displayname) > 30 || strlen($password) > 1024 ) {
			$response->setData(['message' => 'Input too large.', 'success' => false]);
			$response->setStatus(500);
			return $response;
		}
		
		try {
			$mainDomain = $this->userService->getMainDomain();
			$userEmail = $username.'@'.$mainDomain;

			$this->userService->registerUser($displayname, $recoveryEmail, $username, $userEmail, $password, $language);
			$this->userService->sendWelcomeEmail($displayname, $username, $userEmail, $language);
			
			$response->setStatus(200);
			$response->setData(['success' => true]);

		} catch (Exception $e) {
			$response->setData(['message' => $e->getMessage(), 'success' => false]);
			$response->setStatus(500);
		}
		return $response;
	}
	/**
	 * @NoAdminRequired
	 * @PublicPage
	 * @NoCSRFRequired
	 */
	public function checkUsernameAvailable(string $username) {
		$response = new DataResponse();
		if (!$this->userService->userExists($username)) {
			$response->setStatus(200);
		} else {
			$response->setStatus(400);
		}
		return $response;
	}
}
