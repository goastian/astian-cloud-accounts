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

namespace OCA\EcloudAccounts\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IUserSession;
use OCP\Settings\ISettings;
use OCP\Util;
use OCA\EcloudAccounts\Service\ShopAccountService;

class Personal implements ISettings {

	/** @var IUserSession */
	private $userSession;
	/**
	 * @var IInitialState
	 */
	private $initialState;

	private $appName;

	private $shopAccountService;

	public function __construct($appName, IUserSession $userSession, IInitialState $initialState, ShopAccountService $shopAccountService) {
		$this->userSession = $userSession;
		$this->initialState = $initialState;
		$this->appName = $appName;
		$this->shopAccountService = $shopAccountService;
	}

	/**
	 * @return TemplateResponse returns the instance with all parameters set, ready to be rendered
	 * @since 9.1
	 */
	public function getForm(): TemplateResponse {
		
		$user = $this->userSession->getUser();
		if ($user) {
			Util::addScript($this->appName, 'ecloud-accounts-personal-settings');
			Util::addStyle($this->appName, 'personal');
			$deleteShopAccount = $this->shopAccountService->getShopDeletePreference($user->getUID());
			$shopEmailPostDelete = $this->shopAccountService->getShopEmailPostDeletePreference($user->getUID());

			$this->initialState->provideInitialState('delete_shop_account', $deleteShopAccount);
			$this->initialState->provideInitialState('shop_email_post_delete', $shopEmailPostDelete);
		}
		
		return new TemplateResponse($this->appName, 'personal');
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 * @since 9.1
	 * @psalm-return 'drop_account'
	 */
	public function getSection(): string {
		return 'drop_account';
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 * the admin section. The forms are arranged in ascending order of the
	 * priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 * @since 9.1
	 * @psalm-return 40
	 */
	public function getPriority(): int {
		return 20;
	}
}
