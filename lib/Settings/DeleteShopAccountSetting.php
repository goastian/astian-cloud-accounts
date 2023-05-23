<?php

namespace OCA\EcloudAccounts\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IGroupManager;
use OCP\IUserSession;
use OCP\Settings\ISettings;
use OCP\Util;
use OCA\EcloudAccounts\Service\ShopAccountService;
use OCP\App\IAppManager;
use OCP\IUserManager;

class DeleteShopAccountSetting implements ISettings {
	private const DROP_ACCOUNT_APP_ID = 'drop_account';
	/** @var IUserSession */
	private $userSession;
	/**
	 * @var IInitialState
	 */
	private $initialState;

	private $appName;

	private $shopAccountService;

	private $appManager;

	private IGroupManager $groupManager;

	private IUserManager $userManager;

	/** @var Util */
	protected $util;

	public function __construct($appName, IUserSession $userSession, IInitialState $initialState, ShopAccountService $shopAccountService, IAppManager $appManager, IGroupManager $groupManager, IUserManager $userManager, Util $util) {
		$this->userSession = $userSession;
		$this->initialState = $initialState;
		$this->appName = $appName;
		$this->shopAccountService = $shopAccountService;
		$this->appManager = $appManager;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->util = $util;
	}

	/**
	 * @return TemplateResponse returns the instance with all parameters set, ready to be rendered
	 * @since 9.1
	 */
	public function getForm(): TemplateResponse {
		$user = $this->userSession->getUser();
		if ($user) {
			$onlyUser = $this->userManager->countUsers() < 2;
			$adminGroup = $this->groupManager->get('admin');
			$onlyAdmin = $adminGroup && $adminGroup->count() < 2 && $this->groupManager->isAdmin($user->getUID());
			$this->util->addScript($this->appName, $this->appName.'-personal-settings');
			$this->util->addScript($this->appName, $this->appName.'-delete-account-listeners');
			$deleteShopAccount = $this->shopAccountService->getShopDeletePreference($user->getUID());
			$shopEmailPostDelete = $this->shopAccountService->getShopEmailPostDeletePreference($user->getUID());

			$this->initialState->provideInitialState('email', $user->getEMailAddress());
			$this->initialState->provideInitialState('delete_shop_account', $deleteShopAccount);
			$this->initialState->provideInitialState('shop_email_post_delete', $shopEmailPostDelete);
			$this->initialState->provideInitialState('only_user', $onlyUser);
			$this->initialState->provideInitialState('only_admin', $onlyAdmin);
			$shopUsers = $this->shopAccountService->getUsers($user->getEMailAddress());
			$this->initialState->provideInitialState('shopUsers', $shopUsers);
		}

		return new TemplateResponse($this->appName, 'personal');
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 * @since 9.1
	 * @psalm-return 'drop_account'
	 */
	public function getSection(): ?string {
		$dropAccountEnabled = $this->appManager->isEnabledForUser(self::DROP_ACCOUNT_APP_ID);
		if ($dropAccountEnabled) {
			return self::DROP_ACCOUNT_APP_ID;
		}
		return null;
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 * the admin section. The forms are arranged in ascending order of the
	 * priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 * @since 9.1
	 * @psalm-return 20
	 */
	public function getPriority(): int {
		return 20;
	}
}
