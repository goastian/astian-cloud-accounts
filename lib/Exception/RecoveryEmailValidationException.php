<?php

namespace OCA\EcloudAccounts\Exception;

class RecoveryEmailValidationException extends \Exception {
	public function __construct($message = null, $code = 0) {
		parent::__construct($message, $code);
	}
}
