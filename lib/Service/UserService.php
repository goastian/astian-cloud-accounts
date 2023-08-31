<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Service;

use OCP\IUserManager;
use OCP\IUser;
use OCP\IConfig;
use OCP\ILogger;
use OCA\EcloudAccounts\AppInfo\Application;
use OCP\Mail\IMailer;
use OCP\Util;

use UnexpectedValueException;

class UserService {
	/** @var IUserManager */
	private $userManager;

	/** @var array */
	private $appConfig;

	/** @var IConfig */
	private $config;

	private $curl;
	private $mailer;

	public function __construct($appName, IUserManager $userManager, IConfig $config, CurlService $curlService, ILogger $logger, IMailer $mailer) {
		$this->userManager = $userManager;
		$this->config = $config;
		$this->appConfig = $this->config->getSystemValue($appName);
		$this->curl = $curlService;
		$this->logger = $logger;
		$this->mailer = $mailer;
	}


	public function getConfigValue(string $key, mixed $default = false) {
		if (!empty($this->appConfig[$key])) {
			return $this->appConfig[$key];
		}
		return $default;
	}


	public function userExists(string $uid): bool {
		$exists = $this->userManager->userExists($uid);
		if ($exists) {
			return $exists;
		}

		$backends = $this->userManager->getBackends();
		foreach ($backends as $backend) {
			if ($backend->getBackendName() === 'LDAP') {
				$access = $backend->getLDAPAccess($uid);
				$users = $access->fetchUsersByLoginName($uid) ;
				if (count($users) > 0) {
					$exists = true;
				}
			}
		}
		return $exists;
	}

	public function getUser(string $uid): ?IUser {
		return $this->userManager->get($uid);
	}

	public function setRecoveryEmail(string $uid, string $recoveryEmail): bool {
		try {
			$this->config->setUserValue($uid, 'email-recovery', 'recovery-email', $recoveryEmail);
			return true;
		} catch (UnexpectedValueException $e) {
			return false;
		}
	}

	public function getHMEAliasesFromConfig($uid) : array {
		$aliases = $this->config->getUserValue($uid, 'hide-my-email', 'email-aliases', []);
		if (!empty($aliases)) {
			$aliases = json_decode($aliases, true);
		}
		return $aliases;
	}

	public function addHMEAliasInConfig($uid, $alias) : bool {
		$aliases = $this->getHMEAliasesFromConfig($uid);
		$aliases[] = $alias;
		$aliases = json_encode($aliases);
		try {
			$this->config->setUserValue($uid, 'hide-my-email', 'email-aliases', $aliases);
			return true;
		} catch (UnexpectedValueException $e) {
			return false;
		}
	}

	/**
	 * Once NC deleted the account,
	 * perform specific ecloud selfhosting actions
	 * post delete action is delegated to the welcome container
	 *
	 * @param $userID string
	 * @param $welcomeDomain string main NC domain (welcome container)
	 * @param $welcomeSecret string generated at ecloud selfhosting install and added as a custom var in NC's config
	 * @return mixed response of the external endpoint
	 */
	public function ecloudDelete(string $userID, string $welcomeDomain, string $welcomeSecret, string $email, bool $isUserOnLDAP = false) {
		$endpoint = '/postDelete.php';
		if ($isUserOnLDAP) {
			$endpoint = '/postDeleteLDAP.php';
		}
		$postDeleteUrl = "https://" . $welcomeDomain . $endpoint;
		/**
		 * send action to docker_welcome
		 * Handling the non NC part of deletion process
		 */
		try {
			$params = [
				'sec' => $welcomeSecret,
				'uid' => $userID,
				'email' => $email
			];

			$answer = $this->curl->post($postDeleteUrl, $params);

			return json_decode($answer, true);
		} catch (\Exception $e) {
			$this->logger->error('There has been an issue while contacting the external deletion script');
			$this->logger->logException($e, ['app' => Application::APP_ID]);
		}

		return null;
	}
	public function sendWelcomeEmail(string $displayname, string $fromEmail) {
		$title = 'Welcome to Murena Email Service!';
		$description = 'Dear '.$displayname.',<br />We are thrilled to welcome you to Murena Email Service! It\'s a pleasure to have you on board and we are excited about the journey ahead.
		<br />At Murena, we are committed to providing you with a seamless and secure email experience. Our user-friendly interface, advanced features, and robust security measures have been designed to ensure that your communication remains efficient, effective, and protected.
		<br />As you explore our platform, you will discover a range of features tailored to meet your email needs. From easy-to-use organization tools to powerful search capabilities, we aim to enhance your productivity and streamline your communication.
		<br />Your privacy and security are of utmost importance to us. Rest assured that we employ state-of-the-art encryption and multi-layered security protocols to safeguard your sensitive information.
		<br />Should you require any assistance or have any questions, our dedicated support team is here to help. Don\'t hesitate to reach out at [support email] for any inquiries or concerns.
		<br />Once again, welcome to the Murena Email Service community! We\'re excited to have you on board and look forward to serving your email needs.
		<br />Best regards,
		<br />Murena Team';

		$template = $this->mailer->createEMailTemplate('account.SendWelcomeEmail', []);
		$template->addHeader();
		$template->setSubject($title);
		$template->addBodyText(htmlspecialchars($description), $description);

		$message = $this->mailer->createMessage();
		$message->setFrom([Util::getDefaultEmailAddress('noreply')]);
		$message->setReplyTo([$fromEmail => $displayname]);
		$message->setTo([$fromEmail]);
		$message->useTemplate($template);

		$this->mailer->send($message);
		return true;
	}
}
