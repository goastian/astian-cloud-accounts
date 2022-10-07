<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Settings;

use OC\Accounts\AccountManager;
use OCA\FederatedFileSharing\FederatedShareProvider;
use OCP\Accounts\IAccount;
use OCP\Accounts\IAccountManager;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IGroupManager;
use OCP\IUserSession;
use OCP\Settings\ISettings;
use OCA\EcloudAccounts\Service\AliasesService;
use OCP\AppFramework\Http\Response;


class BecomeBetaSetting implements ISettings
{

	/** @var IConfig */
	private $config;
	/** @var IUserManager */
	private $userManager;
	/** @var AccountManager */
	private $accountManager;
	/** @var IUserSession */
	private $userSession;
	/** @var IFactory */
	private $l10nFactory;
	/** @var IL10N */
	private $l;
	protected $groupManager;


	public function __construct(
		IConfig $config,
		IUserManager $userManager,
		IUserSession $userSession,
		AccountManager $accountManager,
		IGroupManager $groupManager,
		AliasesService $aliasesService,
		IL10N $l
	) {
		$this->config = $config;
		$this->userManager = $userManager;
		$this->userSession = $userSession;
		$this->accountManager = $accountManager;
		$this->aliasesService = $aliasesService;
		$this->l = $l;
		$this->groupManager = $groupManager;
	}

	public function getForm(): TemplateResponse
	{
		$response = new Response();
		
		$uid =   $this->userSession->getUser()->getUID();
		$user = $this->userManager->get($uid);
		$gid = 'beta';
		$isBeta = 0;
		
		$group = null;
		$groupExists = $this->groupManager->groupExists($gid);
		
		if (!$groupExists) {
			$group = $this->groupManager->createGroup($gid);
		} else {
			$group = $this->groupManager->get($gid);
		}
		
		$inGroup = $this->groupManager->isInGroup($uid, $gid);
			
		if ($inGroup) {
			$isBeta = 1;
		}
		
		$parameters = ['isBeta' => $isBeta];
		return new TemplateResponse('beta-user', 'beta_user_setting', $parameters, '');
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
