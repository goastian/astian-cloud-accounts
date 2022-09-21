<?php
/**
 * @copyright Copyright (c) 2020 Thomas Citharel <nextcloud@tcit.fr>
 *
 * @author Thomas Citharel <nextcloud@tcit.fr>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\EcloudAccounts\Service;

use Exception;
use OCA\EcloudAccounts\MissingEmailException;
use OCP\Defaults;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Mail\IEMailTemplate;
use OCP\Mail\IMailer;
use OCP\Mail\IMessage;
use OCP\Security\ISecureRandom;
use OCP\Util;

class ConfirmationService {
	private IL10N $l10n;
	private ISecureRandom $random;
	private IMailer $mailer;
	private Defaults $defaults;
	private IURLGenerator $urlGenerator;

	public function __construct(IL10N $l10n, ISecureRandom $random, IMailer $mailer, Defaults $defaults, IURLGenerator $urlGenerator) {
		$this->l10n = $l10n;
		$this->random = $random;
		$this->mailer = $mailer;
		$this->defaults = $defaults;
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * @param IUser $user
	 * @return string
	 * @throws MissingEmailException
	 * @throws Exception
	 */
	public function sendConfirmationEmail(IUser $user): string {
		$recipientName = $user->getDisplayName();
		$recipient = $user->getEMailAddress();
		if ($recipient && $recipientName) {
			$to = [$recipient => $recipientName];
		} elseif ($recipient) {
			$to = [$recipient];
		} else {
			throw new MissingEmailException();
		}


		$token = $this->random->generate(16, ISecureRandom::CHAR_HUMAN_READABLE);
		$template = $this->createTemplate($token);
		$message = $this->createMessage($to, $template);
		$this->mailer->send($message);
		return $token;
	}

	/**
	 * @param array $recipients
	 * @param IEMailTemplate $template
	 * @return IMessage
	 */
	private function createMessage(array $recipients, IEMailTemplate $template): IMessage {
		return $this->mailer->createMessage()
				->setFrom([Util::getDefaultEmailAddress('noreply') => $this->defaults->getName()])
				->setTo($recipients)
				->useTemplate($template);
	}

	/**
	 * @param string $token
	 * @return IEMailTemplate
	 */
	private function createTemplate(string $token): IEMailTemplate {
		$serverName = $this->defaults->getName();
		$template = $this->mailer->createEMailTemplate('drop_account.delete_confirmation');
		$template->setSubject($this->l10n->t('Confirm your account deletion on %s', [$serverName]));
		$template->addHeader();
		$template->addHeading($this->l10n->t('Account deletion confirmation for %s', [$serverName]));
		$template->addBodyText($this->l10n->t('Hello,'));
		$template->addBodyText($this->l10n->t('Someone - probably you - asked to delete their account on %s.', [$serverName]));
		$template->addBodyText($this->l10n->t('To confirm the account deletion, you may click on the button below.'));
		$template->addBodyText($this->l10n->t("If you didn't request this email, you should contact your administrator as soon as possible, someone may be accessing your account without your knowledge."));
		$template->addBodyButton($this->l10n->t('Delete account'), $this->getURLFromToken($token));
		$template->addBodyText($this->l10n->t('Cheers!'));
		$template->addFooter();
		return $template;
	}

	/**
	 * Get URL from public sharing token
	 *
	 * @param string $token
	 * @return string
	 */
	private function getURLFromToken(string $token): string {
		return $this->urlGenerator->linkToRouteAbsolute('drop_account.account.confirm', [
			'token' => $token,
		]);
	}
}
