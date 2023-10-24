<?php

/*
   * Copyright 2022 - Murena SAS - tous droits réservés
   */

namespace OCA\EcloudAccounts\Controller;

use OCA\EcloudAccounts\AppInfo\Application;
use OCA\EcloudAccounts\Service\AccountService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IRequest;
use OCP\L10N\IFactory;

class AccountController extends Controller {
	protected $appName;
	protected $request;
	// private ISession $session;
	private $accountService;
	protected $l10nFactory;

	private $config;
	public function __construct(
		$AppName,
		IRequest $request,
		AccountService $accountService,
		IConfig $config,
		IFactory $l10nFactory
	) {
		parent::__construct($AppName, $request);
		$this->appName = $AppName;
		$this->accountService = $accountService;
		$this->config = $config;
		$this->l10nFactory = $l10nFactory;
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
			$response->setStatus($result ? 200 : 409);
		} catch (Exception $e) {
			$response->setStatus(500);
		}
		return $response;
	}
	/**
	 * @NoAdminRequired
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 */
	public function checkUsernameAvailable(string $username) {
		$response = new DataResponse();
		try {
			$result = $this->accountService->checkUsernameAvailable($username);
			$response->setStatus($result ? 200 : 409);
		} catch (Exception $e) {
			$response->setStatus(500);
		}
		return $response;
	}
	/**
	 * @NoAdminRequired
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 */
	public function getLabels(string $language) {
		if (is_null($language)) {
			$language = 'en';
		}
		$l = $this->l10nFactory->get(Application::APP_ID, $language);
		$response = new DataResponse();
		$response->setStatus(200);
		$response->setData([
			'createMurenaAccount' => $l->t('Create Murena Account'),
			'displayName' => $l->t('Display name'),
			'userName' => $l->t('User name'),
			'enterPassword' => $l->t('Enter Password'),
			'humanVefication' => $l->t('Human Verification'),
			'recoveryEmail' => $l->t('Recovery Email')
		]);
		return $response;
	}
}
