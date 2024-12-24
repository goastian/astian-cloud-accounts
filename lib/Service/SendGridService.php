<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Service;

require __DIR__ . '/../../vendor/autoload.php';

use Exception;
use OCP\IConfig;

class SendGridService {
	private \SendGrid $sendGridClient;
	private $config;

	public function __construct(IConfig $config) {
		$this->config = $config;
		$apiKey = $this->config->getSystemValue('sendgrid_api_key', '');
		$this->sendGridClient = new \SendGrid($apiKey);
	}

	/**
	 * Fetch contacts created between the specified date range.
	 *
	 * @param string $startDate Format: 'Y-m-d H:i:s'
	 * @param string $endDate Format: 'Y-m-d H:i:s'
	 * @return array
	 * @throws Exception
	 */
	public function fetchContactsByDateRange(string $startDate, string $endDate): array {
		$startTimestamp = strtotime($startDate);
		$endTimestamp = strtotime($endDate);

		$contacts = [];
		$page = 1;
		$pageSize = 100;

		do {
			$response = $this->sendGridClient->client->marketing()->contacts()->get(null, $page, $pageSize);
			if ($response->statusCode() !== 200) {
				throw new Exception("Error fetching contacts: " . $response->body());
			}

			$result = json_decode($response->body(), true);
			$fetchedContacts = $result['result'] ?? [];

			foreach ($fetchedContacts as $contact) {
				$createdTimestamp = strtotime($contact['created_at']);
				if ($createdTimestamp >= $startTimestamp && $createdTimestamp <= $endTimestamp) {
					$contacts[] = $contact;
				}
			}

			if (count($fetchedContacts) < $pageSize) {
				break;
			}

			$page++;
		} while (true);

		return $contacts;
	}

	/**
	 * Delete contacts by their IDs.
	 *
	 * @param array $contactIds
	 * @return void
	 * @throws Exception
	 */
	public function deleteContacts(array $contactIds): void {
		if (empty($contactIds)) {
			throw new Exception("No contacts provided for deletion.");
		}

		$requestBody = ['ids' => $contactIds];
		$response = $this->sendGridClient->client->marketing()->contacts()->delete(null, $requestBody);

		if ($response->statusCode() !== 202) {
			throw new Exception("Failed to delete contacts. HTTP Code: " . $response->statusCode() . ". Response: " . $response->body());
		}
	}
}
