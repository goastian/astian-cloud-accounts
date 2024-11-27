<?php


declare(strict_types=1);

namespace OCA\EcloudAccounts\Filesystem;

use OC\Files\Cache\Cache;
use OC\Files\Storage\Wrapper\Wrapper;
use OCP\Constants;
use OCP\Files\Cache\ICache;
use OCP\Files\ForbiddenException;
use OCP\Files\Storage\IStorage;
use OCP\Files\Storage\IWriteStreamStorage;

class StorageWrapper extends Wrapper implements IWriteStreamStorage {
	private const RECOVERY_FOLDER = 'files/Recovery';
	protected readonly int $mask;

	/**
	 * Constructor
	 *
	 * @param array $parameters
	 */
	public function __construct($parameters) {
		parent::__construct($parameters);

		// Set up the permission mask to block specific permissions
		$this->mask = Constants::PERMISSION_ALL
			& ~Constants::PERMISSION_READ
			& ~Constants::PERMISSION_CREATE
			& ~Constants::PERMISSION_UPDATE
			& ~Constants::PERMISSION_DELETE;
	}

	/**
	 * @throws ForbiddenException
	 */
	protected function checkFileAccess(string $path, ?bool $isDir = null): void {
		if ($this->isRecoveryFolder($path)) {
			// Block access to the "Recovery" folder
			throw new ForbiddenException('Access denied to the Recovery folder', false);
		}

		// If you need additional access checks for other folders, you can add here.
	}

	/**
	 * Check if the path refers to the "Recovery" folder.
	 *
	 * @param string $path
	 * @return bool
	 */
	private function isRecoveryFolder(string $path): bool {
		// Ensure the path matches exactly or starts with "files/Recovery"
		return strpos($path, '/' . self::RECOVERY_FOLDER) === 0;
	}

	/*
	 * Storage wrapper methods
	 */

	public function mkdir($path): bool {
		$this->checkFileAccess($path, true);
		return $this->storage->mkdir($path);
	}

	public function rmdir($path): bool {
		$this->checkFileAccess($path, true);
		return $this->storage->rmdir($path);
	}

	public function isCreatable($path): bool {
		try {
			$this->checkFileAccess($path);
		} catch (ForbiddenException $e) {
			return false;
		}
		return $this->storage->isCreatable($path);
	}

	public function isReadable($path): bool {
		try {
			$this->checkFileAccess($path);
		} catch (ForbiddenException $e) {
			return false;
		}
		return $this->storage->isReadable($path);
	}

	public function isUpdatable($path): bool {
		try {
			$this->checkFileAccess($path);
		} catch (ForbiddenException $e) {
			return false;
		}
		return $this->storage->isUpdatable($path);
	}

	public function isDeletable($path): bool {
		try {
			$this->checkFileAccess($path);
		} catch (ForbiddenException $e) {
			return false;
		}
		return $this->storage->isDeletable($path);
	}

	public function getPermissions($path): int {
		try {
			$this->checkFileAccess($path);
		} catch (ForbiddenException $e) {
			return $this->mask;
		}
		return $this->storage->getPermissions($path);
	}

	public function file_get_contents($path): string|false {
		$this->checkFileAccess($path, false);
		return $this->storage->file_get_contents($path);
	}

	public function file_put_contents($path, $data): int|float|false {
		$this->checkFileAccess($path, false);
		return $this->storage->file_put_contents($path, $data);
	}

	public function unlink($path): bool {
		$this->checkFileAccess($path, false);
		return $this->storage->unlink($path);
	}

	public function rename($source, $target): bool {
		$isDir = $this->is_dir($source);
		$this->checkFileAccess($source, $isDir);
		$this->checkFileAccess($target, $isDir);
		return $this->storage->rename($source, $target);
	}

	public function copy($source, $target): bool {
		$isDir = $this->is_dir($source);
		$this->checkFileAccess($source, $isDir);
		$this->checkFileAccess($target, $isDir);
		return $this->storage->copy($source, $target);
	}

	public function fopen($path, $mode) {
		$this->checkFileAccess($path, false);
		return $this->storage->fopen($path, $mode);
	}

	public function touch($path, $mtime = null): bool {
		$this->checkFileAccess($path, false);
		return $this->storage->touch($path, $mtime);
	}

	/**
	 * Get a cache instance for the storage
	 *
	 * @param string $path
	 * @param Storage (optional) the storage to pass to the cache
	 * @return Cache
	 */
	public function getCache($path = '', $storage = null): ICache {
		if (!$storage) {
			$storage = $this;
		}
		$cache = $this->storage->getCache($path, $storage);
		return new CacheWrapper($cache, $storage);
	}

	public function getDirectDownload($path): array|false {
		
		$this->checkFileAccess($path, false);
		return $this->storage->getDirectDownload($path);
	}

	public function copyFromStorage(IStorage $sourceStorage, $sourceInternalPath, $targetInternalPath): bool {
		if ($sourceStorage === $this) {
			return $this->copy($sourceInternalPath, $targetInternalPath);
		}
		$this->checkFileAccess($targetInternalPath);
		return $this->storage->copyFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
	}

	public function moveFromStorage(IStorage $sourceStorage, $sourceInternalPath, $targetInternalPath): bool {
		if ($sourceStorage === $this) {
			return $this->rename($sourceInternalPath, $targetInternalPath);
		}
		$this->checkFileAccess($targetInternalPath);
		return $this->storage->moveFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
	}

	public function writeStream(string $path, $stream, ?int $size = null): int {
		$this->checkFileAccess($path, false);
		return $this->storage->writeStream($path, $stream, $size);
	}
}
