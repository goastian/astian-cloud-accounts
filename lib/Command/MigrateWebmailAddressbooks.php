<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use OCA\EcloudAccounts\Db\WebmailMapper;
use OCP\IUserManager;

class MigrateWebmailAddressbooks extends Command {
	private OutputInterface $commandOutput;
	private WebmailMapper $webmailMapper;
	private IUserManager $userManager;

	public function __construct(WebmailMapper $webmailMapper, IUserManager $userManager) {
		$this->webmailMapper = $webmailMapper;
		$this->userManager = $userManager;
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('ecloud-accounts:migrate-webmail-addressbooks')
			->setDescription('Migrates Webmail addressbooks to cloud')
			->addOption(
				'users',
				null,
				InputOption::VALUE_OPTIONAL,
				'comma separated list of users',
				''
			)
			->addOption(
				'limit',
				null,
				InputOption::VALUE_OPTIONAL,
				'Limit of users to migrate',
				null
			)
			->addOption(
				'offset',
				null,
				InputOption::VALUE_OPTIONAL,
				'Offset',
				0
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		try {
			$this->commandOutput = $output;
			$usernames = [];
			$usernameList = $input->getOption('users');
			if (!empty($usernameList)) {
				$usernames = explode(',', $usernameList);
			}
			$limit = (int) $input->getOption('limit');
			$offset = (int) $input->getOption('offset');
			$this->migrateUsers($limit, $offset, $usernames);
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
	private function migrateUsers(int $limit, int $offset = 0, array $usernames = []) : void {
		$users = [];
		if (!empty($usernames)) {
			$emails = [];
			foreach ($usernames as $username) {
				$user = $this->userManager->get($username);
				$email = $user->getEMailAddress();
				$emails[] = $email;
			}
			$users = $this->webmailMapper->getUsers($limit, $offset, $emails);
			$this->webmailMapper->migrateContacts($emails);
			return;
		}
		$users = $this->webmailMapper->getUsers($limit, $offset);
		$this->webmailMapper->migrateContacts($users);
	}
}
