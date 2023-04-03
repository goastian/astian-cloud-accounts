<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use OCA\TwoFactorTOTP\Db\TotpSecretMapper;
use OCP\Security\ICrypto;
use OCP\IDBConnection;
use OCA\EcloudAccounts\Db\SSOMapper;

class Migrate2FASecrets extends Command {
	private TotpSecretMapper $totpSecretMapper;
	private SSOMapper $SSOMapper;
	private IDBConnection $dbConn;
	private ICrypto $crypto;
	private const TOTP_TABLE = 'twofactor_totp_secrets';

	public function __construct(TotpSecretMapper $totpSecretMapper, IDBConnection $dbConn, ICrypto $crypto, SSOMapper $SSOMapper) {
		$this->totpSecretMapper = $totpSecretMapper;
		$this->SSOMapper = $SSOMapper;
		$this->conn = $conn;
		parent::__construct();
	}

	protected function configure(): void {
		$this
				->setName('ecloud-accounts:migrate-2fa-secrets')
				->setDescription('Migrates 2FA secrets to SSO database')
				->addArgument(
					'users',
					InputArgument::OPTIONAL,
					'comma separated list of users'
				);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		try {
			$users = explode(',', $input->getArgument('users'));
			$ssoSecretEntries = [];
			if (empty($users)) {
				$ssoSecretEntries = $this->getSSOSecretEntriesForAllUsers();
			} else {
				foreach ($users as $user) {
					$secret = $this->totpSecretMapper->getSecret($user);
					$ssoSecretEntries[] = $this->getSSOSecretEntry($user, );
				}
			}
			$this->SSOMapper->insertCredentials($ssoSecretEntries);
		} catch (\Exception $e) {
			$output->writeln($e->getMessage());
			return 1;
		}
	}

	private function getSSOSecretEntriesForAllUsers() : array {
		$entries = [];
		$query = $this->dbConn->getQueryBuilder();
		$qb->select('user_id', 'secret')
		   ->from(self::TOTP_TABLE);
		$result = $qb->execute();
		while ($row = $result->fetch()) {
			$userId = (string) $result['user_id'];
			$secret = (string) $result['secret'];
			$decryptedSecret = $this->crypto->decrypt($secret);
			$entries[] = $this->getSSOSecretEntry($userId, $decryptedSecret);
		}
		return $entries;
	}

	private function getSSOSecretEntry(string $userId, string $secret) : array {
		$SSOUserId = $this->SSOMapper->getUserId($userId);
		$credentialEntry = [
			'ID' => $this->randomUUID(),
			'SALT' => null,
			'USER_ID' => $SSOUserId,
			'USER_LABEL' => 'Murena Cloud 2FA',
			'SECRET_DATA' => [
				'value' => $secret
			],
			'CREATED_DATE' => round(microtime(true) * 1000),
			'CREDENTIAL_DATA' => [
				'subType' => 'nextcloud_totp',
				'period' => 30,
				'digits' => 6,
				'algorithm' => 'HmacSHA1',
			],
			'priority' => 10
		];
		return $credentialEntry;
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
