<?php
declare(strict_types=1);

namespace OCA\EcloudAccounts\Controller;

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
        $this->shopOrdersUrl = getenv("WP_SHOP_URL") . '/my-account/orders';
    }

    /**
     * @NoAdminRequired
     */

    public function setShopEmailPostDelete(string $shopEmailPostDelete) {
        $user = $this->userSession->getUser();
        $userId = $user->getUID();
        $email = $user->getEMailAddress();

        if(!$this->shopAccountService->validateShopEmail($shopEmailPostDelete, $email)) {
            $response = new DataResponse();
            $response->setStatus(400);
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

        $shopUser = $this->shopAccountService->getUser($email);
        $data = ['count' => 0, 'my_orders_url' => $this->shopOrdersUrl];
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
}