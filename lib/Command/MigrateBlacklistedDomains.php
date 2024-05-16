<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Command;

use OCA\EcloudAccounts\Db\WebmailMapper;
use Symfony\Component\Console\Command\Command;
use OCA\EcloudAccounts\Db\BlacklistedDomainMapper;

class MigrateWebmailAddressbooks extends Command {

	private BlacklistedDomainMapper $blacklistedDomainMapper;

	public function __construct(BlacklistedDomainMapper $blacklistedDomainMapper) {
		$this->blacklistedDomainMapper = $blacklistedDomainMapper;
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('ecloud-accounts:blacklisted-domains')
			->setDescription('Migrate blacklisted domains to db table.');
	}
	protected function execute(): int {
		try {
			$this->migrateBlacklistedDomains();
			return 0;
		} catch (\Exception $e) {
			return 1;
		}
	}

	/**
	 * Migrate 
	 *
	 * @return void
	 */
	private function migrateBlacklistedDomains() : void {
		$this->blacklistedDomainMapper->updateBlacklistedDomains();
	}
}
