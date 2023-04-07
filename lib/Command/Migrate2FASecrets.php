<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use OCA\TwoFactorTOTP\Db\TotpSecretMapper;
use OCP\Security\ICrypto;
use OCP\IDBConnection;
use OCP\IUserManager;
use OCP\IUser;
use OCA\EcloudAccounts\Db\SSOMapper;
use OCA\EcloudAccounts\Exception\DbConnectionParamsException;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Connection;

class Migrate2FASecrets extends Command {
	private TotpSecretMapper $totpSecretMapper;
	private SSOMapper $ssoMapper;
	private IDBConnection $dbConn;
	private Connection $ssoDbConn;
	private ICrypto $crypto;
	private IUserManager $userManager;
	private const TOTP_SECRET_TABLE = 'twofactor_totp_secrets';

	public function __construct(TotpSecretMapper $totpSecretMapper, IDBConnection $dbConn, ICrypto $crypto, SSOMapper $ssoMapper, IUserManager $userManager) {
		$this->totpSecretMapper = $totpSecretMapper;
		$this->ssoMapper = $ssoMapper;
		$this->userManager = $userManager;
		$this->dbConn = $dbConn;
		$this->crypto = $crypto;
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
			$dbName = $input->getOption('sso-db-name');
			$dbHost = $input->getOption('sso-db-host');
			$dbPort = $input->getOption('sso-db-port');
			$dbPassword = $input->getOption('sso-db-password');
			$dbUser = $input->getOption('sso-db-user');
			if (empty($dbName) || empty($dbHost) || empty($dbPort) || empty($dbPassword) || empty($dbUser)) {
				throw new DbConnectionParamsException('Invalid database parameters!');
			}

			$this->ssoDbConn = $this->getDatabaseConnection($dbName, $dbHost, $dbPort, $dbPassword, $dbUser);

			$usernames = [];
			$usernameList = $input->getOption('users');
			if (!empty($usernameList)) {
				$usernames = explode(',', $usernameList);
			}

			$ssoSecretEntries = $this->getSSOSecretEntries($usernames);
			foreach ($ssoSecretEntries as $username => $entry) {
				try {
					$this->ssoMapper->insertCredential($entry, $this->ssoDbConn);
				} catch(\Exception $e) {
					$output->writeln('Error inserting entry for user ' . $username . ' message: ' . $e->getMessage());
					continue;
				}
			}
			return 0;
		} catch (\Exception $e) {
			$output->writeln($e->getMessage());
			return 1;
		}
	}

	private function getSSOSecretEntries(array $usernames) : array {
		if (!empty($usernames)) {
			$entries = [];
			foreach ($usernames as $username) {
				$user = $this->userManager->get($username);
				if (!$user instanceof IUser) {
					continue;
				}
				$dbSecret = $this->totpSecretMapper->getSecret($user);
				$decryptedSecret = $this->crypto->decrypt($dbSecret->getSecret());
				$ssoUserId = $this->ssoMapper->getUserId($username, $this->ssoDbConn);
				$entries[$username] = $this->getSSOSecretEntry($decryptedSecret, $ssoUserId);
			}
			return $entries;
		}
		return $this->getAllSSOSecretEntries();
	}

	private function getAllSSOSecretEntries() : array {
		$entries = [];
		$qb = $this->dbConn->getQueryBuilder();
		$qb->select('user_id', 'secret')
			->from(self::TOTP_SECRET_TABLE);
		$result = $qb->execute();
		while ($row = $result->fetch()) {
			$username = (string) $row['user_id'];
			$secret = (string) $row['secret'];
			$decryptedSecret = $this->crypto->decrypt($secret);
			$ssoUserId = $this->ssoMapper->getUserId($username, $this->ssoDbConn);
			$entries[$username] = $this->getSSOSecretEntry($decryptedSecret, $ssoUserId);
		}
		return $entries;
	}


	private function getSSOSecretEntry(string $secret, string $ssoUserId) : array {
		$credentialEntry = [
			'ID' => $this->randomUUID(),
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

	private function getDatabaseConnection(string $dbName, string $dbHost, int $dbPort, string $dbPassword, string $dbUser) {
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

	/*
		From https://www.uuidgenerator.net/dev-corner/php
		As keycloak generates random UUIDs using the java.util.UUID class which is RFC 4122 compliant
	*/
	private function randomUUID($data = null) {
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
