<?php

namespace OCA\EcloudAccounts\Db;


use OCP\IDBConnection;

class TwoFactorMapper {

	private IDBConnection $conn;
	private const TOTP_SECRET_TABLE = 'twofactor_totp_secrets';


	public function __construct(IDBConnection $conn) {
		$this->conn = $conn;
	}

	public function getEntries(array $usernames = []) : array {
		$entries = [];
		$qb = $this->conn->getQueryBuilder();
		$qb->select('user_id', 'secret')
			->from(self::TOTP_SECRET_TABLE);

		if (!empty($usernames)) {
			$qb->where('user_id IN (:usernames)')
				->setParameter('usernames', implode(',', $usernames));
		}

		$result = $qb->execute();
		while ($row = $result->fetch()) {
			$entry = [
				'username' => (string) $row['user_id'],
				'secret' => (string) $row['secret']
			];
			$entries[] = $entry;
		}
		return $entries;
	}

	public function getSecret(string $username) : string {
		$qb = $this->conn->getQueryBuilder();
		$qb->select('secret')
			->from(self::TOTP_SECRET_TABLE)
			->where('user_id = :username')
			->setParameter('username', $username);
		$result = $qb->execute();

		return (string) $result->fetchOne();
	}
}
