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
use OCP\ISession;
use OCP\L10N\IFactory;

class AccountController extends Controller {
	protected $appName;
	protected $request;
	private $userService;
	protected $l10nFactory;
	private $session;
	public function __construct(
		$AppName,
		IRequest $request,
		UserService $userService,
		IFactory $l10nFactory,
		ISession $session
	) {
		parent::__construct($AppName, $request);
		$this->appName = $AppName;
		$this->userService = $userService;
		$this->l10nFactory = $l10nFactory;
		$this->session = $session;
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
		if (strlen($username) > 30 || strlen($displayname) > 30 || strlen($password) > 1024) {
			$response->setData(['message' => 'Input too large.', 'success' => false]);
			$response->setStatus(500);
			return $response;
		}
		if(!$this->session->set('captcha_verified')) {
			$response->setData(['message' => 'Captcha is not verified!', 'success' => false]);
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
	public function checkUsernameAvailable(string $username) : DataResponse {
		$response = new DataResponse();
		if (!$this->userService->userExists($username)) {
			$response->setStatus(200);
		} else {
			$response->setStatus(400);
		}
		return $response;
	}

	/**
	 * @NoAdminRequired
	 * @PublicPage
	 * @NoCSRFRequired
	 */
	public function captcha() : DataResponse {
		$response = new DataResponse();
		$num1 = $this->userService->getRandomCharacter();
		$num2 = $this->userService->getRandomCharacter();
		$operator = $this->userService->getOperator();
		
		$this->session->set('num1', $num1);
		$this->session->set('num2', $num2);
		$this->session->set('operator', $operator);
		$this->session->set('captcha_verified', false);

		$response->setData(['num1' => $num1, 'num2' => $num2, 'operator' => $operator]);
		$response->setStatus(200);
		return $response;
	}
	/**
	 * @NoAdminRequired
	 * @PublicPage
	 * @NoCSRFRequired
	 */
	public function verifyCaptcha(string $humanverification = '') : DataResponse {
		$response = new DataResponse();
		
		$num1 = $this->session->get('num1');
		$num2 = $this->session->get('num2');
		$operator = $this->session->get('operator');

		if (!$humanverification || !$num1 || !$num2 || !$operator) {
			$response->setStatus(400);
			return $response;
		}

		if (!$this->userService->checkAnswer($num1, $num2, $operator, $humanverification)) {
			$this->session->set('captcha_verified', true);
			$response->setStatus(200);
		} else {
			$response->setStatus(400);
		}
		return $response;
	}

}
