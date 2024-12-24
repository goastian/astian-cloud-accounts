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
	 * Fetch contacts from a specific segment.
	 *
	 * @param string $segmentId
	 * @return array
	 * @throws Exception
	 */
	public function fetchContactsFromSegment(string $segmentId): array {
		$contacts = [];
		$page = 1;
		$pageSize = 100;

		do {
			$response = $this->sendGridClient->client->contactdb()->segments()->_($segmentId)->recipients()->get(null, [
				'page' => $page,
				'page_size' => $pageSize,
			]);

			if ($response->statusCode() !== 200) {
				throw new Exception("Failed to fetch contacts: " . $response->body());
			}

			$data = json_decode($response->body(), true);
			$contacts = array_merge($contacts, $data['recipients'] ?? []);

			if (count($data['recipients'] ?? []) < $pageSize) {
				break;
			}

			$page++;
		} while (true);

		return $contacts;
	}

	/**
	 * Filter contacts by creation date range.
	 *
	 * @param array $contacts
	 * @param string $startDate
	 * @param string $endDate
	 * @return array
	 */
	public function filterContactsByDateRange(array $contacts, string $startDate, string $endDate): array {
		$startTimestamp = strtotime($startDate);
		$endTimestamp = strtotime($endDate);

		return array_filter($contacts, function ($contact) use ($startTimestamp, $endTimestamp) {
			$createdTimestamp = isset($contact['created_at']) ? strtotime($contact['created_at']) : null;
			return $createdTimestamp && $createdTimestamp >= $startTimestamp && $createdTimestamp <= $endTimestamp;
		});
	}

	/**
	 * Delete contacts by IDs.
	 *
	 * @param array $contactIds
	 * @throws Exception
	 */
	public function deleteContacts(array $contactIds): void {
		if (empty($contactIds)) {
			throw new Exception("No contacts provided for deletion.");
		}

		$response = $this->sendGridClient->client->marketing()->contacts()->delete(null, [
			'ids' => implode(',', $contactIds),
		]);

		if ($response->statusCode() !== 202) {
			throw new Exception("Failed to delete contacts: " . $response->body());
		}
	}
}
