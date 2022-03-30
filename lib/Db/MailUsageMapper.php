<?php

namespace OCA\EcloudAccounts\Db;

use OCP\IDBConnection;
use OCP\IConfig;

class MailUsageMapper
{
    private $db;
    private $config;

    public function __construct(IDBConnection $db, IConfig $config)
    {
        $this->db = $db;
        $this->config = $config;
    }

    public function updateUsageInPreferences(array $usage = [])
    {
        if (empty($usage)) {
            return;
        }
        
        $dbTablePrefix = $this->config->getSystemValue('dbtableprefix', '');
        $params = [];
        $preferencesTable = $dbTablePrefix . 'preferences';
        $query = 'INSERT INTO ' . $preferencesTable . ' (userid, appid, configkey, configvalue) VALUES ';

        // Add values to the insert query and params array
        foreach ($usage as $username => $usedSpace) {
            $query .= ' (?, "ecloud-accounts", "mailQuotaUsage", ?),';
            $params[] = $username;
            $params[] = $usedSpace;
        }

        // Remove the dangling comma at the end
        $query = rtrim($query, ',');
        // Update only configvalue in case entry already exists
        $query .= ' ON DUPLICATE KEY UPDATE configvalue = VALUES(configvalue);';
        $this->db->executeQuery($query, $params);
    }
}
