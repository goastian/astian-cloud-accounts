<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Command;

use OCA\EcloudAccounts\AppInfo\Application;
use OCA\EcloudAccounts\Service\UserService;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MapActiveAttributetoLDAP extends Command {
	private OutputInterface $commandOutput;
	private IUserManager $userManager;
	private $userService;

	public function __construct(IUserManager $userManager, UserService $userService) {
		$this->userManager = $userManager;
		$this->userService = $userService;
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName(Application::APP_ID.':map-active-attribute-to-ldap')
			->setDescription('Map Active attribute to LDAP');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		try {
			$this->userManager->callForSeenUsers(function (IUser $user) {
				if ($this->isUserValid($user)) {
					if ($user->isEnabled()) {
						$userActiveAttributes = [
							'active' => 'TRUE',
							'mailActive' => 'TRUE',
						];
					} else {
						$userActiveAttributes = [
							'active' => 'FALSE',
							'mailActive' => 'FALSE',
						];
					}
					$username = $user->getUID();
					$this->userService->updateAttributesInLDAP($username, $userActiveAttributes);
				}
			});
			return 0;
		} catch (\Exception $e) {
			$this->commandOutput->writeln($e->getMessage());
			return 1;
		}
	}
	/**
	 * validate user
	 *
	 * @param IUser $user
	 */
	private function isUserValid(?IUser $user) : bool {
		if (!($user instanceof IUser)) {
			return false;
		}
		return true;
	}
}
