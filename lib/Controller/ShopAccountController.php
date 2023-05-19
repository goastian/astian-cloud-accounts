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
			$data = ['order_count' => 0];

			$shopUrls = $this->shopAccountService->getShopUrls();
			$data['my_order_urls'] = array_map(function($url) {
				return $url . '/my-account/orders';
			}, $shopUrls);

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
			$data = ['subscription_count' => 0];
			$subscriptions = $this->shopAccountService->getSubscriptions($userId, 'any');
			$subscriptions = array_filter($subscriptions, function($subscription) {
				return in_array($subscription['status'], self::SUBSCRIPTION_STATUS_LIST);
			});
			$data['subscription_count'] = count($subscriptions);;
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
	public function getShopUsers() {
		$response = new DataResponse();
		$user = $this->userSession->getUser();
		$email = $user->getEMailAddress();

		$shopUsers = $this->shopAccountService->getUsers($email);

		$shopUsers = array_filter($shopUsers, function($shopUser) {
			return $this->shopAccountService->isUserOIDC($shopUser);
		});

		if (empty($shopUsers)) {
			$response->setStatus(404);
			return $response;
		}

		$response->setData($shopUsers);
		return $response;
	}
}
