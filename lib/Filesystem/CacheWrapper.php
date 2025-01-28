<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Filesystem;

use OC\Files\Cache\Wrapper\CacheWrapper as Wrapper;
use OCP\Constants;
use OCP\Files\Cache\ICache;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\Search\ISearchQuery;

class CacheWrapper extends Wrapper {

	private string $excludedFolder = 'files/Recovery';

	public function __construct(
		ICache $cache
	) {
		parent::__construct($cache);
		$this->mask = Constants::PERMISSION_READ;
	}

	/**
	 * Format the cache entry to check access and adjust permissions.
	 */
	protected function formatCacheEntry($entry) {
		if (isset($entry['path']) && isset($entry['permissions'])) {
			// Only restrict permissions for files in the "Recovery" folder
			if ($this->isExcludedPath($entry['path'])) {
				try {
					throw new \OC\ServiceUnavailableException('Service unavailable');
				} catch (\OC\ServiceUnavailableException $e) {
					$entry['permissions'] &= $this->mask;
				}
			}
		}
		return $entry;
	}

	/**
	 * Prevent inserting into the cache for "Recovery" folder.
	 */
	public function insert($file, $data) {
		if ($this->isExcludedPath($file)) {
			throw new \OC\ServiceUnavailableException('Service unavailable');
		}
		return parent::insert($file, $data); // Normal insert for other paths
	}

	/**
	 * Prevent updating cache for files in the "Recovery" folder.
	 */
	public function update($id, $data) {
		if (isset($data['path']) && $data['path'] !== null && $this->isExcludedPath($data['path'])) {
			throw new \OC\ServiceUnavailableException('Service unavailable');
		}
		return parent::update($id, $data); // Normal update for other paths
	}

	/**
	 * Prevent removal from cache for files in the "Recovery" folder.
	 */
	public function remove($fileId) {
		$filePath = $this->storage->getPath($fileId);
		if ($this->isExcludedPath($filePath)) {
			throw new \OC\ServiceUnavailableException('Service unavailable');
		}
		return parent::remove($fileId); // Normal removal for other paths
	}

	/**
	 * Exclude specific folder and its files from search results.
	 */
	public function searchQuery(ISearchQuery $searchQuery) {
		$results = parent::searchQuery($searchQuery);
		return array_filter($results, function ($entry) {
			return isset($entry['path']) && !$this->isExcludedPath($entry['path']);
		});
	}

	/**
	 * Filter out "Recovery" folder from cache search results.
	 */
	public function getCacheEntryFromSearchResult(ICacheEntry $rawEntry): ?ICacheEntry {
		if ($this->isExcludedPath($rawEntry->getPath())) {
			return null;
		}
		return parent::getCacheEntryFromSearchResult($rawEntry);
	}

	/**
	 * Check if a path is within the excluded folder (e.g., "Recovery").
	 */
	private function isExcludedPath(string $path): bool {
		if (empty($path)) {
			return false;
		}
		return strpos($path, $this->excludedFolder) === 0;
	}
}
