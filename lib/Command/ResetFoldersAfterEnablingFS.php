<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Command;

use OCA\EcloudAccounts\Service\LDAPConnectionService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use OC_Util;
use OCP\Files\NotPermittedException;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUserManager;


class ResetFoldersAfterEnablingFS extends Command {
	private LDAPConnectionService $ldapService;
	private IUserManager $userManager;
	private IConfig $config;
	private IGroupManager $groupManager;

	public function __construct(LDAPConnectionService $ldapService, IConfig $config, IUserManager $userManager, IGroupManager $groupManager) {
		$this->ldapService = $ldapService;
		$this->config = $config;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
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

				$this->callSetupFS($username);
				$output->writeln("Call setup fs for user: $username");

				$this->addUserInGroup($username);
				$output->writeln("Add user $username in group.");

			}

			$output->writeln('Setup completed for eligible users.');
			return Command::SUCCESS;
		} catch (\Exception $e) {
			$output->writeln('Error: ' . $e->getMessage());
			return Command::FAILURE;
		}
	}

	private function callSetupFS(string $user): void {
		OC_Util::setupFS($user);
		
		//trigger creation of user home and /files folder
		$userFolder = \OC::$server->getUserFolder($user);

		try {
			// copy skeleton
			OC_Util::copySkeleton($user, $userFolder);
		} catch (NotPermittedException $ex) {
			// read only uses
		}
	}
	public function addUserInGroup($username) {
		$user = $this->userManager->get($username);
  		if (!$user) {
  			return false;
  		}
		$groupName = $this->config->getSystemValue("temporary_group_name");
		if (!$this->groupManager->groupExists($groupName)) {
			return false;
		}
		$group = $this->groupManager->get($groupName);
		$group->addUser($user);
		return true;
	}

}
