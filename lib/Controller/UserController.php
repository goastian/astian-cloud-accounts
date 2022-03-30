<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Controller;

use Exception;
use OCP\IRequest;
use OCP\ILogger;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http\DataResponse;
use OCA\EcloudAccounts\Service\UserService;
use OCA\EcloudAccounts\Db\MailUsageMapper;

class UserController extends ApiController
{

    /** @var UserService */
    private $userService;

    private $mailUsageMapper;

    private $logger;

    public function __construct($appName, IRequest $request, ILogger $logger, UserService $userService, MailUsageMapper $mailUsageMapper)
    {
        parent::__construct($appName, $request);
        $this->userService = $userService;
        $this->mailUsageMapper = $mailUsageMapper;
        $this->logger = $logger;
    }

    /**
     * @CORS
     * @PublicPage
     * @NoCSRFRequired
     */
    public function userExists(string $token, string $uid): DataResponse
    {
        $response = new DataResponse();
        if (!$this->checkAppCredentials($token)) {
            $response->setStatus(401);
            return $response;
        }

        $response->setData($this->userService->userExists($uid));
        return $response;
    }

    /**
     * @CORS
     * @PublicPage
     * @NoCSRFRequired
     */
    public function setAccountData(string $token, string $uid, string $email, string $recoveryEmail, string $quota = '1024 MB'): DataResponse
    {

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

        $recoveryEmailUpdated = $this->userService->setRecoveryEmail($uid, $recoveryEmail);
        if (!$recoveryEmailUpdated) {
            return $this->getErrorResponse($response, 'error_setting_recovery', 400);
        }

        $createdFolder = true;
        if ($this->userService->isShardingEnabled()) {
            $createdFolder = $this->userService->createUserFolder($uid);
        }
        if (!$createdFolder) {
            $response->setStatus(500);
        }

        return $response;
    }

    /**
     * @CORS
     * @PublicPage
     * @NoCSRFRequired
     */
    public function setMailQuotaUsage(array $usage, string $token): DataResponse
    {
        $response = new DataResponse();
        if (!$this->checkAppCredentials($token)) {
            $response->setStatus(401);
            return $response;
        }
        try {
            $this->updateMailQuotaUsageInPreferences($usage);
        } catch (Exception $e) {
            $statusCode = 500;
            $errorMessage = 'error_setting_mail_quota_usage';
            $response = $this->getErrorResponse($response, $errorMessage, $statusCode);

            $this->logger->error($errorMessage . ': ' . $e->getMessage());
        }
        return $response;
    }

    private function updateMailQuotaUsageInPreferences(array $usage)
    {
        $this->mailUsageMapper->updateUsageInPreferences($usage);
    }

    private function getErrorResponse(DataResponse $response, string $error, int $code)
    {
        $response->setStatus($code);
        $response->setData(['error' => $error]);
        return $response;
    }

    private function checkAppCredentials(string $token): bool
    {
        $ecloud_accounts_secret = $this->userService->getConfigValue('secret');
        return strcmp($token, $ecloud_accounts_secret) === 0;
    }
}
