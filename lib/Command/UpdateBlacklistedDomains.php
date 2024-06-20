<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Command;

use OCA\EcloudAccounts\AppInfo\Application;
use OCA\EcloudAccounts\Service\BlackListService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use OCP\ILogger;

class UpdateBlacklistedDomains extends Command {
	private BlackListService $blackListService;
	private ILogger $logger;


	public function __construct(BlackListService $blackListService, ILogger $logger) {
		parent::__construct();
		$this->blackListService = $blackListService;
		$this->logger = $logger;
	}

	protected function configure() {
		$this->setName(Application::APP_ID.':update-blacklisted-domains')->setDescription('Update blacklisted domains');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		try {
			$this->blackListService->updateBlacklistedDomains();
			$output->writeln('Updated blacklisted domains for creation.');
		} catch (\Throwable $th) {
			$this->logger->error('Error while updating blacklisted domains. ' . $th->getMessage());
			$output->writeln('Error while updating blacklisted domains.');
		}
		return 1;
	}
}
