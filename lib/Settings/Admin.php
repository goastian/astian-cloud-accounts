<?php
/**
 * @copyright 2017, Thomas Citharel <nextcloud@tcit.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\EcloudAccounts\Settings;

use OCA\EcloudAccounts\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\Settings\ISettings;
use OCP\Util;

class Admin implements ISettings {
	

	/** @var IConfig */
	private $config;
	/**
	 * @var IInitialState
	 */
	private $initialState;
	private $appName;

	/**
	 * CalDAVSettings constructor.
	 *
	 * @param IConfig $config
	 * @param IInitialState $initialState
	 */
	public function __construct(IConfig $config, IInitialState $initialState) {
		$this->config = $config;
		$this->initialState = $initialState;
		$this->appName = 'drop_account';
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm() {
		$requiresConfirmation = $this->config->getAppValue($this->appName, 'requireConfirmation', 'no') === 'yes';
		$delayPurge = $this->config->getAppValue($this->appName, 'delayPurge', 'no');
		$delayPurgeHours = $this->config->getAppValue($this->appName, 'delayPurgeHours', '24');

		$this->initialState->provideInitialState('requireConfirmation', $requiresConfirmation);
		$this->initialState->provideInitialState('delayPurge', $delayPurge);
		$this->initialState->provideInitialState('delayPurgeHours', $delayPurgeHours);
		Util::addScript($this->appName, 'drop_account-admin-settings');

		return new TemplateResponse($this->appName, 'admin');
	}

	/**
	 * @return string
	 * @psalm-return 'additional'
	 */
	public function getSection() {
		return $this->appName; //'additional';
	}

	/**
	 * @return int
	 * @psalm-return 80
	 */
	public function getPriority() {
		return 81;
	}
}
