<?php
/**
 * @copyright Copyright (c) 2017 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\EcloudAccounts\Migration;

use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\IUser;
use OCP\IUserManager;
use OCA\DAV\CalDAV\CalDavBackend;

/**
 * Class CreateTasksCalendar
 *
 * @package OCA\EcloudAccounts\Migration
 */
class CreateTasksCalendar implements IRepairStep {
	public const APP_ID = 'ecloud-accounts';
	public const TASKS_CALENDAR_URI = 'tasks';
	public const TASKS_CALENDAR_NAME = 'Tasks';

	/** @var IDBConnection */
	protected $connection;

	/** @var  IConfig */
	protected $config;

	/** @var IUserManager */
	private $userManager;

	/** @var CalDavBackend */
	protected $calDav;

	public function __construct(IDBConnection $connection, IConfig $config, IUserManager $userManager, CalDavBackend $calDav) {
		$this->connection = $connection;
		$this->config = $config;
		$this->userManager = $userManager;
		$this->calDav = $calDav;
	}

	/**
	 * Returns the step's name
	 *
	 * @return string
	 * @since 9.1.0
	 */
	public function getName() {
		return 'Fix by creating Tasks calendar for user if not exist.';
	}

	/**
	 * @param IOutput $output
	 */
	public function run(IOutput $output) {
		if ($this->config->getAppValue(self::APP_ID, 'CreateTasksHasRun') === 'yes') {
			$output->info('Repair step already executed');
			return;
		}
		$this->userManager->callForSeenUsers(function (IUser $user) {
			$userId = $user->getUID();
			$principal = 'principals/users/' . $userId;
			$calendar = $this->calDav->getCalendarByUri($principal, self::TASKS_CALENDAR_NAME);
			if ($calendar === null) {
				$this->calDav->createCalendar($principal, self::TASKS_CALENDAR_URI, [
					'{DAV:}displayname' => self::TASKS_CALENDAR_NAME,
					'{http://apple.com/ns/ical/}calendar-color' => $themingDefaults->getColorPrimary(),
					'components' => 'VEVENT'
				]);
			}
		});
		// if everything is done, no need to redo the repair during next upgrade
		$this->config->setAppValue(self::APP_ID, 'CreateTasksHasRun', 'yes');
	}
}
