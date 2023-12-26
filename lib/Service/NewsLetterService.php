<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Service;

require __DIR__ . '/../../vendor/autoload.php';

use Exception;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IUserManager;

class NewsLetterService {
	/** @var IUserManager */
	private $userManager;
	/** @var IConfig */
	private $config;
	/** @var CurlService */
	private $curl;
	/** @var ILogger */
	private $logger;

	public function __construct($appName, IUserManager $userManager, IConfig $config, CurlService $curlService, ILogger $logger) {
		$this->userManager = $userManager;
		$this->config = $config;
		$this->curl = $curlService;
		$this->logger = $logger;
	}

	public function setNewsletterSignup(bool $newsletterEos, bool $newsletterProduct, string $userEmail, string $language): void {
		try {
			if ($newsletterEos || $newsletterProduct) {
				$listIds = [];
				if ($newsletterEos) {
					$listIds[] = $newsletterListIds['eos'];
				}

				if ($newsletterProduct) {
					$listIds[] = $newsletterListIds['product'];
				}
				
				if (!empty($listIds)) {
					
					$this->signupForNewsletter($userEmail, $listIds, $language);
					
				}
			}
		} catch (Exception $e) {
			$this->logger->error('Signup for newsletter failed: ' . $e->getMessage());
		}
	}

	private function signupForNewsletter(string $userEmail, array $listIds, string $userLanguage): void {
		$newsletterApiUrl = $this->config->getSystemValue('newsletter_base_url', '');

		if (empty($newsletterApiUrl)) {
			return;
		}

		$endpoint = '/api/signup';
		$url = $newsletterApiUrl . $endpoint;

		$params = [
			'email' => $userEmail,
			'list_ids' => $listIds,
			'contact_language' => $userLanguage
		];
		$params_string = json_encode($params);
		$headers = [
			'Content-Type: application/json',
			'Content-Length: ' . strlen($params_string)
		];
		$this->curl->post($url, $params, $headers);

		if ($this->curl->getLastStatusCode() !== 200) {
			throw new Exception('Error adding email ' . $userEmail . ' to newsletter app');
		}
	}
}
