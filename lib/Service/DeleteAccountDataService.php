<?php
/**
 * @copyright Copyright (c) 2017 Thomas Citharel <nextcloud@tcit.fr>
 *
 * @author Thomas Citharel <nextcloud@tcit.fr>
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

namespace OCA\EcloudAccounts\Service;

use BadMethodCallException;
use InvalidArgumentException;
use OCA\EcloudAccounts\AppInfo\Application;
use OCP\Activity\IEvent;
use OCP\Activity\IManager;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class DeleteAccountDataService {
	protected IUserManager $userManager;
	protected LoggerInterface $logger;
	protected IGroupManager $groupManager;
	protected IManager $activityManager;

	public function __construct(IUserManager $userManager, LoggerInterface $logger, IGroupManager $groupManager, IManager $activityManager) {
		$this->userManager = $userManager;
		$this->logger = $logger;
		$this->groupManager = $groupManager;
		$this->activityManager = $activityManager;
	}

	public function delete(string $uid): void {
		try {
			$user = $this->userManager->get($uid);

			if (!$user) {
				$this->logger->error("No user found with UID <$uid>");
				return;
			}

			if ($user->isEnabled()) {
				$this->logger->info("Tried to delete the user <$uid>, but their account is still active. An admin might have saved them.");
				return;
			}

			$adminGroup = $this->groupManager->get('admin');
			$admins = [];
			if ($adminGroup) {
				$admins = $adminGroup->getUsers();
			}
			$events = [];
			foreach ($admins as $admin) {
				$events[] = $this->createActivity($user, $admin);
			}

			if (!$user->delete()) {
				$this->logger->error('There has been an issue while deleting the user <' . $uid . '>.');
				return;
			}

			foreach ($events as $event) {
				$this->activityManager->publish($event);
			}
		} catch (InvalidArgumentException | BadMethodCallException $e) {
			$this->logger->error('There has been an issue sending the delete activity to admins', ['app' => Application::APP_NAME, 'exception' => $e]);
		}
	}

	/**
	 * @param IUser $user
	 * @param IUser $admin
	 * @return IEvent
	 */
	private function createActivity(IUser $user, IUser $admin): IEvent {
		/**
		 * To be sure that it's accessible once deleted?
		 */
		$username = $user->getUID();
		$name = $user->getDisplayName();
		$email = $user->getEMailAddress();

		$event = $this->activityManager->generateEvent();
		$event
			->setApp(Application::APP_NAME)
			->setType('account_deletion')
			->setAuthor($username)
			->setSubject('account_self_deletion', ['username' => $username, 'name' => $name, 'email' => $email])
			->setAffectedUser($admin->getUID())
		;

		return $event;
	}
}
