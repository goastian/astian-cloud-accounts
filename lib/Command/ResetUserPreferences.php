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

class ResetUserPreferences extends Command {
    private LDAPConnectionService $ldapService;
    private IDBConnection $db;

    public function __construct(LDAPConnectionService $ldapService, IDBConnection $db) {
        $this->ldapService = $ldapService;
        $this->db = $db;
        parent::__construct();
    }

    protected function configure(): void {
        $this
            ->setName('ecloud-accounts:reset-user-preferences')
            ->setDescription('Invalidate sessions and remove preferences for users created after a specific date')
            ->addOption(
                'date',
                null,
                InputOption::VALUE_REQUIRED,
                'Date in YYYYMMDDHHMMSSZ format (e.g., 20241201000000Z)',
                null
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $date = $input->getOption('date');

        if (!$date) {
            $output->writeln('<error>Date option is required.</error>');
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
                $output->writeln("Processing user: $username");

                // Invalidate user sessions
                $this->invalidateUserSessions($username);
                $output->writeln("Invalidated session for user: $username");

                // Delete specific preferences
                $this->deletePreference($username, 'firstLoginAccomplished');
                $output->writeln("Deleted 'firstLoginAccomplished' preference for user: $username");

                $this->deletePreference($username, 'lastLogin');
                $output->writeln("Deleted 'lastLogin' preference for user: $username");
            }

            $output->writeln('<info>All sessions invalidated and preferences deleted for eligible users.</info>');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }

    private function invalidateUserSessions(string $username): void {
        $qb = $this->db->getQueryBuilder();

        $qb->delete('authtoken')
            ->where($qb->expr()->eq('uid', $qb->createNamedParameter($username, IQueryBuilder::PARAM_STR)));

        $qb->executeStatement();
    }

    private function deletePreference(string $username, string $key): void {
        $qb = $this->db->getQueryBuilder();

        $qb->delete('preferences')
            ->where($qb->expr()->eq('userid', $qb->createNamedParameter($username, IQueryBuilder::PARAM_STR)))
            ->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter($key, IQueryBuilder::PARAM_STR)));

        $qb->executeStatement();
    }
}
