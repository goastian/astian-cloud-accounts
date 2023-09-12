<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Controller;

use Exception;
use OCP\IRequest;
use Psr\Log\LoggerInterface;
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

	public function __construct($appName, IRequest $request, LoggerInterface $logger, IConfig $config, UserService $userService, MailUsageMapper $mailUsageMapper) {
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

		$this->logger->warning("Checking checkAppCredentials...", ['app' => 'ecloud-accounts']);
		if (!$this->checkAppCredentials($token)) {
			$response->setStatus(401);
			$this->logger->error("checkAppCredentials failed!", ['app' => 'ecloud-accounts']);
			return $response;
		}

		$this->logger->warning("Checking userExists...", ['app' => 'ecloud-accounts']);
		if (!$this->userService->userExists($uid)) {
			$response->setStatus(404);
			$this->logger->error("User is already exists!", ['app' => 'ecloud-accounts']);
			return $response;
		}
		$this->logger->warning("Trying to get user...", ['app' => 'ecloud-accounts']);
		$user = $this->userService->getUser($uid);

		if (is_null($user)) {
			$response->setStatus(404);
			$this->logger->error("User not found!", ['app' => 'ecloud-accounts']);
			return $response;
		}

		$user->setEMailAddress($email);
		$user->setQuota($quota);
		$this->logger->error('New User! Email:' . $email . " and UID: ".$uid." is set!", ['app' => 'ecloud-accounts']);
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
