<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Command;

use OCA\EcloudAccounts\Db\BlacklistedDomainMapper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateBlacklistedDomains extends Command {
	private OutputInterface $commandOutput;
	private BlacklistedDomainMapper $blacklistedDomainMapper;

	public function __construct(BlacklistedDomainMapper $blacklistedDomainMapper) {
		$this->blacklistedDomainMapper = $blacklistedDomainMapper;
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('ecloud-accounts:migrate-blacklisted-domains')
			->setDescription('Migrates 2FA secrets to SSO database');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		try {
			$this->commandOutput = $output;
			$this->migrateBlacklistedDomains();
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
	private function migrateBlacklistedDomains() : void {
		try {
			$this->blacklistedDomainMapper->updateBlacklistedDomains();
		} catch (\Exception $e) {
			$this->commandOutput->writeln('Error migrating domains. message: ' . $e->getMessage());
		}
	}
}
