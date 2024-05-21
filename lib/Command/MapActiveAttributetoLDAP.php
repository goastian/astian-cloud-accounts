<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Command;

use Exception;
use OCA\EcloudAccounts\AppInfo\Application;
use OCA\EcloudAccounts\Service\UserService;
use OCP\ILogger;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MapActiveAttributetoLDAP extends Command {
	private OutputInterface $commandOutput;
	private IUserManager $userManager;
	private $userService;
	private $logger;

	public function __construct(IUserManager $userManager, ILogger $logger, UserService $userService) {
		$this->userManager = $userManager;
		$this->userService = $userService;
		$this->logger = $logger;
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName(Application::APP_ID.':map-active-attribute-to-ldap')
			->setDescription('Map Active attribute to LDAP');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->commandOutput = $output;
		$this->userManager->callForSeenUsers(function (IUser $user) {
			if ($this->isUserValid($user)) {
				$username = $user->getUID();
				$isEnabled = $user->isEnabled() ? true : false;
				try {
					$this->userService->mapActiveAttributesInLDAP($username, $isEnabled);
				} catch (Exception $e) {
					$this->logger->logException('Failed to update LDAP attributes for user: ' . $username, ['exception' => $e]);
				}
			}
		});
		$this->commandOutput->writeln('Active attributes mapped successfully.');
		return 0;
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
