<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Command;

use OCA\EcloudAccounts\AppInfo\Application;
use OCA\EcloudAccounts\Service\BlackListService;
use OCA\EcloudAccounts\Service\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateBlacklistedDomains extends Command {
	private UserService $userService;
	private BlackListService $blackListService;

	public function __construct(UserService $userService, BlackListService $blackListService) {
		parent::__construct();
		$this->userService = $userService;
		$this->blackListService = $blackListService;
	}

	protected function configure() {
		$this->setName(Application::APP_ID.':update-blacklisted-domains')->setDescription('Update blacklisted domains');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		try {
			$this->blackListService->updateBlacklistedDomains();
			$output->writeln('Updated blacklisted domains for creation.');
		} catch (\Throwable $th) {
			$output->writeln('Error while updating blacklisted domains.');
		}
		return 1;
	}
}
