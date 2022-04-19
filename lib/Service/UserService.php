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


    public function getConfigValue(string $key, mixed $default = false)
    {
        if (!empty($this->appConfig[$key])) {
            return $this->appConfig[$key];
        }
        return $default;
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

    public function getHMEAliasesFromConfig($uid) : array
    {
        $aliases = $this->config->getUserValue($uid, 'hide-my-email', 'email-aliases', []);
        if (!empty($aliases)) {
            $aliases = json_decode($aliases, true);
        }
        return $aliases;
    }

    public function addHMEAliasInConfig($uid, $alias) : bool
    {
        $aliases = $this->getHMEAliasesFromConfig($uid);
        $aliases[] = $alias;
        $aliases = json_encode($aliases);
        try {
            $this->config->setUserValue($uid, 'hide-my-email', 'email-aliases', $aliases);
            return true;
        } catch(UnexpectedValueException $e) {
            return false;
        } 
    }
}
