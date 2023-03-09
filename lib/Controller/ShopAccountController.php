<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Controller;

use Exception;
use OCA\EcloudAccounts\Service\ShopAccountService;
use OCP\IUserSession;
use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http;
use OCP\ILogger;

class ShopAccountController extends Controller {
	private $shopAccountService;
	private $userSession;

	private $logger;
	private const SUBSCRIPTION_STATUS_LIST = [
		'pending',
		'active',
		'on-hold'
	];
	private const PENDING_CANCEL_STATUS = 'pending-cancel';
	private const CANCELLED_STATUS = 'cancelled';

	public function __construct($appName, IRequest $request, IUserSession $userSession, ShopAccountService $shopAccountService, ILogger $logger) {
		parent::__construct($appName, $request);
		$this->shopAccountService = $shopAccountService;
		$this->userSession = $userSession;
		$this->logger = $logger;
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
		try {
			if (!$userId) {
				throw new Exception("Invalid user id");
			}
			$data = ['order_count' => 0, 'my_orders_url' => $this->shopAccountService->getShopUrl() . '/my-account/orders'];
			$orders = $this->shopAccountService->getOrders($userId);
			$data['order_count'] = count($orders);
			$response = new DataResponse();
			$response->setData($data);
			return $response;
		} catch (Exception $e) {
			$this->logger->error('There was an issue querying order for user : ' . strval($userId));
			$this->logger->logException($e, ['app' => Application::APP_ID]);
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}
	}

	/**
	 * @NoAdminRequired
	 */
	public function getSubscriptionInfo(int $userId) {
		try {
			if (!$userId) {
				throw new Exception("Invalid user id");
			}
			$data = ['subscription_count' => 0 , 'pending_cancel_subscriptions' => []];
			$subscriptions = $this->shopAccountService->getSubscriptions($userId, 'any');
			$total_subscriptions = 0;
			foreach ($subscriptions as $subscription) {
				if (in_array($subscription['status'], self::SUBSCRIPTION_STATUS_LIST)) {
					$total_subscriptions++;
				}
				if ($subscription['status'] === self::PENDING_CANCEL_STATUS) {
					array_push($data['pending_cancel_subscriptions'], $subscription['id']);
					$this->shopAccountService->updateSubscriptionStatus($subscription['id'], self::CANCELLED_STATUS);
				}
			}
			$data['subscription_count'] = $total_subscriptions;
			$response = new DataResponse();
			$response->setData($data);
			return $response;
		} catch (Exception $e) {
			$this->logger->error('There was an issue querying subscription for user : ' . strval($userId));
			$this->logger->logException($e, ['app' => Application::APP_ID]);
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}
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
