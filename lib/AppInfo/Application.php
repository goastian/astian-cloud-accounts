<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020 Florent VINCENT <e.foundation>
 *
 * @author Florent VINCENT <diroots@e.email>
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

namespace OCA\EcloudAccounts\AppInfo;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\EcloudAccounts\Listeners\BeforeTemplateRenderedListener;
use OCA\EcloudAccounts\Listeners\BeforeUserDeletedListener;
use OCA\EcloudAccounts\Listeners\UserChangedListener;
use OCA\EcloudAccounts\Service\LDAPConnectionService;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\Defaults;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\User\Events\BeforeUserDeletedEvent;
use OCP\User\Events\UserChangedEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class Application extends App implements IBootstrap {
	public const APP_ID = 'ecloud-accounts';
	public const TASKS_CALENDAR_URI = 'tasks';
	public const TASKS_CALENDAR_NAME = 'Tasks';
	public const TASKS_CALENDAR_COMPONENT = 'VTODO';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerEventListener(BeforeUserDeletedEvent::class, BeforeUserDeletedListener::class);
		$context->registerEventListener(UserChangedEvent::class, UserChangedListener::class);
		// $context->registerEventListener(BeforeTemplateRenderedEvent::class, BeforeTemplateRenderedListener::class);
	}

	public function boot(IBootContext $context): void {
		$context->injectFn([$this, 'createTasksCalendar']);
		$serverContainer = $context->getServerContainer();
		$serverContainer->registerService('LDAPConnectionService', function ($c) {
			return new LDAPConnectionService();
		});
	}

	public function createTasksCalendar(CalDavBackend $calDav, IDBConnection $db, Defaults $themingDefaults, EventDispatcherInterface $dispatcher): void {
		$dispatcher->addListener(IUser::class . '::firstLogin', function (GenericEvent $event) use ($calDav, $themingDefaults, $db) {
			$user = $event->getSubject();
			if (!$user instanceof IUser) {
				return;
			}
			$userId = $user->getUID();
			$principal = 'principals/users/' . $userId;
			$calendar = $calDav->getCalendarByUri($principal, self::TASKS_CALENDAR_NAME);
			$query = $db->getQueryBuilder();
			$query->select($fields)->from('calendars')
					->where($query->expr()->eq('uri', $query->createNamedParameter(self::TASKS_CALENDAR_NAME)))
					->andWhere($query->expr()->eq('principaluri', $query->createNamedParameter($principal)))
					->andWhere($query->expr()->eq('components', $query->createNamedParameter(self::TASKS_CALENDAR_COMPONENT)))
					->setMaxResults(1);
			$stmt = $query->executeQuery();
			$row = $stmt->fetch();
			$stmt->closeCursor();
			if ($row === false) {
				$calDav->createCalendar($principal, self::TASKS_CALENDAR_URI, [
					'{DAV:}displayname' => self::TASKS_CALENDAR_NAME,
					'{http://apple.com/ns/ical/}calendar-color' => $themingDefaults->getColorPrimary(),
					'components' => self::TASKS_CALENDAR_COMPONENT
				]);
			}
		});
	}
}
