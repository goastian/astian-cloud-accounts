<?php

namespace OCA\EcloudAccounts\Middleware;

use OCA\EcloudAccounts\Controller\AccountController;
use OCA\EcloudAccounts\Exception\SecurityException;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Middleware;
use OCP\IRequest;
use OCP\ISession;

class AccountMiddleware extends Middleware {
	private IRequest $request;
	private ISession $session;

	private const SESSION_METHODS = ['create', 'checkUsernameAvailable', 'captcha', 'verifyCaptcha'];
	private const SESSION_USER_AGENT = 'USER_AGENT';
	private const SESSION_IP_ADDRESS = 'IP_ADDRESS';
	private const HEADER_USER_AGENT = 'USER_AGENT';

	public function __construct(IRequest $request, ISession $session) {
		$this->request = $request;
		$this->session = $session;
	}
	

	public function beforeController($controller, $methodName) {
		if (!$controller instanceof AccountController) {
			return;
		}
		
		// Add the required params to session for index
		if ($methodName === 'index') {
			$ipAddr = $this->request->getRemoteAddress();
			$userAgent = $this->request->getHeader(self::HEADER_USER_AGENT);
			$this->session->set(self::SESSION_IP_ADDRESS, $ipAddr);
			$this->session->set(self::SESSION_USER_AGENT, $userAgent);
			return;
		}

		if (!in_array($methodName, self::SESSION_METHODS)) {
			return;
		}

		$ipAddr = $this->request->getRemoteAddress();
		$userAgent = $this->request->getHeader(self::HEADER_USER_AGENT);
		if (!$this->isValidSessionParam(self::SESSION_IP_ADDRESS, $ipAddr)
		|| !$this->isValidSessionParam(self::SESSION_USER_AGENT, $userAgent)) {
			throw new SecurityException;
		}
	}

	public function afterException($controller, $methodName, \Exception $exception) {
		if (!$controller instanceof AccountController || !$exception instanceof SecurityException) {
			throw $exception;
		}
		
		$response = new DataResponse();
		$response->setStatus(401);
		return $response;
	}

	private function isValidSessionParam(string $sessionParam, string $value) : bool {
		if (!$this->session->exists($sessionParam)) {
			return false;
		}
		if ($this->session->get($sessionParam) !== $value) {
			return false;
		}
		return true;
	}
}
