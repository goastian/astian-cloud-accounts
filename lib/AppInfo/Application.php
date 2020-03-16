<?php
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

use OCA\EcloudDropAccount\Hooks\UserHooks;
use OCP\AppFramework\App;
use OCP\ILogger;
use OCP\IContainer;
use OCP\ServerContainer;
use OCP\IUserManager;
use OCP\IConfig;


class Application extends App {

	const APP_NAME = 'ecloud_drop_account';

	public function __construct ()
	{
		parent::__construct(self::APP_NAME);

		$container = $this->getContainer();

		
        $container->registerService('UserHooks', function($c) {
            return new UserHooks(
                $c->query('ServerContainer')->getUserManager(),
                $c->query('ServerContainer')->getLogger(),
                $c->query('ServerContainer')->getUserSession(),
                $c->query('ServerContainer')->getConfig()
            );
        });
        
	}


}
