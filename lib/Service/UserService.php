<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Service;

use OCP\IUserManager;
use OCP\IConfig;
use OCP\Files\Storage\IStorage;

class UserService
{

    /** @var IUserManager */
    private $userManager;

    /** @var IConfig */
    private $config;

    private $appName;

    private $storage;

    public function __construct($appName, IUserManager $userManager, IConfig $config, IStorage $storage)
    {
        $this->userManager = $userManager;
        $this->config = $config;
        $this->appName = $appName;
        $this->storage = $storage;
    }

    private function isShardingEnabled() : bool {
        $shardingEnabled = $this->config->getAppValue($this->appName, 'user_folder_sharding');
        return $shardingEnabled;
    }
    
    public function userExists(string $uid): bool
    {
        return $this->userManager->userExists($uid);
    }

    public function setEmail(string $uid, string $email): void
    {
        $user = $this->userManager->get($uid);
        if (is_null($user)) return;
        $user->setEMailAddress($email);
    }

    public function setQuota(string $uid, string $quota): void
    {
        $user = $this->userManager->get($uid);
        if (is_null($user)) return;
        $user->setQuota($quota);
    }

    public function createUserFolder(string $uid): bool
    {
       // return true as creation can be handled at login if sharding disabled
        if (!$this->isShardingEnabled()) {
            return true;
        }

        $realDataDir = $this->config->getAppValue($this->appName, 'realdatadirectory');
        $ncDataDir = $this->config->getSystemValue('datadirectory');
        $ncUserFolder = $ncDataDir . '/' . $uid;

        // return false if no realDataDir specified and sharding is enabled
        // As user data directory can't be created in correct location
        if (!$realDataDir) {
            return false;
        }

        // Randomly assign a directory for the new user
        $directories = glob($realDataDir . '/*', GLOB_ONLYDIR);
        $folderIndex = random_int(0, count($directories) - 1);
        $folder = $directories[$folderIndex];
        $realUserFolder = $folder . '/' .  $uid;

        // Folder already exists 
        if ($this->storage->file_exists($realUserFolder)) {
            return true;
        }

        try {
            $created = $this->storage->mkdir($realUserFolder);
            if(!$created) {
                $this->logger->error('Error while creating user folder for user: ' . $uid);
                return false;
            }
            $linked = symlink($realUserFolder, $ncUserFolder);
            if(!$linked) {
                $this->logger->error('Error while linking user folder for user: '. $uid);
                return false;
            }
            return true;  
          
        } catch (Exception $e) {
            $this->logger->error("Error while creating user folder and linking for user: " . $uid);
            return false;
        }
    }
}
