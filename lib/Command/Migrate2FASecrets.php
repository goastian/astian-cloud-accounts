<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Command;

use OCA\EcloudAccounts\Db\TwoFactorMapper;
use OCA\EcloudAccounts\Service\SSOService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Migrate2FASecrets extends Command {
	private SSOService $ssoService;
	private TwoFactorMapper $twoFactorMapper;
	private OutputInterface $commandOutput;

	public function __construct(SSOService $ssoService, TwoFactorMapper $twoFactorMapper) {
		$this->ssoService = $ssoService;
		$this->twoFactorMapper = $twoFactorMapper;
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('ecloud-accounts:migrate-2fa-secrets')
			->setDescription('Migrates 2FA secrets to SSO database')
			->addOption(
				'users',
				null,
				InputOption::VALUE_OPTIONAL,
				'comma separated list of users',
				''
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
			$this->migrateUsers($usernames);
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
	private function migrateUsers(array $usernames = []) : void {
		$entries = $this->twoFactorMapper->getEntries($usernames);
		foreach ($entries as $entry) {
			try {
				$this->ssoService->migrateCredential($entry['username'], $entry['secret']);
			} catch (\Exception $e) {
				$this->commandOutput->writeln('Error inserting entry for user ' . $entry['username'] . ' message: ' . $e->getMessage());
				continue;
			}
		}
	}
}
