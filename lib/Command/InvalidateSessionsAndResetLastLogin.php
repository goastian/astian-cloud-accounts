<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Command;

use OCP\IUser;
use OCP\IUserManager;
use OCP\DB\ISimpleQueryBuilder;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InvalidateSessionsAndResetLastLogin extends Command {
    private IUserManager $userManager;
    private IDBConnection $dbConnection;
    private OutputInterface $commandOutput;

    public function __construct(IUserManager $userManager, IDBConnection $dbConnection) {
        $this->userManager = $userManager;
        $this->dbConnection = $dbConnection;
        parent::__construct();
    }

    protected function configure(): void {
        $this
            ->setName('ecloud-accounts:invalidate-sessions-reset-lastlogin')
            ->setDescription('Invalidates user sessions and resets lastlogin for users created after a specified date')
            ->addOption(
                'created-after',
                null,
                InputOption::VALUE_REQUIRED,
                'Users created after this date (format: YYYY-MM-DD)',
                ''
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        try {
            $this->commandOutput = $output;
            $createdAfter = $input->getOption('created-after');

            if (empty($createdAfter) || !strtotime($createdAfter)) {
                $output->writeln('Invalid or missing date for --created-after.');
                return 1;
            }

            $users = $this->getUsersCreatedAfter($createdAfter);
            if (empty($users)) {
                $output->writeln('No users found created after ' . $createdAfter . '.');
                return 0;
            }

            $this->invalidateSessions($users);
            $this->resetLastLogin($users);

            $output->writeln('Successfully invalidated sessions and reset lastlogin for users:');
            $output->writeln(implode(', ', $users));

            return 0;
        } catch (\Exception $e) {
            $this->commandOutput->writeln('<error>' . $e->getMessage() . '</error>');
            return 1;
        }
    }

    private function getUsersCreatedAfter(string $date): array {
        $queryBuilder = $this->dbConnection->getQueryBuilder();
        $queryBuilder->select('uid')
            ->from('users')
            ->where($queryBuilder->expr()->gt('creation_date', $queryBuilder->createNamedParameter($date, IQueryBuilder::PARAM_STR)));

        $result = $queryBuilder->executeQuery();
        return $result->fetchFirstColumn();
    }

    private function invalidateSessions(array $userIds): void {
        $queryBuilder = $this->dbConnection->getQueryBuilder();
        $queryBuilder->delete('authtoken')
            ->where($queryBuilder->expr()->in('user', $queryBuilder->createNamedParameter($userIds, IQueryBuilder::PARAM_STR_ARRAY)));

        $queryBuilder->executeStatement();
        $this->commandOutput->writeln('Invalidated sessions for ' . count($userIds) . ' users.');
    }

    private function resetLastLogin(array $userIds): void {
        $queryBuilder = $this->dbConnection->getQueryBuilder();
        $queryBuilder->update('preferences')
            ->set('configvalue', $queryBuilder->createNamedParameter(null))
            ->where($queryBuilder->expr()->in('user_id', $queryBuilder->createNamedParameter($userIds, IQueryBuilder::PARAM_STR_ARRAY)))
            ->andWhere($queryBuilder->expr()->eq('appid', $queryBuilder->createNamedParameter('login')))
            ->andWhere($queryBuilder->expr()->eq('configkey', $queryBuilder->createNamedParameter('lastlogin')));

        $queryBuilder->executeStatement();
        $this->commandOutput->writeln('Reset lastlogin for ' . count($userIds) . ' users.');
    }
}
