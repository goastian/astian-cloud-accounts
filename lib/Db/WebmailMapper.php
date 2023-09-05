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
use OCP\DB\QueryBuilder\IQueryBuilder;
use Throwable;

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


	public function getUsers(int $limit = 0, int $offset = 0, array $emails = []) : array {
		$qb = $this->conn->createQueryBuilder();
		$qb->select('rl_email, id_user')
			->from(self::USERS_TABLE, 'u')
			->setFirstResult($offset);
		if ($limit > 0) {
			$qb->setMaxResults($limit);
		}
		if (!empty($emails)) {
			$qb->where('rl_email in (:emails)');
			$qb->setParameter('emails', $emails, IQueryBuilder::PARAM_STR_ARRAY);
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

		$qb->select('p.prop_value')
			->from('rainloop_ab_contacts', 'c')
			->where('c.id_user = :uid')
			->andWhere('p.prop_value IS NOT NULL')
			->setParameter('uid', $uid)
			->leftJoin('c', 'rainloop_ab_properties', 'p', 'p.id_contact = c.id_contact AND p.prop_type = 251');

		$result = $qb->execute();
		$contacts = [];
		while ($row = $result->fetch()) {
			$contacts[] = Reader::readJson($row['prop_value']);
		}
		return $contacts;
	}

	private function createCloudAddressBook(array $contacts, string $email) {
		$users = $this->userManager->getByEmail($email);
		$user = $users[0];

		if (!$user instanceof IUser) {
			return;
		}

		$username = $user->getUID();

		$principalUri = 'principals/users/'. $username;
		$addressbookUri = 'webmail'; // some unique identifier
		try {
			$alreadyImported = $this->cardDavBackend->getAddressBooksByUri($principalUri, $addressbookUri);

			if ($alreadyImported) {
				return;
			}

			$addressBookId = $this->cardDavBackend->createAddressBook(
				$principalUri,
				$addressbookUri,
				[
					'{DAV:}displayname' => 'Webmail',
					'{urn:ietf:params:xml:ns:carddav}addressbook-description' => 'Contacts imported from snappymail'
				]
			);
		} catch (Throwable $e) {
			$this->logger->error('Error creating address book for user: ' . $username . ' ' . $e->getMessage());
		}
		foreach ($contacts as $contact) {
			try {
				$contact->PRODID = '-//IDN murena.io//Migrated contact//EN';

				$this->cardDavBackend->createCard(
					$addressBookId,
					UUIDUtil::getUUID() . '.vcf',
					$contact->serialize(),
					true
				);
			} catch (Throwable $e) {
				$this->logger->error('Error inserting contact for user: ' . $username . ' contact: ' . $contact->serialize() . ' ' . $e->getMessage());
			}
		}
	}

	public function migrateContacts(array $users, $commandOutput = null) {
		$userCount = 0;
		foreach ($users as $user) {
			$userCount += 1;
			if ($commandOutput) {
				$commandOutput->writeln('Migrating user ' . $userCount . ' with email: '.  $user['email']);
			}
			$contacts = $this->getUserContacts($user['id']);
			$commandOutput->writeln('Number of contacts for ' . $user['email'] . ':' . count($contacts));
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
