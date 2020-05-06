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

namespace OCA\EcloudDropAccount\AppInfo;

use OCA\EcloudDropAccount\Events\UserDeletedListener;
use OCP\AppFramework\App;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\User\Events\UserDeletedEvent;

class Application extends App
{

    const APP_NAME = 'ecloud_drop_account';

    public function __construct()
    {
        parent::__construct(self::APP_NAME);
    }

    public function register()
    {
        /* @var IEventDispatcher $eventDispatcher */
        $eventDispatcher = $this->getContainer()->query(IEventDispatcher::class);
        $eventDispatcher->addServiceListener(UserDeletedEvent::class, UserDeletedListener::class);
    }

}
