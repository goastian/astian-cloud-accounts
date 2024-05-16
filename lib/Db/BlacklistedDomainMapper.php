<?php

namespace OCA\EcloudAccounts\Db;

use Exception;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\ILogger;

class BlacklistedDomainMapper {
	private $db;
	private $config;
	private $logger;

	public function __construct(IDBConnection $db, IConfig $config, ILogger $logger) {
		$this->db = $db;
		$this->config = $config;
		$this->logger = $logger;
	}
	public function updateBlacklistedDomains() {
		$json_url = 'https://raw.githubusercontent.com/disposable/disposable-email-domains/master/domains.json';
		$json_data = file_get_contents($json_url);
		
		$this->config->setAppValue('core', 'blacklisted_domains', $json_data);

		$blacklisted_domains = json_decode($json_data, true);
		try {
			$dbTablePrefix = $this->config->getSystemValue('dbtableprefix', '');
			$params = [];
			$blacklistedDomainTable = $dbTablePrefix . 'blacklisted_domains';
			$query = 'INSERT INTO ' . $blacklistedDomainTable . ' (domain) VALUES (?)';

			foreach ($blacklisted_domains as $domain) {
				$params[] = $domain;
				$this->db->executeQuery($query, $params);
			}
			
		} catch (Exception $e) {
			$this->logger->error('Error updating mailbox usage! ' . $e->getMessage());
		}
	}
}
