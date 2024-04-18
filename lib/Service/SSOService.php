<?php


namespace OCA\EcloudAccounts\Service;

use Exception;
use OCA\EcloudAccounts\AppInfo\Application;
use OCP\IConfig;
use OCP\ILogger;
use OCP\Security\ICrypto;

class SSOService {
	private IConfig $config;
	private string $appName;
	private CurlService $curl;
	private ILogger $logger;
	private array $ssoConfig = [];
	private string $adminAccessToken;
	private string $currentUserId;
	private ICrypto $crypto;

	private const ADMIN_TOKEN_ENDPOINT = '/auth/realms/master/protocol/openid-connect/token';
	private const USERS_ENDPOINT = '/users';
	private const CREDENTIALS_ENDPOINT = '/users/{USER_ID}/credentials';

	public function __construct($appName, IConfig $config, CurlService $curlService, ICrypto $crypto, ILogger $logger) {
		$this->appName = $appName;
		$this->config = $config;
		$this->ssoConfig['admin_client_id'] = $this->config->getSystemValue('oidc_admin_client_id', '');
		$this->ssoConfig['admin_client_secret'] = $this->config->getSystemValue('oidc_admin_client_secret', '');
		$this->ssoConfig['admin_username'] = $this->config->getSystemValue('oidc_admin_username', '');
		$this->ssoConfig['admin_password'] = $this->config->getSystemValue('oidc_admin_password', '');
		$this->ssoConfig['provider_url'] = $this->config->getSystemValue('oidc_login_provider_url', '');
		$this->ssoConfig['root_url'] = explode('/auth', $this->ssoConfig['provider_url'])[0];
		$this->crypto = $crypto;
		$this->curl = $curlService;
		$this->logger = $logger;
	}

	public function shouldSync2FA() : bool {
		return $this->config->getSystemValue('oidc_admin_sync_2fa', false);
	}

	public function migrateCredential(string $username, string $secret) : void {
		if(empty($this->currentUserId)) {
			$this->getUserId($username);
		}
		$this->deleteCredentials($username);

		$decryptedSecret = $this->crypto->decrypt($secret);
		$language = $this->config->getUserValue($username, 'core', 'lang', 'en');
		$credentialEntry = $this->getCredentialEntry($decryptedSecret, $language);
		$url = $this->ssoConfig['provider_url'] . self::USERS_ENDPOINT . '/' . $this->currentUserId;

		$data = [
			'credentials' => [$credentialEntry]
		];
		$this->callSSOAPI($url, 'PUT', $data, 204);
	}

	public function deleteCredentials(string $username) : void {
		if(empty($this->currentUserId)) {
			$this->getUserId($username);
		}
		$credentialIds = $this->getCredentialIds();

		foreach ($credentialIds as $credentialId) {
			$url = $this->ssoConfig['provider_url'] . self::CREDENTIALS_ENDPOINT;
			$url = str_replace($url, '{USER_ID}', $this->currentUserId);
			$url .= '/' . $credentialId;

			$this->callSSOAPI($url, 'DELETE', [], 204);
		}
	}

	private function getCredentialIds() : array {
		$url = $this->ssoConfig['provider_url'] . self::CREDENTIALS_ENDPOINT;
		$url = str_replace($url, '{USER_ID}', $this->currentUserId);
		$credentials = $this->callSSOAPI($url, 'GET');

		if (empty($credentials) || !is_array($credentials)) {
			return [];
		}

		$credentials = array_filter($credentials, function($credential) {
			if ($credential['type'] !== 'otp') {
				return false;
			}
			$credentialData = json_decode($credential['credentialData'], true);
			if ($credentialData['subType'] !== 'totp' || $credentialData['secretEncoding'] !== 'BASE32') {
				return false;
			}
			return true;
		});

		return array_map(function($credential) {
			return $credential['id'];
		}, $credentials);
	}

	/**
	 * Create secret entry compatible with Keycloak schema
	 *
	 * @return array
	 */

	 private function getCredentialEntry(string $secret, string $language) : array {
		$l10n = $this->l10nFactory->get(Application::APP_ID, $language);
		$userLabel = $l10n->t('Murena Cloud 2FA');

		$credentialEntry = [
			'userLabel' => $userLabel,
			'type' => 'otp',
			'secretData' => json_encode([
				'value' => $secret
			]),
			'credentialData' => json_encode([
				'subType' => 'totp',
				'period' => 30,
				'digits' => 6,
				'algorithm' => 'HmacSHA1',
				'secretEncoding' => 'BASE32',
			]),
		];

		foreach ($credentialEntry as $key => &$value) {
			$value = "'" . $value . "'";
		}
		return $credentialEntry;
	}

	private function getUserId(string $username) : void {
		$usernameWithoutDomain = explode('@', $username)[0];
		$url = $this->ssoConfig['provider_url'] . self::USERS_ENDPOINT . '?exact=true&username=' . $usernameWithoutDomain;
		$users = $this->callSSOAPI($url, 'GET');
		if (empty($users) || !is_array($users) || !isset($users[0])) {
			throw new Exception();
		}
		$this->currentUserId = $users[0]['username'];
	}

	private function getAdminAccessToken() : void {
		if (!empty($this->adminAccessToken)) {
			return;
		}
		$adminAccessTokenRoute = $this->ssoConfig['provider_url'] . self::ADMIN_TOKEN_ENDPOINT;
		$requestBody = [
			'username' => $this->ssoConfig['admin_username'],
			'password' => $this->ssoConfig['admin_password'],
			'client_id' => $this->ssoConfig['admin_client_id'],
			'client_secret' => $this->ssoConfig['admin_client_secret'],
			'grant_type' => 'password'
		];

		$headers = [
			'Content-Type: application/x-www-form-urlencoded'
		];
		$response = $this->curl->post($adminAccessTokenRoute, $requestBody, $headers);

		if (!$this->curl->getLastStatusCode() === 200) {
			throw new Exception();
		}
		$response = json_decode($response, true);

		if (!isset($response['access_token'])) {
			throw new Exception();
		}
		$this->adminAccessToken = $response['access_token'];
	}

	private function callSSOAPI(string $url, string $method, array $data = [], int $expectedStatusCode = 200) :?array {
		if (empty($url)) {
			return null;
		}
		$accessToken = $this->getAdminAccessToken();
		$headers = [
			"cache-control: no-cache",
			"content-type: application/json",
			"Authorization: Bearer " . $accessToken
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

		$statusCode = $this->curl->getLastStatusCode();

		if ($statusCode !== $expectedStatusCode) {
			throw new Exception();
		}

		$answer = json_decode($answer, true);
		return $answer;
	}
}
