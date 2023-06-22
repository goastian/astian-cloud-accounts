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
use OCA\EcloudAccounts\Db\MailUsageMapper;

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
	public function setAccountData(string $token, string $uid, string $email, string $recoveryEmail, string $hmeAlias, string $quota = '1024 MB', bool $tosAccepted = false): DataResponse {
		$response = new DataResponse();
		if (!$this->checkAppCredentials($token)) {
			$response->setStatus(401);
			return $response;
		}
		$startTimestamp = time();

		if (!$this->userService->userExists($uid)) {
			$response->setStatus(404);
			return $response;
		}
		$existsTimestamp = time();
		$existsTime = $existsTimestamp - $startTimestamp;
		
		$user = $this->userService->getUser($uid);
		if (is_null($user)) {
			$response->setStatus(404);
			return $response;
		}
		$getUserTimestamp = time();
		$getUserTime = $getUserTimestamp - $existsTimestamp;

		$user->setEMailAddress($email);
		$setEmailTimestamp = time();
		$setEmailTime = $setEmailTimestamp - $getUserTimestamp;

		$user->setQuota($quota);
		$setQuotaTimestamp = time();
		$setQuotaTime = $setQuotaTimestamp - $setEmailTimestamp;

		$this->config->setUserValue($uid, 'terms_of_service', 'tosAccepted', intval($tosAccepted));
		$setTosTimestamp = time();
		$setTosTime = $setTosTimestamp - $setQuotaTimestamp;

		$recoveryEmailUpdated = $this->userService->setRecoveryEmail($uid, $recoveryEmail);
		if (!$recoveryEmailUpdated) {
			return $this->getErrorResponse($response, 'error_setting_recovery', 400);
		}
		$setRecoveryTimestamp = time();
		$setRecoveryTime = $setRecoveryTimestamp - $setTosTimestamp;

		$hmeAliasAdded = $this->userService->addHMEAliasInConfig($uid, $hmeAlias);
		if (!$hmeAliasAdded) {
			return $this->getErrorResponse($response, 'error_adding_hme_alias', 400);
		}
		$setHmeTimestamp = time();
		$setHmeTime = $setHmeTimestamp - $setRecoveryTimestamp;
		$this->logger->error(
			'setAccountData-benchmark: starting at: ' . $startTimestamp . "\n"
			. ' userExists time: ' . $existsTime . "\n"
			. ' getUser time: ' . $getUserTime . "\n"
			. ' setQuota time: ' . $setQuotaTime . "\n"
			. ' setEmail time: ' . $setEmailTime . "\n"
			. ' setTos time: ' . $setTosTime . "\n"
			. ' setRecovery time: ' . $setRecoveryTime . "\n"
			. ' setHme time: ' . $setHmeTime . "\n"
		);

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
