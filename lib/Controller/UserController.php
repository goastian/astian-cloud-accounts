
<?php

namespace OCA\EcloudAccounts\Controller;

use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http\JSONResponse;

use OCA\EcloudAccounts\Service\UserService;

class UserController extends ApiController
{

    /** @var UserService */
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @PublicPage
     */

    public function setAccountData(string $token, string $uid, string $email, string $quota = '1024 MB'): JSONResponse
    {

        $response = new JSONResponse();

        if(!$this->checkAppCredentials($token)) { 
            $response->setStatus(401);
            return $response;
        }

        if(!$this->userService->userExists($uid)) {
            $response->setStatus(404);
            return $response; 
        }

        $this->userService->setEmail($uid, $email);
        $this->userService->setQuota($uid, $quota);

        return $response;
    }

    private function checkAppCredentials(string $token) : bool {
        $ecloud_accounts_secret = $_ENV['ECLOUD_ACCOUNTS_SECRET'];
        return strcmp($token, $ecloud_accounts_secret) === 0;
    }
}
