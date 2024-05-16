<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Command;

use OCA\EcloudAccounts\Db\BlacklistedDomainMapper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateBlacklistedDomains extends Command {

	private BlacklistedDomainMapper $blacklistedDomainMapper;
	private OutputInterface $commandOutput;

	public function __construct(BlacklistedDomainMapper $blacklistedDomainMapper) {
		$this->blacklistedDomainMapper = $blacklistedDomainMapper;
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('ecloud-accounts:migrate-blacklisted-domains')
			->setDescription('Migrate blacklisted domains to db table.');
	}
	protected function execute(InputInterface $input, OutputInterface $output): int {
		try {
			$this->commandOutput = $output;
			$this->migrateBlacklistedDomains();
		} catch (\Exception $e) {
		}
	}
	/**
	 * Migrate
	 *
	 * @return void
	 */
	private function migrateBlacklistedDomains() : void {
		$this->blacklistedDomainMapper->updateBlacklistedDomains();
		$this->commandOutput->writeln('Migrated blacklisted domains successfully.');
	}
}
