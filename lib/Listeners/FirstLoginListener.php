<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Listeners;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\ILogger;

class FirstLoginListener implements IEventListener {
	private $logger;
	public function __construct(ILogger $logger) {
		$this->logger = $logger;
	}

	public function handle(Event $event): void {
		$this->logger->error("FIRST TIME LOGIN LISTENER CALLED");
	}

	/**
	 * Summary of sendWelcomeEmail
	 * @param string $displayname
	 * @param string $username
	 * @return bool
	 */
	// public function sendWelcomeEmail(string $displayname, string $username) {
	// 	$title = 'Welcome to Murena Email Service!';
	// 	$description = 'Dear '.$displayname.',\nWe are thrilled to welcome you to Murena Email Service! It\'s a pleasure to have you on board and we are excited about the journey ahead.
	// 	\nAt Murena, we are committed to providing you with a seamless and secure email experience. Our user-friendly interface, advanced features, and robust security measures have been designed to ensure that your communication remains efficient, effective, and protected.
	// 	\nAs you explore our platform, you will discover a range of features tailored to meet your email needs. From easy-to-use organization tools to powerful search capabilities, we aim to enhance your productivity and streamline your communication.
	// 	\nYour privacy and security are of utmost importance to us. Rest assured that we employ state-of-the-art encryption and multi-layered security protocols to safeguard your sensitive information.
	// 	\nShould you require any assistance or have any questions, our dedicated support team is here to help. Don\'t hesitate to reach out at [support email] for any inquiries or concerns.
	// 	\nOnce again, welcome to the Murena Email Service community! We\'re excited to have you on board and look forward to serving your email needs.
	// 	\nBest regards,
	// 	\nMurena Team';
	// 	$domain = $this->config->getSystemValue('main_domain', '');
	// 	$fromEmail = $username.'@'.$domain;

	// 	$template = $this->mailer->createEMailTemplate('account.SendWelcomeEmail', []);
	// 	$template->addHeader();
	// 	$template->setSubject($title);
	// 	$template->addBodyText(htmlspecialchars($description), $description);

	// 	$message = $this->mailer->createMessage();
	// 	$message->setFrom([Util::getDefaultEmailAddress('noreply')]);
	// 	$message->setReplyTo([$fromEmail => $displayname]);
	// 	$message->setTo([$fromEmail]);
	// 	$message->useTemplate($template);

	// 	$this->mailer->send($message);
	// 	return true;
	// }
}
