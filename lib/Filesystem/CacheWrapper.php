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
		$this->mask = Constants::PERMISSION_ALL
			& ~Constants::PERMISSION_READ
			& ~Constants::PERMISSION_CREATE
			& ~Constants::PERMISSION_UPDATE
			& ~Constants::PERMISSION_DELETE;
	}

	/**
	 * Format the cache entry to check access and adjust permissions.
	 */
	protected function formatCacheEntry($entry) {
		if (isset($entry['path']) && isset($entry['permissions'])) {
			// Only restrict permissions for files in the "Recovery" folder
			if ($this->isExcludedPath($entry['path'])) {
				$entry['permissions'] &= $this->mask;
			}
		}
		return $entry;
	}

	/**
	 * Prevent inserting into the cache for "Recovery" folder.
	 */
	public function insert($file, $data) {
		// Ensure path is set before checking
		if (isset($file) && $this->isExcludedPath($file)) {
			throw new \Exception('Cache insert is disabled for the Recovery folder.');
		}
		return parent::insert($file, $data); // Normal insert for other paths
	}

	/**
	 * Prevent updating cache for files in the "Recovery" folder.
	 */
	public function update($id, $data) {
		// Ensure path is set before checking
		if (isset($data['path']) && $this->isExcludedPath($data['path'])) {
			throw new \Exception('Cache update is disabled for the Recovery folder.');
		}
		return parent::update($id, $data); // Normal update for other paths
	}

	/**
	 * Prevent removal from cache for files in the "Recovery" folder.
	 */
	public function remove($fileId) {
		$filePath = $this->storage->getPath($fileId);
		if ($this->isExcludedPath($filePath)) {
			throw new \Exception('Cache removal is disabled for the Recovery folder.');
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
	private function isExcludedPath(?string $path): bool {
		// If path is null or not set, return false
		if ($path === null) {
			return false;
		}
		return strpos($path, $this->excludedFolder) === 0;
	}
}
