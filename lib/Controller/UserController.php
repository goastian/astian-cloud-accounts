<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Controller;

use Exception;
use OCA\EcloudAccounts\Db\MailUsageMapper;
use OCA\EcloudAccounts\Service\UserService;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http\DataResponse;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IRequest;

class UserController extends ApiController {
	/** @var UserService */
	private $userService;

	private $mailUsageMapper;

	private $logger;

	private $config;

	public function __construct($appName, IRequest $request, ILogger $logger, IConfig $config, UserService $userService, MailUsageMapper $mailUsageMapper) {
		parent::__construct($appName, $request);
		$this->userService = $userService;
		$this->mailUsageMapper = $mailUsageMapper;
		$this->logger = $logger;
		$this->config = $config;
	}

	/**
	 * @CORS
	 * @PublicPage
	 * @NoCSRFRequired
	 */
	public function userExists(string $token, string $uid): DataResponse {
		$response = new DataResponse();
		if (!$this->checkAppCredentials($token)) {
			$response->setStatus(401);
			return $response;
		}

		$exists = $this->checkUserExists($uid);
		$response->setData($exists);
		return $response;
	}
	public function checkUserExists(string $uid) : bool {
		$exists = $this->userService->userExists($uid);

		if (!$exists) {
			$uid = trim($uid);
			$uid = mb_strtolower($uid, 'UTF-8');
			$legacyDomain = $this->config->getSystemValue('legacy_domain', '');
			$legacyDomainSuffix = !empty($legacyDomain) ? '@' . $legacyDomain : '';

			$mainDomain = $this->config->getSystemValue('main_domain');
			$mainDomainSuffix = !empty($mainDomain) ? '@' . $mainDomain : '';
			
			if (str_ends_with($uid, $legacyDomainSuffix)) {
				$uid = str_replace($legacyDomainSuffix, '', $uid);
			}
			if (str_ends_with($uid, $mainDomainSuffix)) {
				$uid = str_replace($mainDomainSuffix, '', $uid);
			}
			$exists = $this->userService->userExists($uid);
			if(!$exists) {
				$exists = $this->userService->userExists($uid . $mainDomainSuffix);
			}
			if(!$exists) {
				$exists = $this->userService->userExists($uid . $legacyDomainSuffix);
			}
		}
		return $exists;
	}
	/**
	 * @CORS
	 * @PublicPage
	 * @NoCSRFRequired
	 */
	public function setAccountData(string $token, string $uid, string $email, string $recoveryEmail, string $hmeAlias, string $quota = '1024 MB', bool $tosAccepted = false): DataResponse {
		$response = new DataResponse();

		if (!$this->checkAppCredentials($token)) {
			$response->setStatus(401);
			return $response;
		}

		if (!$this->checkUserExists($uid)) {
			$response->setStatus(404);
			return $response;
		}

		$user = $this->userService->getUser($uid);

		if (is_null($user)) {
			$response->setStatus(404);
			return $response;
		}

		$user->setEMailAddress($email);
		$user->setQuota($quota);
		$this->userService->sendWelcomeEmail($uid, $email);
		$this->config->setUserValue($uid, 'terms_of_service', 'tosAccepted', intval($tosAccepted));
		$recoveryEmailUpdated = $this->userService->setRecoveryEmail($uid, $recoveryEmail);
		if (!$recoveryEmailUpdated) {
			return $this->getErrorResponse($response, 'error_setting_recovery', 400);
		}
		$hmeAliasAdded = $this->userService->addHMEAliasInConfig($uid, $hmeAlias);
		if (!$hmeAliasAdded) {
			return $this->getErrorResponse($response, 'error_adding_hme_alias', 400);
		}
		return $response;
	}

	/**
	 * @CORS
	 * @PublicPage
	 * @NoCSRFRequired
	 */
	public function setMailQuotaUsage(array $usage, string $token): DataResponse {
		$response = new DataResponse();
		if (!$this->checkAppCredentials($token)) {
			$response->setStatus(401);
			return $response;
		}
		try {
			// Explicitly cast input values to integer
			$usage = array_map(fn ($value) => (int) $value, $usage);
			$this->updateMailQuotaUsageInPreferences($usage);
		} catch (Exception $e) {
			$statusCode = 500;
			$errorMessage = 'error_setting_mail_quota_usage';
			$response = $this->getErrorResponse($response, $errorMessage, $statusCode);

			$this->logger->error($errorMessage . ': ' . $e->getMessage());
		}
		return $response;
	}

	private function updateMailQuotaUsageInPreferences(array $usage) {
		$this->mailUsageMapper->updateUsageInPreferences($usage);
	}

	private function getErrorResponse(DataResponse $response, string $error, int $code) {
		$response->setStatus($code);
		$response->setData(['error' => $error]);
		return $response;
	}

	private function checkAppCredentials(string $token): bool {
		$ecloud_accounts_secret = $this->userService->getConfigValue('secret');
		return hash_equals($ecloud_accounts_secret, $token);
	}
}
