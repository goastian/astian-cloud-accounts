<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Command;

use OCA\EcloudAccounts\AppInfo\Application;
use OCA\EcloudAccounts\Service\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateBlacklistedDomains extends Command {
	private UserService $userService;

	public function __construct(UserService $userService) {
		parent::__construct();
		$this->userService = $userService;
	}

	protected function configure() {
		$this->setName(Application::APP_ID.':migrate-blacklisted-domains')->setDescription('Migrate blacklisted domains');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->userService->updateBlacklistedDomains();
		$output->writeln('Updated blacklisted domains for creation.');
		return 1;
	}
}
