<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Filesystem;

use OC\Files\Cache\Wrapper\CacheWrapper as Wrapper;
use OCP\Constants;
use OCP\Files\ForbiddenException;

class CacheWrapper extends Wrapper {

	public function __construct(
		ICache $cache
	) {
		parent::__construct($cache);
		$this->mask = Constants::PERMISSION_ALL
			& ~Constants::PERMISSION_READ
			& ~Constants::PERMISSION_CREATE
			& ~Constants::PERMISSION_UPDATE
			& ~Constants::PERMISSION_DELETE;
	}

	protected function formatCacheEntry($entry) {
		if (isset($entry['path']) && isset($entry['permissions'])) {
			try {
				throw new ForbiddenException('Access denied');
			} catch (ForbiddenException) {
				$entry['permissions'] &= $this->mask;
			}
		}
		return $entry;
	}

	public function get($fileId) {
		throw new OCP\Files\Cache\CacheEntryNotFoundException('User data cache access is disabled.');
	}

	public function insert($file, $data) {
		throw new \OCP\Files\Cache\CacheUpdateForbiddenException('User data cache insert is disabled.');
	}

	public function update($id, $data) {
		throw new \OCP\Files\Cache\CacheUpdateForbiddenException('User data cache update is disabled.');
	}

	public function remove($fileId) {
		throw new \OCP\Files\Cache\CacheUpdateForbiddenException('User data cache removal is disabled.');
	}
}
