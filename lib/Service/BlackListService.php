<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Service;

use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\ILogger;

class BlackListService {
	private IAppData $appData;
	private ILogger $logger;
	private const BLACKLISTED_DOMAINS_FOLDER_NAME = '/';
	private const BLACKLISTED_DOMAINS_FILE_NAME = 'blacklisted_domains.json';
	private const BLACKLISTED_DOMAINS_URL = 'https://raw.githubusercontent.com/disposable/disposable-email-domains/master/domains.json';

	public function __construct(ILogger $logger, IAppData $appData) {
		$this->appData = $appData;
		$this->logger = $logger;
	}

	/**
	 * Check if an email domain is blacklisted against a JSON list of disposable email domains.
	 *
	 * @param string $email The email address to check.
	 * @return bool True if the email domain is blacklisted, false otherwise.
	 */
	public function isBlacklistedEmail(string $email): bool {
		if (!$this->ensureDocumentsFolder()) {
			return false;
		}
		$blacklistedDomains = $this->getBlacklistedDomainData();
		if (empty($blacklistedDomains)) {
			return false;
		}
		$emailParts = explode('@', $email);
		$emailDomain = strtolower(end($emailParts));
		return in_array($emailDomain, $blacklistedDomains);
	}
	/**
	 * Update the blacklisted domains data by fetching it from a URL and saving it locally.
	 *
	 * @return void
	 */
	public function updateBlacklistedDomains(): void {
		$blacklisted_domain_url = self::BLACKLISTED_DOMAINS_URL;
		$json_data = file_get_contents($blacklisted_domain_url);
		$this->setBlacklistedDomainsData($json_data);
	}
	/**
	 * Store blacklisted domain data in a file within AppData.
	 *
	 * @param string $data The data to be stored in the file.
	 */
	private function setBlacklistedDomainsData(string $data): void {
		$file = $this->getBlacklistedDomainsFilePath();
		$file->putContent($data);
	}
	/**
	 * Retrieve the blacklisted domain file path
	 *
	 */
	private function getBlacklistedDomainsFilePath() {
		$foldername = self::BLACKLISTED_DOMAINS_FOLDER_NAME;
		try {
			$currentFolder = $this->appData->getFolder($foldername);
		} catch (NotFoundException $e) {
			$currentFolder = $this->appData->newFolder($foldername);
		}
		$filename = self::BLACKLISTED_DOMAINS_FILE_NAME;
		if ($currentFolder->fileExists($filename)) {
			return $currentFolder->getFile($filename);
		}
		return $currentFolder->newFile($filename);
	}
	/**
	 * Retrieve the blacklisted domain data.
	 *
	 * @return array The array of blacklisted domains.
	 */
	public function getBlacklistedDomainData(): array {
		$foldername = self::BLACKLISTED_DOMAINS_FOLDER_NAME;
		$document = self::BLACKLISTED_DOMAINS_FILE_NAME;
		try {
			$blacklistedDomainsInJson = $this->appData->getFolder($foldername)->getFile((string) $document)->getContent();
			if (empty($blacklistedDomainsInJson)) {
				return [];
			}
			return json_decode($blacklistedDomainsInJson, true);
		} catch (NotFoundException $e) {
			$this->logger->warning('Blacklisted domains file '.$document.' not found!');
			return [];
		}
		
	}
	/**
	 * Ensure the specified folder exists within AppData.
	 *
	 */
	private function ensureDocumentsFolder(): bool {
		$foldername = self::BLACKLISTED_DOMAINS_FOLDER_NAME;
		try {
			$this->appData->getFolder($foldername);
		} catch (NotFoundException $e) {
			$this->logger->error('Blacklisted domains folder '.$foldername.' not found!');
			return false;
		} catch (\RuntimeException $e) {
			$this->logger->error($e);
			return false;
		}
		return true;
	}
}
