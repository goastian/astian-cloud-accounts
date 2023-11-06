<?php

/*
   * Copyright 2022 - Murena SAS - tous droits réservés
   */

namespace OCA\EcloudAccounts\Controller;

use OCA\EcloudAccounts\AppInfo\Application;
use OCA\EcloudAccounts\Service\UserService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;

class AccountController extends Controller {
	protected $appName;
	protected $request;
	private $userService;
	protected $l10nFactory;
	private $url;
	public function __construct(
		$AppName,
		IRequest $request,
		UserService $userService,
		IFactory $l10nFactory,
		IURLGenerator $url
	) {
		parent::__construct($AppName, $request);
		$this->appName = $AppName;
		$this->userService = $userService;
		$this->l10nFactory = $l10nFactory;
		$this->url = $url;
	}

	/**
	 * @NoAdminRequired
	 * @PublicPage
	 * @NoCSRFRequired
	 */
	public function index(string $lang = 'en') {
		$successIcon = $this->url->imagePath(Application::APP_ID, 'success.svg');
		return new TemplateResponse(
			Application::APP_ID,
			'signup',
			['appName' => Application::APP_ID, 'successIcon' => $successIcon],
			TemplateResponse::RENDER_AS_GUEST
		);
	}
	/**
	 * @NoAdminRequired
	 * @PublicPage
	 * @NoCSRFRequired
	 */
	public function create(string $displayname, string $email = '', string $username, string $password, string $language): DataResponse {
		$response = new DataResponse();

		try {
			$result = $this->userService->registerUser($displayname, $email, $username, $password, $language);
			$response->setStatus($result['statusCode']);
			$response->setData(['message' => $result['message']]);
		} catch (Exception $e) {
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
		try {
			if ($this->userService->userExists($username)) {
				$response->setStatus(409);
				return $response;
			}
			$response->setStatus(200);
		} catch (Exception $e) {
			$response->setStatus(500);
		}
		return $response;
	}
}
