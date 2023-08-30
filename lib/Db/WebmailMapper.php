<?php

namespace OCA\EcloudAccounts\Db;

use OCP\IConfig;
use OCP\ILogger;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Connection;
use OCA\EcloudAccounts\Exception\DbConnectionParamsException;
use \RainLoop\Providers\AddressBook\PdoAddressBook;
use Sabre\VObject\UUIDUtil;
use OCP\IUserManager;
use OCP\IUser;
use OCA\DAV\CardDAV\CardDavBackend;

class WebmailMapper {
	private IConfig $config;
	private ILogger $logger;
	private Connection $conn;
	private CardDavBackend $cardDavBackend;
	private IUserManager $userManager;


	private const WEBMAIL_DB_CONFIG_KEY = 'webmail_db';
	private const USERS_TABLE = 'rainloop_users';

	public function __construct(IConfig $config, ILogger $logger, CardDavBackend $cardDavBackend, IUserManager $userManager) {
		$this->config = $config;
		$this->logger = $logger;
		$this->cardDavBackend = $cardDavBackend;
		$this->userManager = $userManager;
		if (!empty($this->config->getSystemValue(self::WEBMAIL_DB_CONFIG_KEY))) {
			$this->initConnection();
		}
	}

	private function isDbConfigValid($config) : bool {
		if (!$config || !is_array($config)) {
			return false;
		}
		if (!isset($config['db_port'])) {
			$config['db_port'] = 3306;
		}

		return isset($config['db_name'])
			&& isset($config['db_user'])
			&& isset($config['db_password'])
			&& isset($config['db_host'])
			&& isset($config['db_port']) ;
	}

	public function getUserEmails(int $limit, int $offset = 0) : array {
		$qb = $this->conn->createQueryBuilder();
		$qb->select('rl_email')
			->from(self::USERS_TABLE)
			->setFirstResult($offset)
			// We can set max to $limit without default as NULL => all results
			->setMaxResults($limit);
		$result = $qb->execute();

		$emails = [];
		while ($row = $result->fetch()) {
			$emails[] = $row['rl_email'];
		}
		return $emails;
	}

	private function getUserContactIds(string $uid) : array {
		$qb = $this->conn->createQueryBuilder();

		$qb->select('id_contact')
			->from('rainloop_ab_contacts')
			->where('id_user = :uid')
			->setParameter('uid', $uid);
		$result = $qb->execute();
		$contactIds = [];
		while ($row = $result->fetch()) {
			$contactIds[] = $row['id_contact'];
		}
		return $contactIds;
	}

	private function createCloudAddressBook(array $contacts, string $email) {
		$user = $this->userManager->getUserByEmail($email);

		if (!$user instanceof IUser) {
			return;
		}

		$username = $user->getUID();

		$principalUri = 'principals/users/'. $username;
		$addressbookUri = 'webmail'; // some unique identifier
		$alreadyImported = $this->cardDavBackend->getAddressBooksByUri($principalUri, $addressbookUri);

		if ($alreadyImported) {
			return;
		}

		$addressBookId = $this->cardDavBackend->createAddressBook($principalUri, $addressbookUri, ['{DAV:}displayname' => 'Webmail',
			'{urn:ietf:params:xml:ns:carddav}addressbook-description' => 'Contacts imported from snappymail']);

		foreach ($contacts as $contact) {
			$contact->PRODID = '-//IDN murena.io//Migrated contact//EN';

			$this->cardDavBackend->createCard(
				$addressBookId,
				UUIDUtil::getUUID() . '.vcf',
				$contact,
				true
			);
		}
	}

	public function migrateContacts(array $emails) {
		$rainloopContactsProvider = new PdoAddressBook();
		foreach ($emails as $email) {
			$uid = $rainloopContactsProvider->setEmail($email);
			$contactIds = $this->getUserContactIds($uid);

			if (!count($contactIds)) {
				return;
			}
			$contacts = [];
			foreach ($contactIds as $id) {
				$contact = $rainloopContactsProvider->GetContactByID($id, false, $uid);

				$contacts[] = $contact->vCard->serialize();
			}

			$this->createCloudAddressBook($contacts, $email);
		}
	}


	private function initConnection() : void {
		try {
			$params = $this->getConnectionParams();
			$this->conn = DriverManager::getConnection($params);
		} catch (Throwable $e) {
			$this->logger->error('Error connecting to Webmail database: ' . $e->getMessage());
		}
	}

	private function getConnectionParams() : array {
		$config = $this->config->getSystemValue(self::WEBMAIL_DB_CONFIG_KEY);
		
		if (!$this->isDbConfigValid($config)) {
			throw new DbConnectionParamsException('Invalid Webmail database configuration!');
		}

		$params = [
			'dbname' => $config['db_name'],
			'user' => $config['db_user'],
			'password' => $config['db_password'],
			'host' => $config['db_host'],
			'port' => $config['db_port'],
			'driver' => 'pdo_mysql'
		];
		return $params;
	}
}
