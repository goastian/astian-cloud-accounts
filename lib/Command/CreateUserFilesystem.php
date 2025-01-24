<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Command;

use OCA\EcloudAccounts\Service\FilesystemService;
use OCA\EcloudAccounts\Service\LDAPConnectionService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateUserFilesystem extends Command {
	private LDAPConnectionService $ldapService;
	private FilesystemService $fsservice;

	public function __construct(LDAPConnectionService $ldapService, FilesystemService $fsservice) {
		$this->ldapService = $ldapService;
		$this->fsservice = $fsservice;
		parent::__construct();
	}
	/**
	 * run: occ ecloud-accounts:create-folders --date='2024-12-01 00:00:00'
	 *       occ ecloud-accounts:create-folders --user_ids='user1,user2,user3'
	 * @return void
	 */
	protected function configure(): void {
		$this
			->setName('ecloud-accounts:create-folders')
			->setDescription('Create Folders for users created after a specific date or for specific user IDs')
			->addOption(
				'date',
				null,
				InputOption::VALUE_REQUIRED,
				'Date in YYYY-MM-DD HH-MM-SS format (e.g., 2024-12-01 00:00:00)',
				null
			)
			->addOption(
				'user_ids',
				null,
				InputOption::VALUE_REQUIRED,
				'Comma-separated list of user IDs to process (e.g., user1,user2,user3)',
				null
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$date = $input->getOption('date');
		$userIdsOption = $input->getOption('user_ids');

		if (!$date && !$userIdsOption) {
			$output->writeln('Either the --date or --user_ids option is required.');
			return Command::INVALID;
		}

		try {
			$users = [];
			if ($date) {
				// Fetch users from LDAP created after the specified date
				$output->writeln("Fetching users created after $date...");
				$users = $this->ldapService->getUsersCreatedAfter($date);
				
			} elseif ($userIdsOption) {
				// Process specific user IDs
				$userIds = array_map('trim', explode(',', $userIdsOption));
				foreach ($userIds as $userId) {
					$users[] = ['username' => $userId];
				}
				$output->writeln('Processing specified user IDs: ' . implode(', ', $userIds));
			}
			$output->writeln('Setup started for all eligible users.');
			foreach ($users as $user) {
				if (!$user['username']) {
					continue;
				}

				$username = $user['username'];
				$output->writeln("$username Processing user...");

				$isUserInGroup = $this->fsservice->addUserInFilesEnabledGroup($username);
				$output->writeln("Checking if user in group");
				if (!$isUserInGroup) {
					$output->writeln("user not in group. Adding...");
					$result = $this->fsservice->addUserInFilesEnabledGroup($username);
					$output->writeln($result ? "Added in group sucessfully.": "Failed to add in Group");
				}
				$output->writeln("Setup FS for user...");
				$isSetupCompleted = $this->fsservice->callSetupFS($username);
				$output->writeln($isSetupCompleted ? "User Setup sucessfully": "Failed to setup");
				
				$output->writeln("--End--");
			}

			$output->writeln('Setup completed for all eligible users.');
			return Command::SUCCESS;
		} catch (\Exception $e) {
			$output->writeln('Error: ' . $e->getMessage());
			return Command::FAILURE;
		}
	}

}
