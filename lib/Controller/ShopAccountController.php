<?php
declare(strict_types=1);

namespace OCA\EcloudAccounts\Controller;

use Exception;
use OCA\EcloudAccounts\Service\ShopAccountService;
use OCP\IUserSession;
use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IL10N;


class ShopAccountController extends Controller {

    private $shopAccountService;
    private $userSession;
    private $l10n;
    private $shopOrdersUrl;

    public function __construct($appName, IRequest $request, IUserSession $userSession, ShopAccountService $shopAccountService, IL10N $l10n)
    {
        parent::__construct($appName, $request);
        $this->shopAccountService = $shopAccountService;
        $this->userSession = $userSession;
        $this->l10n = $l10n;
    }

    /**
     * @NoAdminRequired
     */
    public function checkShopEmailPostDelete(string $shopEmailPostDelete) {
        $user = $this->userSession->getUser();
        $email = $user->getEMailAddress();
        $response = new DataResponse();

        try {
            $this->validateShopEmailPostDelete($shopEmailPostDelete, $email);
        }
        catch(Exception $e) {  
            $response->setStatus(400);
            $response->setData(['message' => $e->getMessage()]);
            return $response;
        }
    }

    private function validateShopEmailPostDelete(string $shopEmailPostDelete, string $cloudEmail) : void {
        if(!filter_var($shopEmailPostDelete, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid Email Format.');
        }
        if($shopEmailPostDelete === $cloudEmail) {
            throw new Exception('Murena.com email cannot be same as this account\'s email.');
        }
        if($this->shopAccountService->shopEmailExists($shopEmailPostDelete)) {
            throw new Exception('A Murena.com account already uses this e-mail address.');
        }
    }
    /**
     * @NoAdminRequired
     */

    public function setShopEmailPostDelete(string $shopEmailPostDelete) {
        $user = $this->userSession->getUser();
        $userId = $user->getUID();
        $email = $user->getEMailAddress();
        $response = new DataResponse();

        try {
            $this->validateShopEmailPostDelete($shopEmailPostDelete, $email);
        }
        catch(Exception $e) {  
            $response->setStatus(400);
            $response->setData(['message' => $e->getMessage()]);
            return $response;
        }

        $this->shopAccountService->setShopEmailPostDeletePreference($userId, $shopEmailPostDelete);

    }

    /**
     * @NoAdminRequired
     */
    public function setShopDeletePreference(bool $deleteShopAccount) {
        $user = $this->userSession->getUser();
        $userId = $user->getUID();

        $this->shopAccountService->setShopDeletePreference($userId, $deleteShopAccount);
    }

    /**
     * @NoAdminRequired
     */
    public function getOrderInfo() {
        $response = new DataResponse();
        $user = $this->userSession->getUser();
        $email = $user->getEMailAddress();

        $data = ['count' => 0, 'my_orders_url' => $this->shopAccountService->getShopUrl()];
        $shopUser = $this->shopAccountService->getUser($email);
        if(!$shopUser) {
            $response->setData($data);
            return $response;
        }

        $orders = $this->shopAccountService->getOrders($shopUser['id']);

        if(!$orders) {
            $response->setData($data);
            return $response;
        }

        $data['count'] = count($orders);
        $response->setData($data);
        return $response;
    }

    /**
     * @NoAdminRequired
     */
    public function getShopUser() {
        $response = new DataResponse();
        $user = $this->userSession->getUser();
        $email = $user->getEMailAddress();

        $shopUser = $this->shopAccountService->getUser($email);

        if(!$shopUser || !$this->shopAccountService->isUserOIDC($shopUser)) {
            $response->setStatus(404);
            return $response;
        }
        $response->setData($shopUser);
        return $response;
    }
}