<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\EcloudAccounts\BackgroundJob;

use OCA\EcloudAccounts\Service\UserService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;

class WeeklyBlacklistedDomainsJob extends TimedJob {
	private LoggerInterface $logger;
	private UserService $userService;
	private ITimeFactory $timeFactory;


	public function __construct(LoggerInterface $logger, ITimeFactory $timeFactory, UserService $userService) {
		parent::__construct($timeFactory);

		$this->setInterval(7 * 24 * 60 * 60); // Run for 7 days
		$this->timeFactory = $timeFactory;
		$this->userService = $userService;
		$this->logger = $logger;
	}

	protected function run($argument): void {
		try {
			$this->userService->updateBlacklistedDomains();
		} catch (\Exception $e) {
			$this->logger->error('Error running blacklisted domain migration', ['exception' => $e]);
			return;
		}
	}
}
