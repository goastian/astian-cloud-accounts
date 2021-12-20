<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Controller;

use OCP\IRequest;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http\DataResponse;
use OCA\EcloudAccounts\Service\UserService;

class UserController extends ApiController
{

    /** @var UserService */
    private $userService;

    public function __construct($appName, IRequest $request, UserService $userService)
    {
        parent::__construct($appName, $request);
        $this->userService = $userService;
    }

    /**
     * @CORS
     * @PublicPage
     * @NoCSRFRequired
     */
    public function setAccountData(string $token, string $uid, string $email, string $quota = '1024 MB'): DataResponse
    {

        $response = new DataResponse();

        if(!$this->checkAppCredentials($token)) { 
            $response->setStatus(401);
            return $response;
        }

        if(!$this->userService->userExists($uid)) {
            $response->setStatus(404);
            return $response; 
        }
        
        $errors = [];
        $this->userService->setEmail($uid, $email);
        $this->userService->setQuota($uid, $quota);
        $createdFolder = $this->userService->createUserFolder($uid);

        if(!$createdFolder){ 
            $errors[] = 'error_creating_user_folder';
        }
        
        $response->setData(['errors' => $errors]);
        
        return $response;
    }

    private function checkAppCredentials(string $token) : bool {
        $ecloud_accounts_secret = $_ENV['ECLOUD_ACCOUNTS_SECRET'];
        return strcmp($token, $ecloud_accounts_secret) === 0;
    }
}
