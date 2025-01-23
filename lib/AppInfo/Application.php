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

use OC\Files\Filesystem;
use OCA\EcloudAccounts\Filesystem\StorageWrapper;
use OCA\EcloudAccounts\Listeners\BeforeTemplateRenderedListener;
use OCA\EcloudAccounts\Listeners\BeforeUserDeletedListener;
use OCA\EcloudAccounts\Listeners\PasswordUpdatedListener;
use OCA\EcloudAccounts\Listeners\TwoFactorStateChangedListener;
use OCA\EcloudAccounts\Listeners\UserChangedListener;
use OCA\EcloudAccounts\Middleware\AccountMiddleware;
use OCA\EcloudAccounts\Service\FilesystemService;
use OCA\EcloudAccounts\Service\LDAPConnectionService;
use OCA\TwoFactorTOTP\Event\StateChanged;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\Files\Storage\IStorage;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\User\Events\BeforeUserDeletedEvent;
use OCP\User\Events\PasswordUpdatedEvent;
use OCP\User\Events\UserChangedEvent;
use OCP\Util;

class Application extends App implements IBootstrap {
	public const APP_ID = 'ecloud-accounts';
	private FilesystemService $fsservice;
	private IUserSession $userSession;

	public function __construct(array $urlParams = [], FilesystemService $fsservice, IUserSession $userSession) {
		parent::__construct(self::APP_ID, $urlParams);
		$this->fsservice = $fsservice;
		$this->userSession = $userSession;
	}

	public function register(IRegistrationContext $context): void {
		$username = $this->userSession->getUser()->getUID();
		if(!$this->fsservice->checkFilesGroupAccess($username)) {
			Util::connectHook('OC_Filesystem', 'preSetup', $this, 'addStorageWrapper');
		}
		$context->registerEventListener(BeforeTemplateRenderedEvent::class, BeforeTemplateRenderedListener::class);
		$context->registerEventListener(BeforeUserDeletedEvent::class, BeforeUserDeletedListener::class);
		$context->registerEventListener(UserChangedEvent::class, UserChangedListener::class);
		$context->registerEventListener(StateChanged::class, TwoFactorStateChangedListener::class);
		$context->registerEventListener(PasswordUpdatedEvent::class, PasswordUpdatedListener::class);
	
		$context->registerMiddleware(AccountMiddleware::class);
	}

	public function boot(IBootContext $context): void {
		$serverContainer = $context->getServerContainer();
		$serverContainer->registerService('LDAPConnectionService', function ($c) {
			return new LDAPConnectionService(
				$c->get(IUserManager::class)
			);
		});
	}

	/**
	 * @internal
	 */
	public function addStorageWrapper(): void {
		Filesystem::addStorageWrapper('ecloud-accounts', [$this, 'addStorageWrapperCallback'], -10);
	}

	/**
	 * @internal
	 * @param $mountPoint
	 * @param IStorage $storage
	 * @return StorageWrapper|IStorage
	 */
	public function addStorageWrapperCallback($mountPoint, IStorage $storage) {
		$instanceId = \OC::$server->getConfig()->getSystemValue('instanceid', '');
		$appdataFolder = 'appdata_' . $instanceId;
		if ($mountPoint !== '/' && strpos($mountPoint, '/' . $appdataFolder) !== 0) {
			return new StorageWrapper([
				'storage' => $storage,
				'mountPoint' => $mountPoint,
			]);
		}

		return $storage;
	}
}
