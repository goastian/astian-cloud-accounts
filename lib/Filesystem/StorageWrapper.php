<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Filesystem;

use OC\Files\Cache\Cache;
use OC\Files\Storage\Storage;
use OC\Files\Storage\Wrapper\Wrapper;
use OC\ServiceUnavailableException;
use OCP\Files\Storage\IStorage;
use OCP\Files\Storage\IWriteStreamStorage;

class StorageWrapper extends Wrapper implements IWriteStreamStorage {
	/**
	 * @param array $parameters
	 */
	public function __construct($parameters) {
		parent::__construct($parameters);
	}

	/**
	 * @throws ServiceUnavailableException
	 */
	protected function checkFileAccess(string $path, bool $isDir = false): void {
		throw new ServiceUnavailableException('Access denied', false);
	}

	/*
	 * Storage wrapper methods
	 */

	/**
	 * see http://php.net/manual/en/function.mkdir.php
	 *
	 * @param string $path
	 * @return bool
	 * @throws ServiceUnavailableException
	 */
	public function mkdir($path) {
		$this->checkFileAccess($path, true);
	}

	/**
	 * see http://php.net/manual/en/function.rmdir.php
	 *
	 * @param string $path
	 * @return bool
	 * @throws ServiceUnavailableException
	 */
	public function rmdir($path) {
		$this->checkFileAccess($path, true);
	}

	/**
	 * check if a file can be created in $path
	 *
	 * @param string $path
	 * @return bool
	 */
	public function isCreatable($path) {
		try {
			$this->checkFileAccess($path);
		} catch (ServiceUnavailableException $e) {
			return false;
		}
	}

	/**
	 * check if a file can be read
	 *
	 * @param string $path
	 * @return bool
	 */
	public function isReadable($path) {
		try {
			$this->checkFileAccess($path);
		} catch (ServiceUnavailableException $e) {
			return false;
		}
	}

	/**
	 * check if a file can be written to
	 *
	 * @param string $path
	 * @return bool
	 */
	public function isUpdatable($path) {
		try {
			$this->checkFileAccess($path);
		} catch (ServiceUnavailableException $e) {
			return false;
		}
	}

	/**
	 * check if a file can be deleted
	 *
	 * @param string $path
	 * @return bool
	 */
	public function isDeletable($path) {
		try {
			$this->checkFileAccess($path);
		} catch (ServiceUnavailableException $e) {
			return false;
		}
	}

	public function getPermissions($path) {
		try {
			$this->checkFileAccess($path);
		} catch (ServiceUnavailableException $e) {
			return $this->mask;
		}
	}

	/**
	 * see http://php.net/manual/en/function.file_get_contents.php
	 *
	 * @param string $path
	 * @return string
	 * @throws ServiceUnavailableException
	 */
	public function file_get_contents($path) {
		$this->checkFileAccess($path);
	}

	/**
	 * see http://php.net/manual/en/function.file_put_contents.php
	 *
	 * @param string $path
	 * @param string $data
	 * @return bool
	 * @throws ServiceUnavailableException
	 */
	public function file_put_contents($path, $data) {
		$this->checkFileAccess($path);
	}

	/**
	 * see http://php.net/manual/en/function.unlink.php
	 *
	 * @param string $path
	 * @return bool
	 * @throws ServiceUnavailableException
	 */
	public function unlink($path) {
		$this->checkFileAccess($path);
	}

	/**
	 * see http://php.net/manual/en/function.rename.php
	 *
	 * @param string $path1
	 * @param string $path2
	 * @return bool
	 * @throws ServiceUnavailableException
	 */
	public function rename($path1, $path2) {
		$this->checkFileAccess($path1);
		$this->checkFileAccess($path2);
	}

	/**
	 * see http://php.net/manual/en/function.copy.php
	 *
	 * @param string $path1
	 * @param string $path2
	 * @return bool
	 * @throws ServiceUnavailableException
	 */
	public function copy($path1, $path2) {
		$this->checkFileAccess($path1);
		$this->checkFileAccess($path2);
	}

	/**
	 * see http://php.net/manual/en/function.fopen.php
	 *
	 * @param string $path
	 * @param string $mode
	 * @return resource
	 * @throws ServiceUnavailableException
	 */
	public function fopen($path, $mode) {
		$this->checkFileAccess($path);
	}

	/**
	 * see http://php.net/manual/en/function.touch.php
	 * If the backend does not support the operation, false should be returned
	 *
	 * @param string $path
	 * @param int $mtime
	 * @return bool
	 * @throws ServiceUnavailableException
	 */
	public function touch($path, $mtime = null) {
		$this->checkFileAccess($path);
	}

	/**
	 * get a cache instance for the storage
	 *
	 * @param string $path
	 * @param Storage (optional) the storage to pass to the cache
	 * @return Cache
	 */
	public function getCache($path = '', $storage = null) {
		if (!$storage) {
			$storage = $this;
		}
		$cache = $this->storage->getCache($path, $storage);
		return new CacheWrapper($cache, $storage);
	}

	/**
	 * A custom storage implementation can return an url for direct download of a give file.
	 *
	 * For now the returned array can hold the parameter url - in future more attributes might follow.
	 *
	 * @param string $path
	 * @return array
	 * @throws ServiceUnavailableException
	 */
	public function getDirectDownload($path) {
		$this->checkFileAccess($path);
	}

	/**
	 * @param IStorage $sourceStorage
	 * @param string $sourceInternalPath
	 * @param string $targetInternalPath
	 * @return bool
	 * @throws ServiceUnavailableException
	 */
	public function copyFromStorage(IStorage $sourceStorage, $sourceInternalPath, $targetInternalPath) {
		$this->checkFileAccess($targetInternalPath);
	}

	/**
	 * @param IStorage $sourceStorage
	 * @param string $sourceInternalPath
	 * @param string $targetInternalPath
	 * @return bool
	 * @throws ServiceUnavailableException
	 */
	public function moveFromStorage(IStorage $sourceStorage, $sourceInternalPath, $targetInternalPath) {
		$this->checkFileAccess($targetInternalPath);
	}

	/**
	 * @throws ServiceUnavailableException
	 */
	public function writeStream(string $path, $stream, ?int $size = null): int {
		$this->checkFileAccess($path);
	}
}
