<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Command;

use OCA\EcloudAccounts\Service\LDAPConnectionService;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ResetUserSessions extends Command {
	private LDAPConnectionService $ldapService;
	private IDBConnection $db;
	
	/**
	 * Construct method
	 * @param \OCA\EcloudAccounts\Service\LDAPConnectionService $ldapService
	 * @param \OCP\IDBConnection $db
	 */
	public function __construct(LDAPConnectionService $ldapService, IDBConnection $db) {
		$this->ldapService = $ldapService;
		$this->db = $db;
		parent::__construct();
	}
	/**
	 * How to run: `occ ecloud-accounts:reset-user-sessions --date=20241201000000Z`
	 * @return void
	 */
	protected function configure(): void {
		$this
			->setName('ecloud-accounts:reset-user-sessions')
			->setDescription('Invalidate sessions and reset first login for users created after a specific date')
			->addOption(
				'date',
				null,
				InputOption::VALUE_REQUIRED,
				'Date in YYYYMMDDHHMMSSZ format (e.g., 20241201000000Z)',
				null
			);
	}
	/**
	 * Execute function
	 * @param \Symfony\Component\Console\Input\InputInterface $input
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$date = $input->getOption('date');

		if (!$date) {
			$output->writeln('Date option is required.');
			return Command::INVALID;
		}

		try {
			$users = $this->ldapService->getUsersCreatedAfter($date);
			foreach ($users as $user) {
				if (!$user['username']) {
					continue;
				}

				$output->writeln("Processing user: " . $user['username']);

				// Invalidate sessions
				$this->invalidateUserSessions($user['username']);
				$output->writeln("Invalidated session for user: " . $user['username']);

				// Reset first login
				$this->resetFirstLogin($user['username']);
				$output->writeln("Reset first login for user: " . $user['username']);
			}

			$output->writeln('All sessions invalidated and first login reset for eligible users.');
			return Command::SUCCESS;
		} catch (\Exception $e) {
			$output->writeln('Error:' . $e->getMessage());
			return Command::FAILURE;
		}
	}
	/**
	 * Invalidate user sessions for username
	 * @param string $username
	 * @return void
	 */
	private function invalidateUserSessions(string $username): void {
		$qb = $this->db->getQueryBuilder();

		$qb->delete('authtoken')
			->where($qb->expr()->eq('uid', $qb->createNamedParameter($username, IQueryBuilder::PARAM_STR)));

		$qb->executeStatement();
	}
	/**
	 * Reset firsst login
	 * @param string $username
	 * @return void
	 */
	private function resetFirstLogin(string $username): void {
		$qb = $this->db->getQueryBuilder();

		$qb->update('preferences')
			->set('configvalue', $qb->createNamedParameter('0', IQueryBuilder::PARAM_STR))
			->where($qb->expr()->eq('userid', $qb->createNamedParameter($username, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter('firstLogin', IQueryBuilder::PARAM_STR)));

		$qb->executeStatement();
	}
}
