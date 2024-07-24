<?php

namespace OCA\EcloudAccounts\Service;

use OCP\IConfig;
use OCP\ISession;

class HCaptchaService {
	private ISession $session;
	private IConfig $config;
	private CurlService $curl;

	private const VERIFY_URL = 'https://hcaptcha.com/siteverify';

	public function __construct(ISession $session, IConfig $config, CurlService $curlService) {
		$this->session = $session;
		$this->config = $config;
		$this->curl = $curlService;
	}

	public function verify(string $token) : bool {
		$secret = $this->config->getSystemValue('ecloud-accounts.hcaptcha_secret');
		$data = [
			'response' => $token,
			'secret' => $secret
		];

		$data = http_build_query($data);
		$response = $this->curl->post(self::VERIFY_URL, $data);
		$response = json_decode($response, true);
		
		return $response['success'];
	}
}
