<?php

namespace OCA\EcloudAccounts\Db;

use OCP\IConfig;
use OCP\ILogger;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Connection;
use OCA\EcloudAccounts\Exception\DbConnectionParamsException;
use Sabre\VObject\UUIDUtil;
use \Sabre\VObject\Reader;
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


	public function getUsers(int $limit, int $offset = 0, array $emails = []) : array {
		$qb = $this->conn->createQueryBuilder();
		$qb->select('rl_email, id_user')
			->from(self::USERS_TABLE)
			->setFirstResult($offset)
			// We can set max to $limit without default as NULL => all results
			->setMaxResults($limit);

		if (!empty($emails)) {
			$qb->where('rl_email IN :emails')
				->setParameter('emails', $emails);
		}
		$result = $qb->execute();

		$users = [];
		while ($row = $result->fetch()) {
			$user = [
				'email' => $row['rl_email'],
				'id' => $row['id_user']
			];
			$users[] = $user;
		}
		return $users;
	}

	private function getUserContacts(string $uid) : array {
		$qb = $this->conn->createQueryBuilder();

		$qb->select('id_contact,jcard')
			->from('rainloop_ab_contacts')
			->where('id_user = :uid')
			->setParameter('uid', $uid);
		$result = $qb->execute();
		$contacts = [];
		while ($row = $result->fetch()) {
			$contacts[] = Reader::readJson($row['jcard']);
		}
		return $contacts;
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

	public function migrateContacts(array $users) {
		foreach ($users as $user) {
			$contacts = $this->getUserContacts($user['id']);
			if (!count($contacts)) {
				return;
			}
			$this->createCloudAddressBook($contacts, $user['email']);
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
