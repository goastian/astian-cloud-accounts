<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Filesystem;

use OC\Files\Cache\Wrapper\CacheWrapper as Wrapper;
use OCP\Constants;
use OCP\Files\Cache\ICache;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\Search\ISearchQuery;

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
				throw new \Exception('Access denied', 503);
			} catch (\Exception) {
				$entry['permissions'] &= $this->mask;
			}
		}
		return $entry;
	}

	public function insert($file, $data) {
		throw new \OC\ServiceUnavailableException('Service unavailable');
		// throw new \Exception('User data cache insert is disabled.', 503);
	}

	public function update($id, $data) {
		throw new \OC\ServiceUnavailableException('Service unavailable');
		// throw new \Exception('User data cache update is disabled.', 503);
	}

	public function remove($fileId) {
		throw new \OC\ServiceUnavailableException('Service unavailable');
		// throw new \Exception('User data cache removal is disabled.', 503);
	}

	public function searchQuery(ISearchQuery $searchQuery) {
		return [];
	}

	public function getCacheEntryFromSearchResult(ICacheEntry $rawEntry): ?ICacheEntry {
		return null;
	}
}
