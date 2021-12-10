<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Service;
use OCP\IUserManager;

class UserService {
   
    /** @var IUserManager */
    private $userManager;

    public function __construct(IUserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    public function userExists(string $uid) : bool {
        return $this->userManager->userExists($uid);
    }

    public function setEmail(string $uid, string $email) : void {
        $user = $this->userManager->get($uid);
        if (is_null($user)) return;
        $user->setEMailAddress($email);

    }

    public function setQuota(string $uid, string $quota) : void {
        $user = $this->userManager->get($uid);
        if (is_null($user)) return;
        $user->setQuota($quota);
    }
}