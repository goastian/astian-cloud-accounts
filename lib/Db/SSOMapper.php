<?php

namespace OCA\EcloudAccounts\Db;

use OCP\IConfig;
use OCP\ILogger;
use OCA\EcloudAccounts\Exception\DbConnectionParamsException;
use Doctrine\DBAL\DriverManager;
use Throwable;

class SSOMapper {
	private $config;
	private $conn;
	private $logger;

	public function __construct(IConfig $config, ILogger $logger) {
		$this->config = $config;
		$this->logger = $logger;
		$this->initConnection();
	}

	private function initConnection() {
		$params = $this->getConnectionParams();
		$this->conn = DriverManager::getConnection($params);
	}

	private function isDbConfigValid($config) : bool {
		if (!$config || !is_array($config)) {
			return false;
		}
		return isset($config['db_name'])
			&& isset($config['db_user'])
			&& isset($config['db_password'])
			&& isset($config['db_host'])
			&& isset($config['db_port']) ;
	}

	private function getConnectionParams() {
		$config = $this->config->getSystemValue('sso_database');
		
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

	public function getUserId(string $username) : string {
			$query = $this->conn->createQueryBuilder();
			$query->select('USER_ID')
				->from('USER_ATTRIBUTE')
				->where($query->expr()->eq('NAME', 'LDAP_ID'))
				->where($query->expr()->eq('VALUE', $query->createParameter('username')));
			
			$query->setParameter('username', $username);

			$result = $query->execute();
			$SSOUserId = (string) $result->fetchColumn();	
			return $SSOUserId;	
	}	

	public function insertCredentials(array $entries) {
		
	}
}
