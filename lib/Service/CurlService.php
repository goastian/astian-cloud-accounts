<?php

/**
 * User: Srdi
 * Date: 18-Feb-17
 * Time: 20:42
 */


declare(strict_types=1);

namespace OCA\EcloudAccounts\Service;

use Exception;

class CurlService {

	private int $lastStatusCode = 0;
	/**
	 * GET alias for request method
	 *
	 * @param $url
	 * @param array $params
	 * @param array $headers
	 * @param array $userOptions
	 * @return mixed
	 */
	public function get($url, $params = array(), $headers = array(), $userOptions = array()) {
		return $this->request('GET', $url, $params, $headers, $userOptions);
	}

	/**
	 * POST alis for request method
	 *
	 * @param $url
	 * @param array $params
	 * @param array $headers
	 * @param array $userOptions
	 * @return mixed
	 */
	public function post($url, $params = array(), $headers = array(), $userOptions = array()) {
		return $this->request('POST', $url, $params, $headers, $userOptions);
	}

	public function delete($url, $params = [], $headers = [], $userOptions = []) {
		return $this->request('DELETE', $url, $params, $headers, $userOptions);
	}

	public function put($url, $params = [], $headers = [], $userOptions = []) {
		return $this->request('PUT', $url, $params, $headers, $userOptions);
	}

	/**
	 * @return int
	 */

	public function getLastStatusCode() : int {
		return $this->lastStatusCode;
	}

	private function buildPostData($params = [], $headers = []) {
		$jsonContent = in_array('Content-Type: application/json', $headers);
		if ($jsonContent) {
			$params = json_encode($params);
			if (json_last_error() !== JSON_ERROR_NONE) {
				throw new Exception('JSON encoding failed: ' . json_last_error_msg());
			}
			return $params;
		}
		
		$formContent = in_array('Content-Type: application/x-www-form-urlencoded', $headers);
		if ($formContent) {
			$params = http_build_query($params);
			return $params;
		}

		return $params;
	}

	/**
	 * Curl run request
	 *
	 * @param $method
	 * @param string $url
	 * @param array $params
	 * @param array $headers
	 * @param array $userOptions
	 * @return mixed
	 * @throws Exception
	 */
	private function request($method, $url, $params = array(), $headers = array(), $userOptions = array()) {
		$ch = curl_init();
		$method = strtoupper($method);
		$options = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => $headers
		);
		array_merge($options, $userOptions);
		switch ($method) {
			case 'GET':
				if ($params) {
					$url = $url . '?' . http_build_query($params);
				}
				break;
			case 'POST':
				$options[CURLOPT_POST] = true;
				$options[CURLOPT_POSTFIELDS] = $this->buildPostData($params, $headers);
				break;
			case 'DELETE':
				$options[CURLOPT_CUSTOMREQUEST] = "DELETE";
				if ($params) {
					$url = $url . '?' . http_build_query($params);
				}
				break;
			case 'PUT':
				$options[CURLOPT_CUSTOMREQUEST] = "PUT";
				$options[CURLOPT_POSTFIELDS] = $this->buildPostData($params, $headers);
			default:
				throw new Exception('Unsuported method.');
				break;
		}
		$options[CURLOPT_URL] = $url;

		curl_setopt_array($ch, $options);

		$response = curl_exec($ch);

		$this->lastStatusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		

		if ($errno = curl_errno($ch)) {
			$errorMessage = curl_strerror($errno);
			throw new Exception("Curl error $errno - $errorMessage");
		}

		curl_close($ch);

		return $response;
	}
}
