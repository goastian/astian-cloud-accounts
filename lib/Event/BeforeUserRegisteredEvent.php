<?php

declare(strict_types=1);

/**
 * @copyright 2024 Murena SAS <ronak.patel.ext@murena.com>
 *
 * @author Murena SAS <ronak.patel.ext@murena.com>
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

namespace OCA\EcloudAccounts\Event;

use OCP\EventDispatcher\Event;

/**
 * Emitted before a new user is created on the back-end.
 *
 * @since 18.0.0
 */
class BeforeUserRegisteredEvent extends Event {

	private $recoveryMailAddress;
	private $language;
	private $username;
	private $displayName;

	public function __construct(string $username, string $displayName, string $recoveryMailAddress, string $language) {
		$this->username = $username;
		$this->displayName = $displayName;
		$this->recoveryMailAddress = $recoveryMailAddress;
		$this->language = $language;
	}
	public function getRecoveryEmail(): string {
		return $this->recoveryMailAddress;
	}

	public function getLanguage(): string {
		return $this->language;
	}
	public function getUsername(): string {
		return $this->username;
	}
	public function getDisplayName(): string {
		return $this->displayName;
	}
}
