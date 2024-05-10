<?php

/*
   * Copyright 2022 - Murena SAS - tous droits réservés
   */

namespace OCA\EcloudAccounts\Controller;

use Exception;
use OCA\EcloudAccounts\AppInfo\Application;
use OCA\EcloudAccounts\Exception\AddUsernameToCommonStoreException;
use OCA\EcloudAccounts\Exception\LDAPUserCreationException;
use OCA\EcloudAccounts\Service\CaptchaService;
use OCA\EcloudAccounts\Service\NewsLetterService;
use OCA\EcloudAccounts\Service\UserService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\L10N\IFactory;

class AccountController extends Controller {
	protected $appName;
	protected $request;
	private $userService;
	private $newsletterService;
	private $captchaService;
	protected $l10nFactory;
	private $session;
	private $userSession;
	private $urlGenerator;
	/** @var IConfig */
	private IConfig $config;
	private const SESSION_USERNAME_CHECK = 'username_check_passed';
	private const CAPTCHA_VERIFIED_CHECK = 'captcha_verified';
	private ILogger $logger;
	public function __construct(
		$AppName,
		IRequest $request,
		UserService $userService,
		NewsLetterService $newsletterService,
		CaptchaService $captchaService,
		IFactory $l10nFactory,
		IUserSession $userSession,
		IURLGenerator $urlGenerator,
		ISession $session,
		IConfig $config,
		ILogger $logger
	) {
		parent::__construct($AppName, $request);
		$this->appName = $AppName;
		$this->userService = $userService;
		$this->newsletterService = $newsletterService;
		$this->captchaService = $captchaService;
		$this->l10nFactory = $l10nFactory;
		$this->session = $session;
		$this->userSession = $userSession;
		$this->config = $config;
		$this->urlGenerator = $urlGenerator;
		$this->logger = $logger;
	}

	/**
	 * @NoAdminRequired
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $lang Language code (default: 'en')
	 *
	 */
	public function index(string $lang = 'en') {
		if ($this->userSession->isLoggedIn()) {
			return new RedirectResponse($this->urlGenerator->linkToDefaultPageUrl());
		}

		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = $lang;

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
	 * @param bool $newsletterEos     Users's subscribe to eos newsletter
	 * @param bool $newsletterProduct Users's subscribe to murena product newsletter
	 *
	 * @return \OCP\AppFramework\Http\DataResponse
	 */
	public function create(string $displayname = '', string $recoveryEmail = '', string $username = '', string $password = '', string $language = 'en', bool $newsletterEos = false, bool $newsletterProduct = false): DataResponse {
		
		$response = new DataResponse();
		
		if(!$this->session->get(self::CAPTCHA_VERIFIED_CHECK)) {
			$response->setData(['message' => 'Captcha is not verified!', 'success' => false]);
			$response->setStatus(400);
			return $response;
		}

		if (!$this->session->get(self::SESSION_USERNAME_CHECK)) {
			$response->setData(['message' => 'Username is already taken.', 'success' => false]);
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
			$this->userService->registerUser($displayname, $recoveryEmail, $username, $userEmail, $password);
			sleep(2);

			$this->userService->setAccountDataLocally($username, $userEmail);
			$this->userService->createHMEAlias($username, $userEmail);
			$this->userService->createNewDomainAlias($username, $userEmail);
			$this->userService->setTOS($username, true);
			$this->userService->setUserLanguage($username, $language);
			$this->newsletterService->setNewsletterSignup($newsletterEos, $newsletterProduct, $userEmail, $language);
			$this->userService->setRecoveryEmail($username, '');
			if($recoveryEmail !== '') {
				$this->userService->setUnverifiedRecoveryEmail($username, $recoveryEmail);
			}
		
			$this->userService->sendWelcomeEmail($displayname, $username, $userEmail, $language);
			
			$this->session->remove(self::SESSION_USERNAME_CHECK);
			$this->session->remove(self::CAPTCHA_VERIFIED_CHECK);

			$this->userService->addUsernameToCommonDataStore($username);
			$response->setStatus(200);
			$response->setData(['success' => true]);

		} catch (LDAPUserCreationException | Error $e) {
			$this->logger->logException($e, ['app' => Application::APP_ID]);
			$response->setData(['message' => 'A server-side error occurred while processing your request! Please try again later.', 'success' => false]);
			$response->setStatus(500);
		} catch (AddUsernameToCommonStoreException $e) {
			$this->logger->logException($e, ['app' => Application::APP_ID]);
			$response->setStatus(200);
			$response->setData(['success' => true]);
		} catch (Exception $e) {
			$this->logger->logException($e, ['app' => Application::APP_ID]);
			$response->setData(['message' => 'An error occurred while creating your account!', 'success' => false]);
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

		if (empty($username)) {
			return $response;
		}

		try {
			if (!$this->userService->userExists($username) && !$this->userService->isUsernameTaken($username)) {
				$response->setStatus(200);
				$this->session->set(self::SESSION_USERNAME_CHECK, true);
			}
		} catch (Exception $e) {
			$this->logger->logException($e, ['app' => Application::APP_ID ]);
			$response->setStatus(500);
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
		$captchToken = getenv('BYPASS_CAPTCHA_TOKEN');
		if($captchInpue === $$captchToken) {
			$response->setStatus(200);
			return $response;
		}

		$captchaResult = (string) $this->session->get(CaptchaService::CAPTCHA_RESULT_KEY, '');
		$response->setStatus(400);
		if ($captchaResult === $captchaInput) {
			$this->session->set(self::CAPTCHA_VERIFIED_CHECK, true);
			$response->setStatus(200);
		}
		$this->session->remove(CaptchaService::CAPTCHA_RESULT_KEY);
		return $response;
	}

}
