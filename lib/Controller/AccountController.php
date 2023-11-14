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

		$inputData = [
			'username' => ['value' => $username, 'maxLength' => 30],
			'displayname' => ['value' => $displayname, 'maxLength' => 30],
			'password' => ['value' => $password, 'maxLength' => 1024],
		];
		
		foreach ($inputData as $inputName => $inputInfo) {
			$validationError = $this->validateInput($inputName, $inputInfo['value'], $inputInfo['maxLength']);
			if ($validationError !== null) {
				$response->setData(['message' => $validationError, 'success' => false]);
				$response->setStatus(400);
				return $response;
			}
		}
		
		if(!$this->session->get('captcha_verified')) {
			$response->setData(['message' => 'Captcha is not verified!', 'success' => false]);
			$response->setStatus(400);
			return $response;
		}
		
		try {
			$mainDomain = $this->userService->getMainDomain();
			$userEmail = $username.'@'.$mainDomain;

			$newUserEntry = $this->userService->registerUser($displayname, $recoveryEmail, $username, $userEmail, $password);
			
			$this->userService->createHMEAlias($username, $userEmail);
			$this->userService->createNewDomainAlias($username, $userEmail);
			
			$this->userService->setTOS($username, true);
			$this->userService->setUserLanguage($username, $language);
			
			if($recoveryEmail !== '') {
				$this->userService->setRecoveryEmail($username, $recoveryEmail);
			}
			$this->userService->setAccountDataLocally($username, $userEmail, $newUserEntry['quota']);
		
			$this->userService->sendWelcomeEmail($displayname, $username, $userEmail, $language);
			
			$response->setStatus(200);
			$response->setData(['success' => true]);

		} catch (Exception $e) {
			$response->setData(['message' => $e->getMessage(), 'success' => false]);
			$response->setStatus(500);
		}
		return $response;
	}

	public function validateInput($inputName, $value, $maxLength = null) : mixed {
		if ($value === '') {
			return "$inputName is missing.";
		}
	
		if ($maxLength !== null && strlen($value) > $maxLength) {
			return "$inputName is too large.";
		}
	
		return null; // Validation passed
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
		$operand1 = $this->userService->getRandomCharacter();
		$operand2 = $this->userService->getRandomCharacter();
		$operator = $this->userService->getOperator();
		
		$this->session->set('operand1', $operand1);
		$this->session->set('operand2', $operand2);
		$this->session->set('operator', $operator);
		$this->session->set('captcha_verified', false);

		$response->setData(['operand1' => $operand1, 'operand2' => $operand2, 'operator' => $operator]);
		$response->setStatus(200);
		return $response;
	}
	/**
	 * @NoAdminRequired
	 * @PublicPage
	 * @NoCSRFRequired
	 */
	public function verifyCaptcha(string $humanverification = '') : DataResponse {
		
		$this->session->set('captcha_verified', false);
		
		$operand1 = $this->session->get('operand1');
		$operand2 = $this->session->get('operand2');
		$operator = $this->session->get('operator');
		
		$response = new DataResponse();
		$response->setStatus(400);
		if (!$humanverification || !$operand1 || !$operand2 || !$operator) {
			return $response;
		}
		
		if (!$this->userService->checkAnswer($operand1, $operand2, $operator, $humanverification)) {
			$this->session->set('captcha_verified', true);
			$response->setStatus(200);
		}
		return $response;
	}

}
