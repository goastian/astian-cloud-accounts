<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use OCP\Security\ICrypto;
use OCP\IDBConnection;
use OCP\IUserManager;
use OCP\IUser;
use OCP\IConfig;
use OCA\EcloudAccounts\Db\SSOMapper;
use OCA\EcloudAccounts\Exception\DbConnectionParamsException;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Connection;

class Migrate2FASecrets extends Command {
	private SSOMapper $ssoMapper;
	private IDBConnection $dbConn;
	private Connection $ssoDbConn;
	private ICrypto $crypto;
	private IUserManager $userManager;
	private OutputInterface $commandOutput;
	private IConfig $config;
	private const TOTP_SECRET_TABLE = 'twofactor_totp_secrets';
	private const USER_LABELS = [
		'en' => 'Murena Cloud 2FA',
		'es' => 'Murena Cloud 2FA',
		'de' => 'Murena Cloud 2FA',
		'it' => 'Murena Cloud 2FA',
		'fr' => 'Murena Cloud 2FA',
	];

	public function __construct(IDBConnection $dbConn, ICrypto $crypto, SSOMapper $ssoMapper, IUserManager $userManager, IConfig $config) {
		$this->ssoMapper = $ssoMapper;
		$this->userManager = $userManager;
		$this->dbConn = $dbConn;
		$this->crypto = $crypto;
		$this->config = $config;
		parent::__construct();
	}

	protected function configure(): void {
		$this
				->setName('ecloud-accounts:migrate-2fa-secrets')
				->setDescription('Migrates 2FA secrets to SSO database')
				->addOption(
					'users',
					null,
					InputOption::VALUE_OPTIONAL,
					'comma separated list of users',
					''
				)
				->addOption(
					'sso-db-name',
					null,
					InputOption::VALUE_REQUIRED,
					'SSO database name',
				)
				->addOption(
					'sso-db-user',
					null,
					InputOption::VALUE_REQUIRED,
					'SSO database user',
				)
				->addOption(
					'sso-db-password',
					null,
					InputOption::VALUE_REQUIRED,
					'SSO database password',
				)
				->addOption(
					'sso-db-host',
					null,
					InputOption::VALUE_REQUIRED,
					'SSO database host',
				)
				->addOption(
					'sso-db-port',
					null,
					InputOption::VALUE_REQUIRED,
					'SSO database port',
					3306
				);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		try {
			$this->commandOutput = $output;
			$dbName = $input->getOption('sso-db-name');
			$dbHost = $input->getOption('sso-db-host');
			$dbPort = $input->getOption('sso-db-port');
			$dbPassword = $input->getOption('sso-db-password');
			$dbUser = $input->getOption('sso-db-user');
			$this->ssoDbConn = $this->getDatabaseConnection($dbName, $dbHost, $dbPort, $dbPassword, $dbUser);
			
			$usernames = [];
			$usernameList = $input->getOption('users');
			if (!empty($usernameList)) {
				$usernames = explode(',', $usernameList);
			}
			$this->migrateUsers($usernames);
			return 0;
		} catch (\Exception $e) {
			$this->commandOutput->writeln($e->getMessage());
			return 1;
		}
	}

	/**
	 * Migrate user secrets to the SSO database
	 *
	 * @return void
	 */
	private function migrateUsers(array $usernames = []) : void {
		$entries = [];
		$qb = $this->dbConn->getQueryBuilder();
		$qb->select('user_id', 'secret')
			->from(self::TOTP_SECRET_TABLE);

		if (!empty($usernames)) {
			$qb->where('user_id IN (:usernames)')
				->setParameter('usernames', implode(',', $usernames));
		}

		$result = $qb->execute();
		while ($row = $result->fetch()) {
			try {
				$username = (string) $row['user_id'];
				if (!$this->userManager->get($username) instanceof IUser) {
					throw new \Exception('No user found in nextcloud with given username');
				}
				
				$secret = (string) $row['secret'];
				$decryptedSecret = $this->crypto->decrypt($secret);
				$ssoUserId = $this->ssoMapper->getUserId($username, $this->ssoDbConn);
				if (empty($ssoUserId)) {
					throw new \Exception('Does not exist in SSO database');
				}

				$language = $this->config->getUserValue($uid, 'core', 'lang', 'en');
				if (!array_key_exists($language, self::USER_LABELS)) {
					$language = 'en';
				}
				$entry = $this->getSSOSecretEntry($decryptedSecret, $ssoUserId, $language);
				$this->ssoMapper->insertCredential($entry, $this->ssoDbConn);
			} catch(\Exception $e) {
				$this->commandOutput->writeln('Error inserting entry for user ' . $username . ' message: ' . $e->getMessage());
				continue;
			}
		}
	}

	/**
	 * Create secret entry compatible with Keycloak schema
	 *
	 * @return array
	 */

	private function getSSOSecretEntry(string $secret, string $ssoUserId, string $language) : array {
		// Create the random UUID from the sso user ID so multiple entries of same credential do not happen
		$id = $this->randomUUID(substr($ssoUserId, 0, 16));

		$userLabel = self::USER_LABELS[$language];
		$credentialEntry = [
			'ID' => $id,
			'USER_ID' => $ssoUserId,
			'USER_LABEL' => 'Murena Cloud 2FA',
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

	/**
	 * Attempt to connect to a non-NC database
	 *
	 * @return Connection
	 */
	private function getDatabaseConnection(string $dbName, string $dbHost, int $dbPort, string $dbPassword, string $dbUser) : Connection {
		if (empty($dbName) || empty($dbHost) || empty($dbPort) || empty($dbPassword) || empty($dbUser)) {
			throw new DbConnectionParamsException('Invalid database parameters!');
		}
		
		$params = [
			'dbname' => $dbName,
			'user' => $dbUser,
			'password' => $dbPassword,
			'host' => $dbHost,
			'port' => $dbPort,
			'driver' => 'pdo_mysql'
		];

		return  DriverManager::getConnection($params);
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
