<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Controller;

use Exception;
use OCP\IRequest;
use OCP\ILogger;
use OCP\IConfig;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http\DataResponse;
use OCA\EcloudAccounts\Service\UserService;
use OCA\TermsOfService\Service\SignatoryService ;
use OCA\EcloudAccounts\Db\MailUsageMapper;

class UserController extends ApiController {
	/** @var UserService */
	private $userService;
	/** @var SignatoryService */
	private $signatoryService;

	private $mailUsageMapper;

	private $logger;

	private $config;

	public function __construct($appName, IRequest $request, ILogger $logger, IConfig $config, UserService $userService, MailUsageMapper $mailUsageMapper, SignatoryService $signatoryService) {
		parent::__construct($appName, $request);
		$this->userService = $userService;
		$this->mailUsageMapper = $mailUsageMapper;
		$this->logger = $logger;
		$this->config = $config;
		$this->signatoryService = $signatoryService;
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

		$exists = false;

		if ($this->userService->userExists($uid)) {
			$exists = true;
		}

		// To check for old accounts
		$mailDomain = $this->config->getSystemValue('mail_domain');
		$mailDomainSuffix = !empty($mailDomain) ? '@' . $mailDomain : '';
		if (!$exists && stristr($uid, $mailDomainSuffix) === false) {
			$exists = $this->userService->userExists($uid . $mailDomainSuffix);
		}

		$response->setData($exists);
		return $response;
	}

	/**
	 * @CORS
	 * @PublicPage
	 * @NoCSRFRequired
	 */
	public function setAccountData(string $token, string $uid, string $email, string $recoveryEmail, string $hmeAlias, string $quota = '1024 MB'): DataResponse {
		$response = new DataResponse();

		if (!$this->checkAppCredentials($token)) {
			$response->setStatus(401);
			return $response;
		}

		if (!$this->userService->userExists($uid)) {
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
		$tosSignatoryInserted = $this->signatoryService->tosSignatoryInsert($uid);
		if (!$tosSignatoryInserted) {
			return $this->getErrorResponse($response, 'error_setting_tos', 400);
		}
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
