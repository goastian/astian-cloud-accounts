<?php

namespace OCA\EcloudAccounts\Db;

use OCP\IConfig;
use OCP\ILogger;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Connection;
use OCA\EcloudAccounts\AppInfo\Application;
use OCP\IUserManager;
use OCP\Security\ICrypto;
use OCP\IUser;
use OCA\EcloudAccounts\Exception\DbConnectionParamsException;
use OCP\L10N\IFactory;

class SSOMapper {
	private IConfig $config;
	private ILogger $logger;
	private Connection $conn;
	private IUserManager $userManager;
	private ICrypto $crypto;
	private IFactory $l10nFactory;

	private const USER_ATTRIBUTE_TABLE = 'USER_ATTRIBUTE';
	private const CREDENTIAL_TABLE = 'CREDENTIAL';
	private const SSO_CONFIG_KEY = 'keycloak';

	public function __construct(IConfig $config, IUserManager $userManager, ILogger $logger, ICrypto $crypto, IFactory $l10nFactory) {
		$this->l10nFactory = $l10nFactory;
		$this->config = $config;
		$this->logger = $logger;
		$this->userManager = $userManager;
		$this->crypto = $crypto;
		$this->initConnection();
	}

	public function isSSOEnabled() : bool {
		return isset($this->conn);
	}

	public function getUserId(string $username) : string {
		$qb = $this->conn->createQueryBuilder();
		$qb->select('USER_ID')
			->from(self::USER_ATTRIBUTE_TABLE)
			->where('NAME = "LDAP_ID"')
			->andWhere('VALUE = :username');
			
		$qb->setParameter('username', $username);
		$result = $qb->execute();
		return (string) $result->fetchOne();
	}

	public function deleteCredentials(string $username) {
		$userId = $this->getUserId($username);
		$qb = $this->conn->createQueryBuilder();
		$qb->delete(self::CREDENTIAL_TABLE)
			->where('USER_ID = :username')
			->andWhere('TYPE = "otp"')
			->andWhere('CREDENTIAL_DATA LIKE "%\"subType\":\"nextcloud_totp\"%" OR CREDENTIAL_DATA LIKE "%\"subType\":\"totp\"%"')
			->setParameter('username', $userId)
			->execute();
	}

	public function migrateCredential(string $username, string $secret) {
		if (!$this->userManager->get($username) instanceof IUser) {
			throw new \Exception('No user found in nextcloud with given username');
		}

		$decryptedSecret = $this->crypto->decrypt($secret);
		$ssoUserId = $this->getUserId($username);
		if (empty($ssoUserId)) {
			throw new \Exception('Does not exist in SSO database');
		}

		$language = $this->config->getUserValue($username, 'core', 'lang', 'en');

		// Only one 2FA device at  a time
		$this->deleteCredentials($username);

		$entry = $this->getCredentialEntry($decryptedSecret, $ssoUserId, $language);
		$this->insertCredential($entry);
	}

	public function insertCredential(array $entry) : void {
		$qb = $this->conn->createQueryBuilder();
		$qb->insert(self::CREDENTIAL_TABLE)
			->values($entry)
			->execute();
	}

	/**
	 * Create secret entry compatible with Keycloak schema
	 *
	 * @return array
	 */

	private function getCredentialEntry(string $secret, string $ssoUserId, string $language) : array {
		// Create the random UUID from the sso user ID so multiple entries of same credential do not happen
		$id = $this->randomUUID(substr($ssoUserId, 0, 16));

		$l10n = $this->l10nFactory->get(Application::APP_ID, $language);
		$userLabel = $l10n->t('Murena Cloud 2FA');

		$credentialEntry = [
			'ID' => $id,
			'USER_ID' => $ssoUserId,
			'USER_LABEL' => $userLabel,
			'TYPE' => 'otp',
			'SECRET_DATA' => json_encode([
				'value' => $secret
			]),
			'CREDENTIAL_DATA' => json_encode([
				'subType' => 'nextcloud_totp',
				'period' => 30,
				'digits' => 6,
				'algorithm' => 'HmacSHA1',
			]),
		];

		foreach ($credentialEntry as $key => &$value) {
			$value = "'" . $value . "'";
		}
		$credentialEntry['CREATED_DATE'] = round(microtime(true) * 1000);
		$credentialEntry['PRIORITY'] = 10;

		return $credentialEntry;
	}

	private function initConnection() : void {
		try {
			$params = $this->getConnectionParams();
			$this->conn = DriverManager::getConnection($params);
		} catch (Throwable $e) {
			$this->logger->error('Error connecting to Keycloak database: ' . $e->getMessage());
		}
	}

	private function isDbConfigValid($config) : bool {
		if (!$config || !is_array($config)) {
			return false;
		}
		if (!isset($config['db_port'])) {
			$config['db_port'] = 3306;
		}

		return isset($config['db_name'])
			&& isset($config['db_user'])
			&& isset($config['db_password'])
			&& isset($config['db_host'])
			&& isset($config['db_port']) ;
	}

	private function getConnectionParams() : array {
		$config = $this->config->getSystemValue(self::SSO_CONFIG_KEY);
		
		if (!$this->isDbConfigValid($config)) {
			throw new DbConnectionParamsException('Invalid SSO database configuration!');
		}

		$params = [
			'dbname' => $config['db_name'],
			'user' => $config['db_user'],
			'password' => $config['db_password'],
			'host' => $config['db_host'],
			'port' => $config['db_port'],
			'driver' => 'pdo_mysql'
		];
		return $params;
	}

		/**
		 *	From https://www.uuidgenerator.net/dev-corner/php
		 *	As keycloak generates random UUIDs using the java.util.UUID class which is RFC 4122 compliant
		 *
		 *   @return string
		 */
	private function randomUUID($data = null) : string {
		// Generate 16 bytes (128 bits) of random data or use the data passed into the function.
		$data = $data ?? random_bytes(16);
		assert(strlen($data) == 16);
	
		// Set version to 0100
		$data[6] = chr(ord($data[6]) & 0x0f | 0x40);
		// Set bits 6-7 to 10
		$data[8] = chr(ord($data[8]) & 0x3f | 0x80);
	
		// Output the 36 character UUID.
		return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
	}
}
