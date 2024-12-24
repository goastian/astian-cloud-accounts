<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Service;

require __DIR__ . '/../../vendor/autoload.php';

use Exception;
use OCP\IConfig;

class SendGridService {
	private \SendGrid $sendGridClient;
	private $config;
	private $apiKey;

	public function __construct(IConfig $config) {
		$this->config = $config;
		$apiKey = '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';
		$this->apiKey = $apiKey;
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
	public function filterContactsByDateRange($contacts, $startDate, $endDate) {
		$startTimestamp = is_int($startDate) ? $startDate : strtotime($startDate);
		$endTimestamp = is_int($endDate) ? $endDate : strtotime($endDate);

		return array_filter($contacts, function ($contact) use ($startTimestamp, $endTimestamp) {
			// Check if `created_at` exists and is numeric
			if (!isset($contact['created_at']) || !is_numeric($contact['created_at'])) {
				echo "Skipping: Missing or non-numeric created_at.\n";
				return false;
			}

			$createdTimestamp = (int) $contact['created_at'];
			// Check if the timestamp falls within the range
			if ($createdTimestamp >= $startTimestamp && $createdTimestamp <= $endTimestamp) {
				return true;
			} else {
				return false;
			}
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

	public function refreshSendGridSegment($segmentId) {
		$data = [
			'user_time_zone' => 'America/Chicago'
		];

		try {
			$response = $this->sendGridClient->client
				->marketing()
				->segments()
				->_($segmentId)
				->refresh()
				->post($data);

			if ($response->statusCode() === 202) {
				return true;
			} else {
				throw new Exception("Failed to delete contacts: " . $response->body());
			}
		} catch (Exception $e) {
			throw new Exception('Caught exception: ' . $e->getMessage());
		}
	}
}
