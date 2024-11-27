<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Filesystem;

use OC\Files\Cache\Cache;
use OC\Files\Storage\Storage;
use OC\Files\Storage\Wrapper\Wrapper;
use OCP\Files\ForbiddenException;
use OCP\Files\Storage\IStorage;
use OCP\Files\Storage\IWriteStreamStorage;

class StorageWrapper extends Wrapper implements IWriteStreamStorage {
	private const RECOVERY_FOLDER = 'files/Recovery';
	/**
	 * @param array $parameters
	 */
	public function __construct($parameters) {
		parent::__construct($parameters);
	}

	/**
	 * @throws ForbiddenException
	 */
	protected function checkFileAccess(string $path, bool $isDir = false): void {
		if ($this->isRecoveryFolder($path)) {
			throw new ForbiddenException('Access denied to the Recovery folder', false);
		}
	}

	/**
	 * Check if the path refers to the "Recovery" folder.
	 *
	 * @param string $path
	 * @return bool
	 */
	private function isRecoveryFolder(string $path): bool {
	  
		return strpos($path, '/' . self::RECOVERY_FOLDER) !== false;
	}

	/*
	 * Storage wrapper methods
	 */

	/**
	 * see http://php.net/manual/en/function.mkdir.php
	 *
	 * @param string $path
	 * @return bool
	 * @throws ForbiddenException
	 */
	public function mkdir($path) {
		$this->checkFileAccess($path, true);
	}

	/**
	 * see http://php.net/manual/en/function.rmdir.php
	 *
	 * @param string $path
	 * @return bool
	 * @throws ForbiddenException
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
		
		$this->checkFileAccess($path);
		
	}

	/**
	 * check if a file can be read
	 *
	 * @param string $path
	 * @return bool
	 */
	public function isReadable($path) {
		
		$this->checkFileAccess($path);
		
	}

	/**
	 * check if a file can be written to
	 *
	 * @param string $path
	 * @return bool
	 */
	public function isUpdatable($path) {
		
		$this->checkFileAccess($path);
		
	}

	/**
	 * check if a file can be deleted
	 *
	 * @param string $path
	 * @return bool
	 */
	public function isDeletable($path) {
		
		$this->checkFileAccess($path);
		
	}

	public function getPermissions($path) {
		
		$this->checkFileAccess($path);
		
	}

	/**
	 * see http://php.net/manual/en/function.file_get_contents.php
	 *
	 * @param string $path
	 * @return string
	 * @throws ForbiddenException
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
	 * @throws ForbiddenException
	 */
	public function file_put_contents($path, $data) {
		$this->checkFileAccess($path);
	}

	/**
	 * see http://php.net/manual/en/function.unlink.php
	 *
	 * @param string $path
	 * @return bool
	 * @throws ForbiddenException
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
	 * @throws ForbiddenException
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
	 * @throws ForbiddenException
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
	 * @throws ForbiddenException
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
	 * @throws ForbiddenException
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
	 * @throws ForbiddenException
	 */
	public function getDirectDownload($path) {
		$this->checkFileAccess($path);
	}

	/**
	 * @param IStorage $sourceStorage
	 * @param string $sourceInternalPath
	 * @param string $targetInternalPath
	 * @return bool
	 * @throws ForbiddenException
	 */
	public function copyFromStorage(IStorage $sourceStorage, $sourceInternalPath, $targetInternalPath) {
		$this->checkFileAccess($targetInternalPath);
	}

	/**
	 * @param IStorage $sourceStorage
	 * @param string $sourceInternalPath
	 * @param string $targetInternalPath
	 * @return bool
	 * @throws ForbiddenException
	 */
	public function moveFromStorage(IStorage $sourceStorage, $sourceInternalPath, $targetInternalPath) {
		$this->checkFileAccess($targetInternalPath);
	}

	/**
	 * @throws ForbiddenException
	 */
	public function writeStream(string $path, $stream, ?int $size = null): int {
		$this->checkFileAccess($path);
	}
}
