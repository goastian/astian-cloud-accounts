<?php


namespace OCA\EcloudAccounts\Service;

use OCA\EcloudAccounts\AppInfo\Application;
use OCA\EcloudAccounts\Exception\SSOAdminAccessTokenException;
use OCA\EcloudAccounts\Exception\SSOAdminAPIException;
use OCP\IConfig;
use OCP\ILogger;
use OCP\L10N\IFactory;
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
	private IFactory $l10nFactory;

	private const ADMIN_TOKEN_ENDPOINT = '/auth/realms/master/protocol/openid-connect/token';
	private const USERS_ENDPOINT = '/users';
	private const CREDENTIALS_ENDPOINT = '/users/{USER_ID}/credentials';

	public function __construct($appName, IConfig $config, CurlService $curlService, ICrypto $crypto, IFactory $l10nFactory, ILogger $logger) {
		$this->appName = $appName;
		$this->config = $config;

		$ssoProviderUrl = $this->config->getSystemValue('oidc_login_provider_url', '');
		$ssoProviderUrlParts = explode('/auth', $ssoProviderUrl);
		$rootUrl = $ssoProviderUrlParts[0];
		$realmsPart = $ssoProviderUrlParts[1];

		$this->ssoConfig['admin_client_id'] = $this->config->getSystemValue('oidc_admin_client_id', '');
		$this->ssoConfig['admin_client_secret'] = $this->config->getSystemValue('oidc_admin_client_secret', '');
		$this->ssoConfig['admin_username'] = $this->config->getSystemValue('oidc_admin_username', '');
		$this->ssoConfig['admin_password'] = $this->config->getSystemValue('oidc_admin_password', '');
		$this->ssoConfig['admin_rest_api_url'] = $rootUrl . '/auth/admin' . $realmsPart;
		$this->ssoConfig['root_url'] = $rootUrl;
		$this->crypto = $crypto;
		$this->curl = $curlService;
		$this->logger = $logger;
		$this->l10nFactory = $l10nFactory;
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
		$url = $this->ssoConfig['admin_rest_api_url'] . self::USERS_ENDPOINT . '/' . $this->currentUserId;

		$data = [
			'credentials' => [$credentialEntry]
		];

		$this->logger->debug('migrateCredential calling SSO API with url: '. $url . ' and data: ' . print_r($data, true));
		$this->callSSOAPI($url, 'PUT', $data, 204);
	}

	public function deleteCredentials(string $username) : void {
		if(empty($this->currentUserId)) {
			$this->getUserId($username);
		}
		$credentialIds = $this->getCredentialIds();

		foreach ($credentialIds as $credentialId) {
			$url = $this->ssoConfig['admin_rest_api_url'] . self::CREDENTIALS_ENDPOINT;
			$url = str_replace('{USER_ID}', $this->currentUserId, $url);
			$url .= '/' . $credentialId;
			$this->logger->debug('deleteCredentials calling SSO API with url: '. $url);
			$this->callSSOAPI($url, 'DELETE', [], 204);
		}
	}

	private function getCredentialIds() : array {
		$url = $this->ssoConfig['admin_rest_api_url'] . self::CREDENTIALS_ENDPOINT;
		$url = str_replace('{USER_ID}', $this->currentUserId, $url);
		$this->logger->debug('getCredentialIds calling SSO API with url: '. $url);

		$credentials = $this->callSSOAPI($url, 'GET');

		if (empty($credentials) || !is_array($credentials)) {
			return [];
		}

		$credentials = array_filter($credentials, function ($credential) {
			if ($credential['type'] !== 'otp') {
				return false;
			}
			$credentialData = json_decode($credential['credentialData'], true);
			if ($credentialData['subType'] !== 'totp' || $credentialData['secretEncoding'] !== 'BASE32') {
				return false;
			}
			return true;
		});

		return array_map(function ($credential) {
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

		$secretData = '{"value":"' . $secret . '"}';
		$credentialData = '{"subType":"totp","period":30,"digits":6,"algorithm":"HmacSHA1","secretEncoding":"BASE32"}';
		$credentialEntry = [
			'userLabel' => $userLabel,
			'type' => 'otp',
			'secretData' => $secretData,
			'credentialData' => $credentialData
		];
		return $credentialEntry;
	}

	private function getUserId(string $username) : void {
		$usernameWithoutDomain = explode('@', $username)[0];
		$url = $this->ssoConfig['admin_rest_api_url'] . self::USERS_ENDPOINT . '?exact=true&username=' . $usernameWithoutDomain;
		$this->logger->debug('getUserId calling SSO API with url: '. $url);
		$users = $this->callSSOAPI($url, 'GET');
		if (empty($users) || !is_array($users) || !isset($users[0])) {
			throw new SSOAdminAPIException('Error: no user found for search with url: ' . $url);
		}
		$this->currentUserId = $users[0]['id'];
	}

	private function getAdminAccessToken() : void {
		if (!empty($this->adminAccessToken)) {
			return;
		}
		$adminAccessTokenRoute = $this->ssoConfig['root_url'] . self::ADMIN_TOKEN_ENDPOINT;
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
		$this->logger->debug('getAdminAccessToken calling SSO API with url: '. $adminAccessTokenRoute . ' and headers: ' . print_r($headers, true) . ' and body: ' . print_r($requestBody, true));
		$response = $this->curl->post($adminAccessTokenRoute, $requestBody, $headers);

		if ($this->curl->getLastStatusCode() !== 200) {
			$statusCode = strval($this->curl->getLastStatusCode());
			throw new SSOAdminAccessTokenException('Error getting Admin Access token. Status Code: ' . $statusCode);
		}
		$response = json_decode($response, true);

		if (!isset($response['access_token'])) {
			throw new SSOAdminAccessTokenException('Error: admin access token not set in response!');
		}
		$this->adminAccessToken = $response['access_token'];
	}

	private function callSSOAPI(string $url, string $method, array $data = [], int $expectedStatusCode = 200) :?array {
		if (empty($url)) {
			return null;
		}
		$this->getAdminAccessToken();
		$headers = [
			"cache-control: no-cache",
			"Content-Type: application/json",
			"Authorization: Bearer " . $this->adminAccessToken
		];

		if ($method === 'GET') {
			$answer = $this->curl->get($url, $data, $headers);
		}

		if ($method === 'DELETE') {
			$answer = $this->curl->delete($url, $data, $headers);
		}

		if ($method === 'POST') {
			$answer = $this->curl->post($url, $data, $headers);
		}

		if ($method === 'PUT') {
			$answer = $this->curl->put($url, $data, $headers);
		}

		$statusCode = $this->curl->getLastStatusCode();

		if ($statusCode !== $expectedStatusCode) {
			throw new SSOAdminAPIException('Error calling SSO API with url ' . $url . ' status code: ' . $statusCode);
		}

		$answer = json_decode($answer, true);
		return $answer;
	}
}
