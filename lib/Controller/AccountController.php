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
		
		$titles = [
			'createMurenaAccount' => $l->t("Create Murena Account"),
			'captchaVerification' => $l->t("Captcha Verification"),
			'recoveryEmailForm1' => $l->t("For security reasons you need to set a recovery address for your Murena Cloud account."),
			'recoveryEmailForm2' => $l->t("As long as you don't, you'll have limited access to your account."),
			'readAndAcceptTOS' => $l->t("I have read and accept the <a href='__termsURL__' target='_blank'>Terms of Service</a>.")
		];
		$buttons = [
			'createMyAccount' => $l->t("Create My Account"),
			'verify' => $l->t("Verify"),
			'later' => $l->t("Later"),
			'setRecoverEmail' => $l->t("Set my recovery email address"),
			'useMyAccountNow' => $l->t("Use My Account Now")
		];
		$labels = [
			'displayName' => $l->t("Display name"),
			'userName' => $l->t("Username"),
			'enterPassword' => $l->t("Enter Password"),
			'humanVefication' => $l->t("Human Verification"),
			'recoveryEmail' => $l->t("Recovery Email"),
			'newsletter_product' => $l->t("I want to receive news about Murena products and promotions"),
			'newsletter_eos' => $l->t("I want to receive news about /e/OS")
		];
		$placeholders = [
			'displayName' => $l->t("Your name as shown to others"),
			'userName' => $l->t("Username"),
			'enterPassword' => $l->t("Password"),
			'confirmPassword' => $l->t("Confirm"),
			'humanVefication' => $l->t("Human Verification"),
			'recoveryEmail' => $l->t("Recovery Email")
		];
		$errors = [
			'displayName' => $l->t("Display name is required."),
			'userName' => $l->t("Username is required."),
			'userNameInvalid' => $l->t("Username must consist of letters, numbers, hyphens, and underscores only."),
			'userNameLength' => $l->t("Username must be at least 3 characters long."),
			'userNameTaken' => $l->t("Username is already taken."),
			'password' => $l->t("Password is required."),
			'confirmPassword' => $l->t("Confirm password is required."),
			'passwordNotMatched' => $l->t("The confirm password does not match the password."),
			'humanVefication' => $l->t("Human Verification is required."),
			'humanVeficationNotCorrect' => $l->t("Human Verification code is not correct."),
			'recoveryEmail' => $l->t("Recovery Email is required."),
			'acceptTOS' => $l->t("You must read and accept the Terms of Service to create your account.")
		];
		$others = [
			'somethingWentWrong' => $l->t("Something went wrong."),
		];
		$success = [
			'successMessage' => $l->t("Success!"),
			'accountCreated' => $l->t("Your <b>__username__@__domain__</b> account was successfully created."),
			'supportMessage' => $l->t("If you want to use your murena.io email in a mail app like Thunderbird, Outlook or another, please visit <a href='__supportURL__'>this page</a>.")
		];
		$data = [
			'titles' => $titles,
			'buttons' => $buttons,
			'labels' => $labels,
			'placeholders' => $placeholders,
			'errors' => $errors,
			'others' => $others,
			'success' => $success
		];
		$response = new DataResponse();
		$response->setStatus(200);
		$response->setData($data);
		return $response;
	}
}
