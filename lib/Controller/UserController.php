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
use OCP\L10N\IFactory;

class UserController extends ApiController {
	/** @var UserService */
	private $userService;

	private $mailUsageMapper;

	private $logger;

	private $config;
	protected $l10nFactory;
	public function __construct($appName, IRequest $request, ILogger $logger, IConfig $config, UserService $userService, MailUsageMapper $mailUsageMapper, IFactory $l10nFactory) {
		parent::__construct($appName, $request);
		$this->userService = $userService;
		$this->mailUsageMapper = $mailUsageMapper;
		$this->logger = $logger;
		$this->config = $config;
		$this->l10nFactory = $l10nFactory;
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
		$legacyDomain = $this->config->getSystemValue('legacy_domain');
		$legacyDomainSuffix = !empty($legacyDomain) ? '@' . $legacyDomain : '';
		if (!$exists && stristr($uid, $legacyDomainSuffix) === false) {
			$exists = $this->userService->userExists($uid . $legacyDomainSuffix);
		}

		$response->setData($exists);
		return $response;
	}

	/**
	 * @CORS
	 * @PublicPage
	 * @NoCSRFRequired
	 */
	public function setAccountData(string $token, string $uid, string $email, string $recoveryEmail, string $hmeAlias, string $quota = '1024 MB', bool $tosAccepted = false, string $userLanguage = 'en'): DataResponse {
		$response = new DataResponse();

		if (!$this->checkAppCredentials($token)) {
			$response->setStatus(401);
			return $response;
		}

		$data = $this->userService->setAccountData($uid, $email, $recoveryEmail, $hmeAlias, $quota, $tosAccepted, $userLanguage);
		
		if ($data['status'] != 200) {
			return $this->getErrorResponse($response, $data['error'], $data['status']);
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
