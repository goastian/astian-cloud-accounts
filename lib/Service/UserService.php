<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Service;

use OCP\IUserManager;
use OCP\IUser;
use OCP\IConfig;
use UnexpectedValueException;

class UserService
{

    /** @var IUserManager */
    private $userManager;

    /** @var array */
    private $appConfig;

    /** @var IConfig */
    private $config;

    public function __construct($appName, IUserManager $userManager, IConfig $config)
    {
        $this->userManager = $userManager;
        $this->config = $config;
        $this->appConfig = $this->config->getSystemValue($appName);
    }

    public function isShardingEnabled(): bool
    {
        $shardingEnabled = $this->config->getSystemValue('user_folder_sharding', false);
        return $shardingEnabled;
    }

    public function getConfigValue(string $key)
    {
        if (!empty($this->appConfig[$key])) {
            return $this->appConfig[$key];
        }
        return false;
    }


    public function userExists(string $uid): bool
    {
        return $this->userManager->userExists($uid);
    }

    public function getUser(string $uid): ?IUser
    {
        return $this->userManager->get($uid);
    }

    public function setRecoveryEmail(string $uid, string $recoveryEmail): bool
    {
        try {
            $this->config->setUserValue($uid, 'email-recovery', 'recovery-email', $recoveryEmail);
            return true;
        } catch (UnexpectedValueException $e) {
            return false;
        }
    }

    public function createUserFolder(string $uid): bool
    {

        $realDataDir = $this->getConfigValue('realdatadirectory');
        $ncDataDir = $this->config->getSystemValue('datadirectory');
        $ncUserFolder = $ncDataDir . '/' . $uid;

        // return false if no realDataDir specified and sharding is enabled
        // As user data directory can't be created in correct location
        if (!$realDataDir) {
            return false;
        }

        // Folder already exists 
        if (file_exists($ncUserFolder)) {
            return true;
        }

        // Randomly assign a directory for the new user
        $directories = glob($realDataDir . '/*', GLOB_ONLYDIR);
        $folderIndex = random_int(0, count($directories) - 1);
        $folder = $directories[$folderIndex];
        $realUserFolder = $folder . '/' .  $uid;

        try {
            $created = mkdir($realUserFolder);
            if (!$created) {
                $this->logger->error('Error while creating user folder for user: ' . $uid);
                return false;
            }
            $linked = symlink($realUserFolder, $ncUserFolder);
            if (!$linked) {
                $this->logger->error('Error while linking user folder for user: ' . $uid);
                return false;
            }
            return true;
        } catch (Exception $e) {
            $this->logger->error("Error while creating user folder and linking for user: " . $uid);
            return false;
        }
    }

}
