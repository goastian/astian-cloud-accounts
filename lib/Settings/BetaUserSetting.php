<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\IGroupManager;
use OCP\IUserSession;
use OCP\Settings\ISettings;


class BetaUserSetting implements ISettings
{

	/** @var IUserSession */
	private $userSession;

	/** @var IGroupManager */
	protected $groupManager;

	const GROUP_NAME = "beta";


	public function __construct(
		IUserSession $userSession,
		IGroupManager $groupManager
	) {
		$this->userSession = $userSession;
		$this->groupManager = $groupManager;
	}

	public function getForm(): TemplateResponse
	{		
		$uid =  $this->userSession->getUser()->getUID();
		$isBeta = 0;

		$groupExists = $this->groupManager->groupExists(self::GROUP_NAME);
		if($groupExists){
			$isBeta = $this->groupManager->isInGroup($uid, self::GROUP_NAME);
		}
		
		$parameters = ['isBeta' => $isBeta, 'groupExists' => $groupExists];
		return new TemplateResponse('ecloud-accounts', 'beta_user_setting', $parameters, '');
	}

	public function getSection(): ?string
	{
		return 'beta-user';
	}

	public function getPriority(): int
	{
		return 0;
	}
}
