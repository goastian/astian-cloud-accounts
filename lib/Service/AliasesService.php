<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Service;

use OCP\IUserManager;
use OCP\IUser;
use OCP\IConfig;
use UnexpectedValueException;

class AliasesService
{

    /** @var IUserManager */
    private $userManager;

    /** @var array */
    private $appName;

    /** @var IConfig */
    private $config;

    public function __construct($appName, IUserManager $userManager, IConfig $config)
    {
        $this->appName = $appName;
        $this->userManager = $userManager;
        $this->config = $config;
    }

    public function userExists(string $uid): bool
    {
        return $this->userManager->userExists($uid);
    }

    public function getUser(string $uid): ?IUser
    {
        return $this->userManager->get($uid);
    }


}
