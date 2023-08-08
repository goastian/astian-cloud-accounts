<?php

/*
   * Copyright 2022 - Murena SAS - tous droits réservés
   */

namespace OCA\EcloudAccounts\Controller;

use OCP\AppFramework\Controller;
use OCP\IRequest;

class AccountController extends Controller {
	protected $appName;
	protected $request;

	public function __construct(
		$AppName,
		IRequest $request,
	) {
		parent::__construct($AppName, $request);
		$this->appName = $AppName;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 */
	public function index() {
		echo 'index page'; die;
		return true;
	}
	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 */
	public function signup() {
		echo 'signup page'; die;
		return true;
	}
}
