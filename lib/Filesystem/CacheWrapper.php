<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Filesystem;

use OC\Files\Cache\Wrapper\CacheWrapper as Wrapper;
use OCP\Constants;
use OCP\Files\Cache\ICache;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\ForbiddenException;
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

	protected function formatCacheEntry($entry) {
		if (isset($entry['path']) && isset($entry['permissions'])) {
			try {
				throw new ForbiddenException('Access denied', false);
			} catch (ForbiddenException) {
				$entry['permissions'] &= $this->mask;
			}
		}
		return $entry;
	}

	public function insert($file, $data) {
		throw new \Exception('User data cache insert is disabled.');
	}

	public function update($id, $data) {
		throw new \Exception('User data cache update is disabled.');
	}

	public function remove($fileId) {
		throw new \Exception('User data cache removal is disabled.');
	}

	// Exclude specific folder and its files from search results
	public function searchQuery(ISearchQuery $searchQuery) {
		$results = parent::searchQuery($searchQuery);
		return array_filter($results, function ($entry) {
			return isset($entry['path']) && !$this->isExcludedPath($entry['path']);
		});
	}

	public function getCacheEntryFromSearchResult(ICacheEntry $rawEntry): ?ICacheEntry {
		if ($this->isExcludedPath($rawEntry->getPath())) {
			return null;
		}
		return parent::getCacheEntryFromSearchResult($rawEntry);
	}

	// Check if a path is within the excluded folder
	private function isExcludedPath(string $path): bool {
		return strpos($path, $this->excludedFolder) === 0;
	}
}
