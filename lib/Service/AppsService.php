<?php

namespace OCA\EcloudAccounts\Service;

use OCP\IConfig;
use OCP\INavigationManager;
use OCP\App\IAppManager;
use OCP\IGroupManager;
use OCP\IUserSession;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Files\IRootFolder;

class AppsService {
	private string $appName = 'murena_launcher';
	private ?string $userId;
	private IConfig $config;
	private INavigationManager $navigationManager;
	private IAppManager $appManager;
	private IFactory $l10nFac;
	private IUserSession $userSession;
	private IGroupManager $groupManager;
	private IURLGenerator $urlGenerator;
	private IRootFolder $rootFolder;

	private const DEFAULT_ORDER = array("/apps/snappymail/", "/apps/calendar/", "/apps/files/" , "/apps/photos/", "/apps/memories/", "/apps/contacts/", "/apps/onlyoffice/ajax/new?id=onlyoffice_docx", "/apps/onlyoffice/ajax/new?id=onlyoffice_xlsx", "/apps/onlyoffice/ajax/new?id=onlyoffice_pptx", "/apps/notes/", "/apps/tasks/", "https://spot.murena.io" , "https://murena.com" );
	public function __construct(
		IConfig $config,
		INavigationManager $navigationManager,
		IAppManager $appManager,
		IFactory $l10nFac,
		IUserSession $userSession,
		IGroupManager $groupManager,
		IURLGenerator $urlGenerator,
		IRootFolder $rootFolder,
		$userId
	) {
		$this->userId = $userId;
		$this->config = $config;
		$this->navigationManager = $navigationManager;
		$this->appManager = $appManager;
		$this->l10nFac = $l10nFac;
		$this->userSession = $userSession;
		$this->groupManager = $groupManager;
		$this->urlGenerator = $urlGenerator;
		$this->rootFolder = $rootFolder;
	}

	public function getOnlyOfficeEntries() {
		$l = $this->l10nFac->get("onlyoffice");
		$onlyOfficeEntries = array(
			array(
				"id" => "onlyoffice_docx",
				"icon" => $this->urlGenerator->imagePath('onlyoffice', 'docx/app-color.svg'),
				"name" => $l->t("Document"),
				"default_filename" => 'untitled.docx'
			),
			array(
				"id" => "onlyoffice_xlsx",
				"icon" => $this->urlGenerator->imagePath('onlyoffice', 'xlsx/app-color.svg'),
				"name" => $l->t("Spreadsheet"),
				"default_filename" => 'untitled.xlsx'
			),
			array(
				"id" => "onlyoffice_pptx",
				"icon" => $this->urlGenerator->imagePath('onlyoffice', 'pptx/app-color.svg'),
				"name" => $l->t("Presentation"),
				"default_filename" => 'untitled.pptx'
			),
		);
		$onlyOfficeEntries = array_map(function ($entry) {
			$entry["type"] = "link";
			$entry["active"] = false;
			$baseDirectory = $this->getDocumentsFolder();
			$entry["href"] = "/apps/onlyoffice/new?id=" . $entry["id"] . "&name=" . $entry["default_filename"] . "&dir=" . $baseDirectory;
			return $entry;
		}, $onlyOfficeEntries);

		return $onlyOfficeEntries;
	}

	public function getAppOrder() {
		$order_raw = $this->config->getUserValue($this->userId, $this->appName, 'order');
		// If order raw empty try to get from 'apporder' app config
		$order_raw = !$order_raw ? $this->config->getUserValue($this->userId, 'apporder', 'order') : $order_raw;
		// If order raw is still empty, return empty array
		if (!$order_raw) {
			return self::DEFAULT_ORDER;
		}
		return json_decode($order_raw);
	}

	public function getAppEntries() {
		$entries = array_values($this->navigationManager->getAll());
		$order = $this->getAppOrder();
		$entriesByHref = array();
		if ($this->appManager->isEnabledForUser("onlyoffice")) {
			$office_entries = $this->getOnlyOfficeEntries();
			$entries = array_merge($entries, $office_entries);
		}
		$betaGroupName = $this->config->getSystemValue("beta_group_name");
		$isBeta = $this->isBetaUser();
		foreach ($entries as &$entry) {
			try {
				$entry["icon"] = $this->urlGenerator->imagePath($entry["id"], 'app-color.svg');
			} catch (\Throwable $th) {
				//exception - continue execution
			}
			if (strpos($entry["id"], "external_index") !== 0) {
				$entry["target"] = "";
			} else {
				$entry["target"] = "_blank";
			}
			$entry["class"] = "";
			if (strpos($entry["icon"], "/custom_apps/") === 0) {
				$entry["class"] = "icon-invert";
			}
			$entry["iconOffsetY"] = 0;
			$entry["is_beta"] = 0;
			$appEnabledGroups = $this->config->getAppValue($entry['id'], 'enabled', 'no');
			if ($isBeta && str_contains($appEnabledGroups, $betaGroupName)) {
				$entry["is_beta"] = 1;
			}
			$entriesByHref[$entry["href"]] = $entry;
		}
		/*
		 Sort apps according to order
		 Since "entriesByHref" is indexed by "href", simply reverse the order array and prepend in "entriesByHref"
		 Prepend is done by using each "href" in the reversed order array and doing a union of the "entriesByHref"
		 array with the current element
		*/
		if ($order) {
			$order = array_reverse($order);
			foreach ($order as $href) {
				if (!empty($entriesByHref[$href])) {
					$entriesByHref = array($href => $entriesByHref[$href]) + $entriesByHref;
				}
			}
		}
		unset($entriesByHref['/apps/dashboard/']);
		unset($entriesByHref['/apps/murena-dashboard/']);
		unset($entriesByHref['']);

		return array('apps' => array_values($entriesByHref));
	}

	public function updateOrder(string $order) {
		$this->config->setUserValue($this->userId, $this->appName, 'order', $order);
	}

	private function isBetaUser() {
		$uid = $this->userSession->getUser()->getUID();
		$gid = $this->config->getSystemValue("beta_group_name");
		return $this->groupManager->isInGroup($uid, $gid);
	}
	public function getDocumentsFolder() {
		$folderName = 'Documents';
		$userId = $this->userSession->getUser()->getUID();
		$userPath = $this->rootFolder->getUserFolder($userId)->getPath();
		$filePath = $userPath . '/' . $folderName;

		$folder = null;
		if ($this->rootFolder->nodeExists($filePath)) {
			$folder = $this->rootFolder->get($filePath);
		} else {
			$folder = $this->rootFolder->get($userPath);
			$filePath = $userPath;
		}
		return $filePath === $userPath ? '/' : $folder->getName();
	}
}
