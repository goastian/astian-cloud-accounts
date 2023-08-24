<?php

/*
   * Copyright 2022 - Murena SAS - tous droits réservés
   */

namespace OCA\EcloudAccounts\Controller;

use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\AppFramework\Http\TemplateResponse;
use OCA\EcloudAccounts\AppInfo\Application;
use OCA\EcloudAccounts\Service\LDAPConnectionService;
use OCP\ISession;
use Psr\Log\LoggerInterface;
use OCA\LdapWriteSupport\Service\Configuration;
use OCP\LDAP\ILDAPProvider;
use OCP\AppFramework\Http\DataResponse;

class AccountController extends Controller {
	protected $appName;
	protected $request;
	// private ISession $session;
	private $LDAPConnectionService;
	private $logger;
	private $configuration;
	private $ldapProvider;
	private $ldapConnect;
	private int $quotaInBytes = 1073741824;
	private int $ldapQuota;

	public function __construct(
		$AppName,
		IRequest $request,
		// ISession $session,
		LDAPConnectionService $LDAPConnectionService,
		LoggerInterface $logger,
		ILDAPProvider $LDAPProvider,
		Configuration $configuration
	) {
		parent::__construct($AppName, $request);
		$this->appName = $AppName;
		// $this->session = $session;
		$this->LDAPConnectionService = $LDAPConnectionService;
		$this->ldapProvider = $LDAPProvider;
		$this->configuration = $configuration;
		$this->logger = $logger;
		$quota = getenv('CLOUD_QUOTA_IN_BYTES');
		if (!$quota) {
			$this->ldapQuota = $this->quotaInBytes;
		} else {
			$this->ldapQuota = intval($quota);
		}
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
	public function create(string $displayname, string $email, string $username, string $password, string $domain) {
		$response = new DataResponse();
		try {
			$connection = $this->LDAPConnectionService->getLDAPConnection();
			$base = $this->LDAPConnectionService->getLDAPBaseUsers()[0];
			
			// Check if the username already exists
			$filter = "(usernameWithoutDomain=$username)";
			$searchResult = ldap_search($connection, $base, $filter);
			
			if (!$searchResult) {
				throw new Exception("Error while searching Murena username.");
			}
			
			$entries = ldap_get_entries($connection, $searchResult);
			
			if ($entries['count'] > 0) {
				$msg = "Username already exists.";
				$response->setStatus(403);
			} else {
				$newUserDN = "username=$username@$domain," . $base;
				$userClusterID = 'HEL01';
				$newUserEntry = [
					'mailAddress' => $email,
					'username' => $username . '@' . $domain,
					'usernameWithoutDomain' => $username,
					'userPassword' => $password,
					'displayName' => $displayname,
					'quota' => $this->ldapQuota,
					'mailAlternate' => $email,
					'recoveryMailAddress' => $email,
					'active' => 'TRUE',
					'mailActive' => 'TRUE',
					'userClusterID' => $userClusterID,
					'objectClass' => ['murenaUser', 'simpleSecurityObject']
				];
				
				$ret = ldap_add($connection, $newUserDN, $newUserEntry);
				
				if (!$ret) {
					throw new Exception("Error while creating Murena account.");
				}
				
				$msg = "Congratulations! You've successfully created a Murena account.";
				$response->setStatus(200);
			}
		} catch (Exception $e) {
			$msg = $e->getMessage();
			$response->setStatus(403);
		}
		$response->setData(['message' => $msg]);
		return $response;
	}
}
