<?php

namespace OCA\EcloudAccounts\Middleware;

use OCA\EcloudAccounts\Controller\AccountController;
use OCA\EcloudAccounts\Exception\SecurityException;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Middleware;
use OCP\IRequest;
use OCP\ISession;

class SecurityMiddleware extends Middleware {
	private IRequest $request;
	private ISession $session;

	private const SESSION_METHODS = ['create', 'checkUsernameAvailable', 'captcha', 'verifyCaptcha'];
	public function __construct(IRequest $request, ISession $session) {
		$this->request = $request;
		$this->session = $session;
	}
	

	public function beforeController($controller, $methodName) {
		if (!$controller instanceof AccountController) {
			return;
		}
		
		if (!in_array($methodName, self::SESSION_METHODS)) {
			return;
		}

		if ($this->session->exists(AccountController::SESSION_USER_AGENT) && ($this->session->get(AccountController::SESSION_USER_AGENT) !== $this->request->getHeader('USER_AGENT')) ||
		$this->session->exists(AccountController::SESSION_IP_ADDRESS) && $this->session->get(AccountController::SESSION_IP_ADDRESS) !== $this->request->getRemoteAddress()
		) {
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
}
