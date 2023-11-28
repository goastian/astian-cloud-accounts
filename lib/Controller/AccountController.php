<?php

/*
   * Copyright 2022 - Murena SAS - tous droits réservés
   */

namespace OCA\EcloudAccounts\Controller;

use Exception;
use OCA\EcloudAccounts\AppInfo\Application;
use OCA\EcloudAccounts\Service\CaptchaService;
use OCA\EcloudAccounts\Service\UserService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\ISession;
use OCP\L10N\IFactory;

class AccountController extends Controller {
	protected $appName;
	protected $request;
	private $userService;
	private $captchaService;
	protected $l10nFactory;
	private $session;
	public function __construct(
		$AppName,
		IRequest $request,
		UserService $userService,
		CaptchaService $captchaService,
		IFactory $l10nFactory,
		ISession $session
	) {
		parent::__construct($AppName, $request);
		$this->appName = $AppName;
		$this->userService = $userService;
		$this->captchaService = $captchaService;
		$this->l10nFactory = $l10nFactory;
		$this->session = $session;
	}

	/**
	 * @NoAdminRequired
	 * @UseSession
	 * @NoCSRFRequired
	 *
	 * @param string $lang Language code (default: 'en')
	 *
	 * @return \OCP\AppFramework\Http\TemplateResponse
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
	 *
	 * @param string $displayname      User's display name
	 * @param string $recoveryEmail    User's recovery email
	 * @param string $username         User's username
	 * @param string $password         User's password
	 * @param string $language         User's language preference
	 *
	 * @return \OCP\AppFramework\Http\DataResponse
	 */
	public function create(string $displayname = '', string $recoveryEmail = '', string $username = '', string $password = '', string $language = ''): DataResponse {
		
		$response = new DataResponse();
		
		if(!$this->session->get('captcha_verified')) {
			$response->setData(['message' => 'Captcha is not verified!', 'success' => false]);
			$response->setStatus(400);
			return $response;
		}

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
		
		try {
			$mainDomain = $this->userService->getMainDomain();
			$userEmail = $username.'@'.$mainDomain;

			$newUserEntry = $this->userService->registerUser($displayname, $recoveryEmail, $username, $userEmail, $password);
			
			$this->userService->setAccountDataLocally($username, $userEmail, $newUserEntry['quota']);
			$this->userService->createHMEAlias($username, $userEmail);
			$this->userService->createNewDomainAlias($username, $userEmail);
			$this->userService->setTOS($username, true);
			$this->userService->setUserLanguage($username, $language);
			
			if($recoveryEmail !== '') {
				$this->userService->setRecoveryEmail($username, $recoveryEmail);
			}
		
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
	 * Validate input for a given input name, value, and optional maximum length.
	 *
	 * @param string $inputName The name of the input.
	 * @param string  $value     The value of the input.
	 * @param int|null $maxLength The optional maximum length allowed.
	 *
	 * @return string|null If validation fails, a string describing the error; otherwise, null.
	 */
	public function validateInput(string $inputName, string $value, int $maxLength = null) : ?string {
		if ($value === '') {
			return "$inputName is required.";
		}
	
		if ($maxLength !== null && strlen($value) > $maxLength) {
			return "$inputName is too large.";
		}
	
		return null; // Validation passed
	}
	/**
	 * Check if a username is available.
	 *
	 * @NoAdminRequired
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $username The username to check.
	 *
	 * @return \OCP\AppFramework\Http\DataResponse
	 */
	public function checkUsernameAvailable(string $username) : DataResponse {
		$response = new DataResponse();
		$response->setStatus(400);
		if (!$this->userService->userExists($username)) {
			$response->setStatus(200);
		}
		return $response;
	}

	/**
	 * @NoAdminRequired
	 * @PublicPage
	 * @NoCSRFRequired
	 */
	public function captcha(): Http\DataDisplayResponse {
		$captchaValue = $this->captchaService->generateCaptcha();

		$response = new Http\DataDisplayResponse($captchaValue, Http::STATUS_OK, ['Content-Type' => 'image/png']);

		return $response;
	}
	/**
	 * Verify a human verification input against captcha session values.
	 *
	 * @NoAdminRequired
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $captchaInput The user-provided human verification input.
	 *
	 * @return \OCP\AppFramework\Http\DataResponse
	 */
	public function verifyCaptcha(string $captchaInput = '') : DataResponse {
		$response = new DataResponse();
		
		$captchaResult = (string)$this->session->get('captcha_result', '');
		$response->setStatus(400);
		if ($captchaResult === $captchaInput) {
			$this->session->remove('captcha_result');
			$this->session->set('captcha_verified', true);
			$response->setStatus(200);
		}
		return $response;
	}

}
