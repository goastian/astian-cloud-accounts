<?php


namespace OCA\EcloudAccounts\Service;

use Exception;
use OCP\IConfig;
use OCP\ILogger;
use OCA\EcloudAccounts\AppInfo\Application;

class ShopAccountService {
	private $config;
	private $appName;
	private $curl;
	private $logger;

	public function __construct($appName, IConfig $config, CurlService $curlService, ILogger $logger) {
		$this->config = $config;
		$this->appName = $appName;

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

	public function getShopUrl() {
		return $this->shopUrl;
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
		try {
			return $this->callShopAPI($this->shopOrdersUrl, 'GET', ['customer' => $userId]);
		} catch (Exception $e) {
			$this->logger->error('There was an issue querying shop for orders for user ' . strval($userId));
			$this->logger->logException($e, ['app' => Application::APP_ID]);
		}
		return null;
	}

	public function getUsers(string $searchTerm): ?array {
		try {
			return $this->callShopAPI($this->shopUserUrl, 'GET', ['search' => $searchTerm]);
		} catch (Exception $e) {
			$this->logger->error('There was an issue querying shop for users');
			$this->logger->logException($e, ['app' => Application::APP_ID]);
		}
		return null;
	}

	public function getUser(string $email) : ?array {
		$users = $this->getUsers($email);
		if (empty($users)) {
			return null;
		}
		return $users[0];
	}

	public function deleteUser(int $userId) : void {
		$params = [
			'force' => true,
			'reassign' => $this->shopReassignUserId
		];
		$deleteUrl = $this->shopUserUrl . '/' . strval($userId);

		try {
			$answer = $this->callShopAPI($deleteUrl, 'DELETE', $params);

			if (!$answer['deleted']) {
				throw new Exception('Unknown error while deleting!');
			}
		} catch (Exception $e) {
			$this->logger->error('Error deleting user at WP with ID ' . $userId);
			$this->logger->logException($e, ['app' => Application::APP_ID]);
		}
	}

	public function updateUserEmailAndEmptyOIDC(int $userId, string $email) : void {
		$updateUrl = $this->shopUserUrl . '/' . strval($userId);

		$params = [
			'email' => $email,
			'openid-connect-generic-last-user-claim' => []
		];

		try {
			$answer = $this->callShopAPI($updateUrl, 'POST', $params);

			if ($answer['email'] !== $email) {
				throw new Exception('Unknown error while updating!');
			}
		} catch (Exception $e) {
			$this->logger->error('Error updating user email at WP with ID ' . $userId . ' and new email ' . $email);
			$this->logger->logException($e, ['app' => Application::APP_ID]);
		}
	}

	private function callShopAPI(string $url, string $method, array $data = []) {
		if (empty($this->shopUrl)) {
			return [];
		}
		$headers = [
			"cache-control: no-cache",
			"content-type: application/json",
			"Authorization: Basic " . $this->shopCredentials
		];

		if ($method === 'GET') {
			$answer = $this->curl->get($url, $data, $headers);
		}

		if ($method === 'DELETE') {
			$answer = $this->curl->delete($url, $data, $headers);
		}

		if ($method === 'POST') {
			$answer = $this->curl->post($url, json_encode($data), $headers);
		}

		$answer = json_decode($answer, true);
		if (isset($answer['code']) && isset($answer['message'])) {
			throw new Exception($answer['message']);
		}

		return $answer;
	}

	public function isUserOIDC(array $user) {
		return !empty($user['openid-connect-generic-last-user-claim']);
	}

	public function getSubscriptions(int $userId, string $status): ?array {
		try {
			return $this->callShopAPI($this->subscriptionUrl, 'GET', ['customer' => $userId , 'status' => $status]);
		} catch (Exception $e) {
			$this->logger->error('There was an issue querying shop for subscriptions for user ' . strval($userId));
			$this->logger->logException($e, ['app' => Application::APP_ID]);
		}
		return null;
	}
}
