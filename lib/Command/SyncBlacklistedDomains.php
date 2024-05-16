<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Command;

use OCA\EcloudAccounts\Db\BlacklistedDomainMapper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncBlacklistedDomains extends Command {
	private OutputInterface $commandOutput;
	private BlacklistedDomainMapper $blacklistedDomainMapper;

	public function __construct(BlacklistedDomainMapper $blacklistedDomainMapper) {
		$this->blacklistedDomainMapper = $blacklistedDomainMapper;
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('ecloud-accounts:sync-blacklisted-domains')
			->setDescription('Sync blacklisted domains');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->blacklistedDomainMapper->updateBlacklistedDomains();
	}
}
