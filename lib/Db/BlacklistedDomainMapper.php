<?php

namespace OCA\EcloudAccounts\Db;

use OCA\EcloudAccounts\AppInfo\Application;
use OCP\IConfig;

class BlacklistedDomainMapper {
	private $config;

	public function __construct(IConfig $config) {
		$this->config = $config;
	}
	public function updateBlacklistedDomains() {
		$json_url = 'https://raw.githubusercontent.com/disposable/disposable-email-domains/master/domains.json';
		$json_data = file_get_contents($json_url);
		$this->config->setAppValue(Application::APP_ID, 'blacklisted_domains', $json_data);
	}
}
