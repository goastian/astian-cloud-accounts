<?php

/*
   * Copyright 2022 - Murena SAS - tous droits réservés
   */

namespace OCA\EcloudAccounts\Controller;

use Exception;
use OCA\EcloudAccounts\AppInfo\Application;
use OCA\EcloudAccounts\Exception\AddUsernameToCommonStoreException;
use OCA\EcloudAccounts\Exception\LDAPUserCreationException;
use OCA\EcloudAccounts\Exception\RecoveryEmailValidationException;
use OCA\EcloudAccounts\Service\CaptchaService;
use OCA\EcloudAccounts\Service\HCaptchaService;
use OCA\EcloudAccounts\Service\NewsLetterService;
use OCA\EcloudAccounts\Service\UserService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Files\IAppData;
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
	private HCaptchaService $hCaptchaService;
	protected $l10nFactory;
	private $session;
	private $userSession;
	private $urlGenerator;
	/** @var IConfig */
	private IConfig $config;
	private IInitialState $initialState;
	private IAppData $appData;
	private const SESSION_VERIFIED_USERNAME = 'verified_username';
	private const SESSION_VERIFIED_DISPLAYNAME = 'verified_displayname';
	private const CAPTCHA_VERIFIED_CHECK = 'captcha_verified';
	private const ALLOWED_CAPTCHA_PROVIDERS = ['image', 'hcaptcha'];
	private const DEFAULT_CAPTCHA_PROVIDER = 'image';
	private const HCAPTCHA_PROVIDER = 'hcaptcha';
	private const HCAPTCHA_DOMAINS = ['https://hcaptcha.com', 'https://*.hcaptcha.com'];
	private const BLACKLISTED_USERNAMES_FILE_NAME = 'blacklisted_usernames';
	
	private ILogger $logger;
	public function __construct(
		$AppName,
		IRequest $request,
		UserService $userService,
		NewsLetterService $newsletterService,
		CaptchaService $captchaService,
		HCaptchaService $hCaptchaService,
		IFactory $l10nFactory,
		IUserSession $userSession,
		IURLGenerator $urlGenerator,
		ISession $session,
		IConfig $config,
		ILogger $logger,
		IInitialState $initialState,
		IAppData $appData
	) {
		parent::__construct($AppName, $request);
		$this->appName = $AppName;
		$this->userService = $userService;
		$this->newsletterService = $newsletterService;
		$this->captchaService = $captchaService;
		$this->hCaptchaService = $hCaptchaService;
		$this->l10nFactory = $l10nFactory;
		$this->session = $session;
		$this->userSession = $userSession;
		$this->config = $config;
		$this->urlGenerator = $urlGenerator;
		$this->logger = $logger;
		$this->request = $request;
		$this->initialState = $initialState;
		$this->appData = $appData;
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
		$this->initialState->provideInitialState('lang', $lang);
		
		$response = new TemplateResponse(
			Application::APP_ID,
			'signup',
			['appName' => Application::APP_ID, 'lang' => $lang],
			TemplateResponse::RENDER_AS_GUEST
		);

		$captchaProvider = $this->getCaptchaProvider();
		$this->initialState->provideInitialState('captchaProvider', $captchaProvider);
		
		if ($captchaProvider === self::HCAPTCHA_PROVIDER) {
			$csp = $response->getContentSecurityPolicy();
			foreach (self::HCAPTCHA_DOMAINS as $domain) {
				$csp->addAllowedScriptDomain($domain);
				$csp->addAllowedFrameDomain($domain);
				$csp->addAllowedStyleDomain($domain);
				$csp->addAllowedConnectDomain($domain);
			}
			$response->setContentSecurityPolicy($csp);
			$hcaptchaSiteKey = $this->config->getSystemValue(Application::APP_ID . '.hcaptcha_site_key');
			$this->initialState->provideInitialState('hCaptchaSiteKey', $hcaptchaSiteKey);
		}
		return $response;
	}
	/**
	 * @NoAdminRequired
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 **/
	public function sendemail() {
		$displayname = 'Ronak';
		$username = 'ronak.patel';
		$userEmail = 'eronax59@gmail.com';
		$language = 'en';
		$this->userService->sendWelcomeEmail($displayname, $username, $userEmail, $language);
		echo 'sent';
		die;
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
	public function create(string $recoveryEmail = '', string $password = '', string $language = 'en', bool $newsletterEos = false, bool $newsletterProduct = false): DataResponse {
		
		$response = new DataResponse();
		
		if(!$this->session->get(self::CAPTCHA_VERIFIED_CHECK)) {
			$response->setData(['message' => 'Captcha is not verified!', 'success' => false]);
			$response->setStatus(400);
			return $response;
		}

		$displayname = $this->session->get(self::SESSION_VERIFIED_DISPLAYNAME);
		$username = $this->session->get(self::SESSION_VERIFIED_USERNAME);

		if ($this->isNullOrEmptyInput($displayname) || $this->isNullOrEmptyInput($username)) {
			$response->setData(['message' => 'Username is already taken.', 'success' => false]);
			$response->setStatus(400);
			return $response;
		}

		if (preg_match("/\\\/", $password)) {
			$response->setData(['message' => 'Password has invalid characters.', 'success' => false]);
			$response->setStatus(400);
			return $response;
		}

		$inputData = [
			'username' => ['value' => $username, 'maxLength' => 30],
			'display name' => ['value' => $displayname, 'maxLength' => 30],
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
			$username = mb_strtolower($username, 'UTF-8');
			$mainDomain = $this->userService->getMainDomain();
			$userEmail = $username.'@'.$mainDomain;
			$this->userService->registerUser($displayname, $recoveryEmail, $username, $userEmail, $password, $language);
			sleep(5);

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
			
			$this->session->remove(self::SESSION_VERIFIED_USERNAME);
			$this->session->remove(self::SESSION_VERIFIED_DISPLAYNAME);
			$this->session->remove(self::CAPTCHA_VERIFIED_CHECK);
			$ipAddress = $this->request->getRemoteAddress();
			$this->userService->addUsernameToCommonDataStore($username, $ipAddress, $recoveryEmail);
			$response->setStatus(200);
			$response->setData(['success' => true]);

		} catch (LDAPUserCreationException | Error $e) {
			$this->logger->logException($e, ['app' => Application::APP_ID]);
			$response->setData(['message' => 'A server-side error occurred while processing your request! Please try again later.', 'success' => false]);
			$response->setStatus(500);
		} catch (RecoveryEmailValidationException $e) {
			$this->logger->logException($e, ['app' => Application::APP_ID]);
			$response->setData(['message' => $e->getMessage(), 'success' => false]);
			$response->setStatus(400);
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

	private function isNullOrEmptyInput(string|null $input): bool {
		if($input === null || empty(trim($input))) {
			return true;
		}

		return false;
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
	private function validateInput(string $inputName, string $value, ?int $maxLength = null) : ?string {
		if ($value === '') {
			return ucfirst(strtolower($inputName))." is required.";
		}
		if ($maxLength !== null && strlen($value) > $maxLength) {
			return ucfirst(strtolower($inputName))." is too large.";
		}
	
		return null; // Validation passed
	}
	/**
	 * Check if a username and displayname is valid or not.
	 *
	 * @NoAdminRequired
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 *
	 */
	public function tempApiCheck() {
		try {
			$username = 'ronakp1';
			if($this->userService->isUsernameTaken($username)) {
				echo 'username taken';
			} else {
				echo 'username nottaken';
			}
		} catch (Exception $e) {
			echo 'username exception issue. '.$e->getMessage();
		}
	}
	/**
	 * Check if a username is available.
	 *
	 * @NoAdminRequired
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $username The username to check.
	 * @param string $displayname The displayname to check.
	 *
	 * @return \OCP\AppFramework\Http\DataResponse
	 */
	public function validateFields(string $username, string $displayname) : DataResponse {
		$this->session->remove(self::SESSION_VERIFIED_DISPLAYNAME);
		$this->session->remove(self::SESSION_VERIFIED_USERNAME);
		$response = new DataResponse();
		$response->setStatus(400);

		if (empty($username)) {
			$response->setData(['message' => 'Username is required.', 'field' => 'username', 'success' => false]);
			return $response;
		}
		if (empty($displayname)) {
			$response->setData(['message' => 'Display name is required.', 'field' => 'display name', 'success' => false]);
			return $response;
		}

		$inputData = [
			'username' => ['value' => $username, 'maxLength' => 30],
			'display name' => ['value' => $displayname, 'maxLength' => 30]
		];
		
		foreach ($inputData as $inputName => $inputInfo) {
			$validationError = $this->validateInput($inputName, $inputInfo['value'], $inputInfo['maxLength']);
			if ($validationError !== null) {
				$response->setData(['message' => $validationError, 'field' => $inputName, 'success' => false]);
				$response->setStatus(400);
				return $response;
			}
		}
		if (!preg_match('/^(?=.{3,30}$)(?![_.-])(?!.*[_.-]{2})[a-zA-Z0-9._-]+(?<![_.-])$/', $username)) {
			$response->setData(['message' => 'Username must consist of letters, numbers, hyphens, dots and underscores only.', 'field' => 'username', 'success' => false]);
			$response->setStatus(403);
			return $response;
		}
		try {
			$username = mb_strtolower($username, 'UTF-8');
			$blacklist = [];
			$appDataFolder = $this->appData->getFolder('/');
			if (!$appDataFolder->fileExists(self::BLACKLISTED_USERNAMES_FILE_NAME)) {
				$appDataFolder->newFile(self::BLACKLISTED_USERNAMES_FILE_NAME, "");
			}
			$content = $appDataFolder->getFile(self::BLACKLISTED_USERNAMES_FILE_NAME)->getContent();
			$blacklist = explode("\n", $content);

			if (in_array($username, $blacklist)) {
				$response->setData(['message' => 'Username is already taken.', 'field' => 'username', 'success' => false]);
			} elseif (!$this->userService->userExists($username) && !$this->userService->isUsernameTaken($username)) {
				$response->setStatus(200);
				$this->session->set(self::SESSION_VERIFIED_USERNAME, $username);
				$this->session->set(self::SESSION_VERIFIED_DISPLAYNAME, $displayname);
			} else {
				$response->setData(['message' => 'Username is already taken.', 'field' => 'username', 'success' => false]);
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
		// Don't allow requests to image captcha if different provider is set
		if ($this->getCaptchaProvider() !== self::DEFAULT_CAPTCHA_PROVIDER) {
			$response = new DataResponse();
			$response->setStatus(400);
			return $response;
		}

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
	 * @param string $token The user-provided human verification input.
	 * @param string $bypassToken Token to bypass captcha for automation testing
	 *
	 * @return \OCP\AppFramework\Http\DataResponse
	 */
	public function verifyCaptcha(string $userToken = '', string $bypassToken = '') : DataResponse {
		$response = new DataResponse();

		// Check if the input matches the bypass token
		$bypassTokenInConfig = $this->config->getSystemValue('bypass_captcha_token', '');
		if ((!empty($bypassTokenInConfig) && $bypassToken === $bypassTokenInConfig)) {
			$this->session->set(self::CAPTCHA_VERIFIED_CHECK, true);
			$response->setStatus(200);
		}

		$response->setStatus(400);
		$captchaProvider = $this->getCaptchaProvider();

		// Check for default captcha provider
		if ($captchaProvider === self::DEFAULT_CAPTCHA_PROVIDER && $this->verifyImageCaptcha($userToken)) {
			$this->session->set(self::CAPTCHA_VERIFIED_CHECK, true);
			$this->session->remove(CaptchaService::CAPTCHA_RESULT_KEY);
			$response->setStatus(200);
		}

		// Check for hcaptcha provider
		if ($captchaProvider === self::HCAPTCHA_PROVIDER && $this->hCaptchaService->verify($userToken)) {
			$this->session->set(self::CAPTCHA_VERIFIED_CHECK, true);
			$response->setStatus(200);
		}
		return $response;
	}

	private function verifyImageCaptcha(string $captchaInput = '') : bool {
		$captchaResult = (string) $this->session->get(CaptchaService::CAPTCHA_RESULT_KEY, '');
		return (!empty($captchaResult) && $captchaInput === $captchaResult);
	}

	private function getCaptchaProvider() : string {
		$captchaProvider = $this->config->getSystemValue('ecloud-accounts.captcha_provider', self::DEFAULT_CAPTCHA_PROVIDER);

		if (!in_array($captchaProvider, self::ALLOWED_CAPTCHA_PROVIDERS)) {
			$captchaProvider = self::DEFAULT_CAPTCHA_PROVIDER;
		}
		return $captchaProvider;
	}

}
