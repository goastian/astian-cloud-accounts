<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\BackgroundJob;

use OCA\EcloudAccounts\Service\UserService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;

class WeeklyBlacklistedDomainsJob extends TimedJob {
	private LoggerInterface $logger;
	private UserService $userService;
	private ITimeFactory $timeFactory;
	public const INTERVAL_PERIOD = 7 * 24 * 60 * 60;// Run for 7 days
	public function __construct(LoggerInterface $logger, ITimeFactory $timeFactory, UserService $userService) {
		parent::__construct($timeFactory);

		$this->setInterval(self::INTERVAL_PERIOD);
		$this->timeFactory = $timeFactory;
		$this->userService = $userService;
		$this->logger = $logger;
	}

	protected function run($argument): void {
		try {
			$this->userService->updateBlacklistedDomains();
		} catch (\Exception $e) {
			$this->logger->logException('Error updating blacklisted domains for account creation', ['exception' => $e]);
			return;
		}
	}
}
