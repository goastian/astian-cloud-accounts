<?php

namespace OCA\EcloudAccounts\Db;

use OCP\IConfig;
use OCP\ILogger;
use OCA\EcloudAccounts\Exception\DbConnectionParamsException;
use Doctrine\DBAL\DriverManager;
use Throwable;

class MailboxMapper
{
    private $config;
    private $conn;
    private $logger;

    public function __construct(IConfig $config, ILogger $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->initConnection();
    }

    private function initConnection()
    {
        try {
            $params = $this->getConnectionParams();
            $this->conn = DriverManager::getConnection($params);
        } catch (Throwable $e) {
            $this->logger->info('Error connecting to SQL raw backend: ' . $e->getMessage());
        }
    }

    private function isDbConfigValid($config) : bool
    {
        if (!$config || !is_array($config)) {
            return false;
        }
        return isset($config['db_name'])
            && isset($config['mariadb_charset'])
            && isset($config['db_user'])
            && isset($config['db_password'])
            && isset($config['db_host'])
            && isset($config['db_port']) ;
    }

    private function getConnectionParams()
    {
        $config = $this->config->getSystemValue('user_backend_sql_raw');
        
        if (!$this->isDbConfigValid($config)) {
            throw new DbConnectionParamsException('Invalid SQL raw configuration!');
        }

        $params = [
            'dbname' =>  $config['db_name'],
            'charset' => $config['mariadb_charset'],
            'user' =>  $config['db_user'],
            'password' => $config['db_password'],
            'host' => $config['db_host'],
            'port' => $config['db_port'],
            'driver' => 'pdo_mysql'
        ];
        return $params;
    }

    public function updateMailboxQuota(string $username, int $quotaInMb)
    {
        try {
            $qb = $this->conn->createQueryBuilder();
            $qb->update('mailbox', 'm')
                ->set('m.quota', $quotaInMb)
                ->where('m.username = :username')
                ->setParameter('username', $username);

            $qb->execute();
        } catch (Exception $e) {
            $this->logger->error('Error setting mailbox quota of user ' . $username . ' to ' . strval($quotaInMb) . ': ' . $e->getMessage());
        }
    }
}
