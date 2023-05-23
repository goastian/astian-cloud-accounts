<?php


namespace OCA\EcloudAccounts\Service;

use Exception;
use OCP\IConfig;
use OCP\ILogger;
use OCA\EcloudAccounts\AppInfo\Application;
use OCA\EcloudAccounts\Service\CurlService;

class ShopAccountService {
	private IConfig $config;
	private string $appName;
	private CurlService $curl;
	private ILogger $logger;
	private array $shops = [];

	private const ORDERS_ENDPOINT = '/wp-json/wc/v3/orders';
	private const USERS_ENDPOINT = '/wp-json/wp/v2/users';
	private const SUBSCRIPTIONS_ENDPOINT = '/wp-json/wc/v3/subscriptions';
	private const MY_ORDERS_ENDPOINT = '/my-account/orders';
	private const SUBSCRIPTION_STATUS_LIST = [
		'pending',
		'active',
		'on-hold'
	];

	public function __construct($appName, IConfig $config, CurlService $curlService, ILogger $logger) {
		$this->config = $config;
		$this->appName = $appName;

		$shops = $this->config->getSystemValue('murena_shops', []);
		foreach ($shops as $shop) {
			$this->shops[$shop['url']] = $shop;
		}
		$shopUsername = $this->config->getSystemValue('murena_shop_username');
		$shopPassword = $this->config->getSystemValue('murena_shop_password');
		$this->shopUrl = $this->config->getSystemValue('murena_shop_url', '');
		$this->shopReassignUserId = $this->config->getSystemValue('murena_shop_reassign_user_id');


		$this->shopUserUrl = $this->shopUrl . "/wp-json/wp/v2/users";
		$this->shopOrdersUrl = $this->shopUrl . "/wp-json/wc/v3/orders";
		$this->subscriptionUrl = $this->shopUrl . "/wp-json/wc/v3/subscriptions";
		$this->shopCredentials = base64_encode($shopUsername . ":" . $shopPassword);
		$this->curl = $curlService;
		$this->logger = $logger;
	}

	public function getShopUrls() : array {
		return array_map(function($shop) {
			return $shop['url'];
		}, $this->shops);
	}

	public function setShopDeletePreference($userId, bool $delete) {
		$this->config->setUserValue($userId, $this->appName, 'delete_shop_account', intval($delete));
	}

	public function shopEmailExists(string $shopEmail) : bool {
		return !empty($this->getUser($shopEmail));
	}

	public function validateShopEmailPostDelete(string $shopEmailPostDelete, string $cloudEmail) : void {
		if (!filter_var($shopEmailPostDelete, FILTER_VALIDATE_EMAIL)) {
			throw new Exception('Invalid Email Format.');
		}
		if ($shopEmailPostDelete === $cloudEmail) {
			throw new Exception('Murena.com email cannot be same as this account\'s email.');
		}
		if ($this->shopEmailExists($shopEmailPostDelete)) {
			throw new Exception('A Murena.com account already uses this e-mail address.');
		}
	}

	public function setShopEmailPostDeletePreference($userId, string $shopEmailPostDelete) {
		$this->config->setUserValue($userId, $this->appName, 'shop_email_post_delete', $shopEmailPostDelete);
	}

	public function getShopDeletePreference($userId) {
		return boolval($this->config->getUserValue($userId, $this->appName, 'delete_shop_account', false));
	}

	public function getShopEmailPostDeletePreference($userId) {
		$recoveryEmail = $this->config->getUserValue($userId, 'email-recovery', 'recovery-email');

		return $this->config->getUserValue($userId, $this->appName, 'shop_email_post_delete', $recoveryEmail);
	}

	public function getOrders(int $userId): ?array {
		$orders = [];
		foreach ($this->shops as $shop) {
			$orders[] = $this->callShopAPI($shop, self::ORDERS_ENDPOINT, 'GET', ['customer' => $userId]);
		}
		return $orders;
	}

	public function getUsers(string $searchTerm): ?array {
		try {
			$users = [];
			foreach ($this->shops as $shop) {
				$usersFromThisShop = $this->callShopAPI($shop, self::USERS_ENDPOINT, 'GET', ['search' => $searchTerm]);
				if (empty($usersFromThisShop)) {
					continue;
				}
				$users[] =  $usersFromThisShop[0];
			}
			
			return $users;
		} catch (Exception $e) {
			$this->logger->error('There was an issue querying shop for users');
			$this->logger->logException($e, ['app' => Application::APP_ID]);
		}
	}

	public function getUser(string $email) : ?array {
		$users = $this->getUsers($email);
		if (empty($users)) {
			return null;
		}
		return $users[0];
	}

	public function deleteUser(string $shopUrl, int $userId) : void {
		$shop = $this->shops[$shopUrl];
		$params = [
			'force' => true,
			'reassign' => $shop['reassign_user_id']
		];
		$deleteEndpoint = self::USERS_ENDPOINT . '/' . strval($userId);

		try {
			$answer = $this->callShopAPI($shop, $deleteEndpoint, 'DELETE', $params);

			if (!$answer['deleted']) {
				throw new Exception('Unknown error while deleting!');
			}
		} catch (Exception $e) {
			$this->logger->error('Error deleting user at WP with ID ' . $userId);
			$this->logger->logException($e, ['app' => Application::APP_ID]);
		}
	}

	public function updateUserEmailAndEmptyOIDC(string $shopUrl, int $userId, string $email) : void {
		$shop = $this->shops[$shopUrl];
		$updateEndpoint = self::USERS_ENDPOINT . '/' . strval($userId);

		$params = [
			'email' => $email,
			'openid-connect-generic-last-user-claim' => []
		];

		try {
			$answer = $this->callShopAPI($shop, $updateEndpoint, 'POST', $params);

			if ($answer['email'] !== $email) {
				throw new Exception('Unknown error while updating!');
			}
		} catch (Exception $e) {
			$this->logger->error('Error updating user email at WP with ID ' . $userId . ' and new email ' . $email);
			$this->logger->logException($e, ['app' => Application::APP_ID]);
		}
	}

	public function isUserOIDC(array $user) {
		return !empty($user['openid-connect-generic-last-user-claim']);
	}

	public function getSubscriptions(int $userId, string $status = 'any'): ?array {
		$subscriptions = [];
		foreach ($this->shops as $shop) {
			$subscriptions[] = $this->callShopAPI($shop, self::SUBSCRIPTIONS_ENDPOINT, 'GET', ['customer' => $userId , 'status' => $status]);
		}
		return $subscriptions;
	}
	
	private function callShopAPI(array $shop, string $endpoint, string $method, array $data = []) {
		if (empty($shop['url'])) {
			return [];
		}
		$shopCredentials = $shop['username'] . ':' . $shop['password'];
		$headers = [
			"cache-control: no-cache",
			"content-type: application/json",
			"Authorization: Basic " . $shopCredentials
		];

		if ($method === 'GET') {
			$answer = $this->curl->get($shop['url'] . $endpoint, $data, $headers);
		}

		if ($method === 'DELETE') {
			$answer = $this->curl->delete($shop['url'] . $endpoint, $data, $headers);
		}

		if ($method === 'POST') {
			$answer = $this->curl->post($shop['url'] . $endpoint, json_encode($data), $headers);
		}

		$answer = json_decode($answer, true);
		if (isset($answer['code']) && isset($answer['message'])) {
			throw new Exception($answer['message']);
		}

		return $answer;
	}

	
}
