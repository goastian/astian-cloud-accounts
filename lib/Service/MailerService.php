<?php
/**
 * @copyright Copyright (c) 2021 Thomas Citharel <nextcloud@tcit.fr>
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

use DateInterval;
use Exception;
use OCA\EcloudAccounts\AppInfo\Application;
use OCA\EcloudAccounts\MissingEmailException;
use OCP\Defaults;
use OCP\IConfig;
use OCP\IDateTimeFormatter;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Mail\IEMailTemplate;
use OCP\Mail\IMailer;
use OCP\Mail\IMessage;
use OCP\Util;

class MailerService {
	private IL10N $l10n;
	private IMailer $mailer;
	private Defaults $defaults;
	private IConfig $config;
	private IDateTimeFormatter $dateFormatter;
	private IURLGenerator $urlGenerator;

	public function __construct(IConfig $config, IL10N $l10n, IMailer $mailer, Defaults $defaults, IURLGenerator $urlGenerator, IDateTimeFormatter $dateFormatter) {
		$this->config = $config;
		$this->l10n = $l10n;
		$this->mailer = $mailer;
		$this->defaults = $defaults;
		$this->urlGenerator = $urlGenerator;
		$this->dateFormatter = $dateFormatter;
	}

	/**
	 * @param IUser $user
	 * @throws MissingEmailException
	 * @throws Exception
	 */
	public function sendFinalEmail(IUser $user): void {
		$to = $this->getRecipients($user);
		$template = $this->createFinalTemplate();
		$message = $this->createMessage($to, $template);
		$this->mailer->send($message);
	}

	/**
	 * @throws MissingEmailException
	 * @throws Exception
	 */
	public function sendReactivationEmail(IUser $user): void {
		$to = $this->getRecipients($user);
		$template = $this->createReactivationTemplate($user);
		$message = $this->createMessage($to, $template);
		$this->mailer->send($message);
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
	 * @throws MissingEmailException
	 */
	private function getRecipients(IUser $user): array {
		$recipientName = $user->getDisplayName();
		$recipient = $user->getEMailAddress();
		if ($recipient && $recipientName) {
			return [$recipient => $recipientName];
		}

		if ($recipient) {
			return [$recipient];
		}
		throw new MissingEmailException();
	}

	/**
	 * @return IEMailTemplate
	 * @throws Exception
	 */
	private function createFinalTemplate(): IEMailTemplate {
		$scheduled = $this->config->getAppValue(Application::APP_NAME, 'delayPurge', 'no') === 'yes';
		$purgeHours = (int) $this->config->getAppValue(Application::APP_NAME, 'delayPurgeHours', '24');
		$purgeDays = intdiv($purgeHours, 24);

		$expirationDate = new \DateTime();
		$duration = 'PT' . $purgeHours . 'H';
		$expirationDate->add(new DateInterval($duration));
		$formattedDate = $this->dateFormatter->formatDate($expirationDate);

		$serverName = $this->defaults->getName();
		$template = $this->mailer->createEMailTemplate('drop_account.deletion');

		if ($scheduled) {
			$template->setSubject($this->l10n->t('Your account on %s has been disabled', [$serverName]));
		} else {
			$template->setSubject($this->l10n->t('Your account on %s has been deleted', [$serverName]));
		}
		$template->addHeader();
		if ($scheduled) {
			$template->addHeading($this->l10n->t('Account deletion on %s scheduled', [$serverName]));
		} else {
			$template->addHeading($this->l10n->t('Account on %s deleted', [$serverName]));
		}
		$template->addBodyText($this->l10n->t('Hi,'));
		if ($scheduled) {
			if ($purgeDays === 0) {
				$template->addBodyText($this->l10n->n('Your data will be permanently deleted in %d hour.', 'Your data will be permanently deleted in %d hours.', $purgeHours, [$purgeHours]));
			} else {
				$template->addBodyText($this->l10n->n('Your data will be permanently deleted in %d day, on %s.', 'Your data will be permanently deleted in %d days, on %s.', $purgeDays, [$purgeDays, $formattedDate, $purgeDays, $formattedDate]));
			}

			$template->addBodyText($this->l10n->t('You can cancel your account deletion by contacting an administrator before the %s.', [$formattedDate]));
		} else {
			$template->addBodyText($this->l10n->t('We confirm all your personal data on %s was permanently deleted.', [$serverName]));
		}
		$template->addBodyText($this->l10n->t('Thanks for using our service.'));
		$template->addBodyText($this->l10n->t('Cheers!'));
		$template->addFooter();
		return $template;
	}

	private function createReactivationTemplate(IUser $user): IEMailTemplate {
		$serverName = $this->defaults->getName();
		$template = $this->mailer->createEMailTemplate('drop_account.reactivation');
		$template->setSubject($this->l10n->t('Your account on %s has been reactivated', [$serverName]));
		$template->addHeader();
		$template->addHeading($this->l10n->t('Access to your account %1$s has been restored', [$user->getUID()]));
		$template->addBodyText($this->l10n->t('An administrator re-enabled your account on %s before it was destroyed.', [$serverName]));
		$template->addBodyText($this->l10n->t('Your account is available again, none of your data was removed. You may now login again.'));
		$template->addBodyButton($this->l10n->t('Login'), $this->urlGenerator->getBaseUrl());
		return $template;
	}
}
