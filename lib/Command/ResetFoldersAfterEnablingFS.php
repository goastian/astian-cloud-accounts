<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Command;

use OCA\EcloudAccounts\Service\FSService;
use OCA\EcloudAccounts\Service\LDAPConnectionService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ResetFoldersAfterEnablingFS extends Command {
	private LDAPConnectionService $ldapService;
	private FSService $fsservice;

	public function __construct(LDAPConnectionService $ldapService, FSService $fsservice) {
		$this->ldapService = $ldapService;
		$this->fsservice = $fsservice;
		parent::__construct();
	}
	/**
	 * run: occ ecloud-accounts:create-folders --date='2024-12-01 00:00:00'
	 * @return void
	 */
	protected function configure(): void {
		$this
			->setName('ecloud-accounts:create-folders')
			->setDescription('Create Folders for users created after a specific date')
			->addOption(
				'date',
				null,
				InputOption::VALUE_REQUIRED,
				'Date in YYYY-MM-DD HH-MM-SS format (e.g., 2024-12-01 00:00:00)',
				null
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$date = $input->getOption('date');

		if (!$date) {
			$output->writeln('Date option is required.');
			return Command::INVALID;
		}

		try {
			// Fetch users from LDAP
			$users = $this->ldapService->getUsersCreatedAfter($date);

			foreach ($users as $user) {
				if (!$user['username']) {
					continue;
				}

				$username = $user['username'];
				$output->writeln("Processing user $username");

				$this->fsservice->callSetupFS($username);
				$output->writeln("Call setup fs for user: $username");

				$output->writeln("Add user $username in group: ");
				$isAdded = $this->fsservice->addUserInGroup($username);
				$output->writeln($isAdded ? "YES": "NO");

			}

			$output->writeln('Setup completed for eligible users.');
			return Command::SUCCESS;
		} catch (\Exception $e) {
			$output->writeln('Error: ' . $e->getMessage());
			return Command::FAILURE;
		}
	}

}
