<?php

namespace OCA\EcloudAccounts\Db;

use OCP\IConfig;
use OCP\ILogger;
use Doctrine\DBAL\Connection;

class SSOMapper {
	private $config;
	private $logger;
	private const USER_ATTRIBUTE_TABLE = 'USER_ATTRIBUTE';
	private const CREDENTIAL_TABLE = 'CREDENTIAL';

	public function __construct(IConfig $config, ILogger $logger) {
		$this->config = $config;
		$this->logger = $logger;
	}

	public function getUserId(string $username, Connection $conn) : string {
		$qb = $conn->createQueryBuilder();
		$qb->select('USER_ID')
			->from(self::USER_ATTRIBUTE_TABLE)
			->where('NAME = "LDAP_ID"')
			->andWhere('VALUE = :username');
			
		$qb->setParameter('username', $username);
		$result = $qb->execute();
		return (string) $result->fetchOne();
	}

	public function insertCredential(array $entry, Connection $conn) {
		$qb = $conn->createQueryBuilder();
		$qb->insert(self::CREDENTIAL_TABLE)
			->values($entry)
			->execute();
	}
}
