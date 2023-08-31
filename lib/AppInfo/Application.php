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

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCA\EcloudAccounts\Listeners\BeforeUserDeletedListener;
use OCA\EcloudAccounts\Service\LDAPConnectionService;
use OCP\User\Events\BeforeUserDeletedEvent;
use OCP\User\Events\UserChangedEvent;
use OCA\EcloudAccounts\Listeners\UserChangedListener;
use OCA\EcloudAccounts\Listeners\TwoFactorStateChangedListener;
use OCA\TwoFactorTOTP\Event\StateChanged;
use OCP\IUserManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use OCA\EcloudAccounts\Listeners\FirstLoginListener;
use OCP\IUser;

class Application extends App implements IBootstrap {
	public const APP_ID = 'ecloud-accounts';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerEventListener(BeforeUserDeletedEvent::class, BeforeUserDeletedListener::class);
		$context->registerEventListener(UserChangedEvent::class, UserChangedListener::class);
		$context->registerEventListener(StateChanged::class, TwoFactorStateChangedListener::class);
	}

	public function boot(IBootContext $context): void {
		$serverContainer = $context->getServerContainer();
		$serverContainer->registerService('LDAPConnectionService', function ($c) {
			return new LDAPConnectionService(
				$c->get(IUserManager::class)
			);
		});
		$context->injectFn([$this, 'registerHooks']);
	}
	public function registerHooks(EventDispatcherInterface $dispatcher) {
		// $dispatcher->addListener(IUser::class . '::firstLogin', function ($event) {

		// });
		// first time login event setup
		$dispatcher->addListener(IUser::class . '::firstLogin', [FirstLoginListener::class, 'sendWelcomeEmail']);
	}
}
