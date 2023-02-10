<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Controller;

use Exception;
use OCA\EcloudAccounts\Service\ShopAccountService;
use OCP\IUserSession;
use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;

class ShopAccountController extends Controller {
	private $shopAccountService;
	private $userSession;
	public const SUBSCRIPTION_STATUS_LIST = [
		'pending',
		'active',
		'on-hold',
		'pending-cancel'
	];

	public function __construct($appName, IRequest $request, IUserSession $userSession, ShopAccountService $shopAccountService) {
		parent::__construct($appName, $request);
		$this->shopAccountService = $shopAccountService;
		$this->userSession = $userSession;
	}

	/**
	 * @NoAdminRequired
	 */
	public function checkShopEmailPostDelete(string $shopEmailPostDelete) {
		$user = $this->userSession->getUser();
		$email = $user->getEMailAddress();
		$response = new DataResponse();

		try {
			$this->shopAccountService->validateShopEmailPostDelete($shopEmailPostDelete, $email);
		} catch (Exception $e) {
			$response->setStatus(400);
			$response->setData(['message' => $e->getMessage()]);
			return $response;
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
			$this->shopAccountService->validateShopEmailPostDelete($shopEmailPostDelete, $email);
		} catch (Exception $e) {
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
	public function getOrderInfo(int $userId) {
		$response = new DataResponse();
		$data = ['order_count' => 0,'subscription_count' => 0, 'my_orders_url' => $this->shopAccountService->getShopUrl() . '/my-account/orders'];
		
		$orders = $this->shopAccountService->getOrders($userId);
		if ($orders) {
			$data['order_count'] = count($orders);
		}
		$total_subscriptions = 0;
		
		$subscriptions = $this->shopAccountService->getSubscriptions($userId, 'any');
		foreach ($subscriptions as $subscription) {
			if (in_array($subscription['status'], self::SUBSCRIPTION_STATUS_LIST)) {
				$total_subscriptions++;
			}
		}
		$data['subscription_count'] = $total_subscriptions;
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

		if (!$shopUser || !$this->shopAccountService->isUserOIDC($shopUser)) {
			$response->setStatus(404);
			return $response;
		}
		$response->setData($shopUser);
		return $response;
	}
}
