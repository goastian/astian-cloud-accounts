<?php

namespace OCA\EcloudAccounts\Db;

use \Sabre\VObject\Reader;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use OCA\DAV\CardDAV\CardDavBackend;
use OCA\EcloudAccounts\Exception\DbConnectionParamsException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use OCP\ILogger;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Connection;
use OCA\EcloudAccounts\Exception\DbConnectionParamsException;
use Sabre\VObject\UUIDUtil;
use OCP\IUserManager;
use OCP\IUser;
use OCA\DAV\CardDAV\CardDavBackend;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCA\SnappyMail\Util\SnappyMailHelper;
use RainLoop\Providers\AddressBook\PdoAddressBook;

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


	public function getEmails(int $limit = 0, int $offset = 0, array $emails = []) : array {
		$qb = $this->conn->createQueryBuilder();
		$qb->select('rl_email')
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

		$emails = [];
		while ($row = $result->fetch()) {
			$emails[] = (string) $row['rl_email'];
		}
		return $emails;
	}

	private function getUserContacts(string $email) : array {
		SnappyMailHelper::loadApp();
		$pdoAddressBook = new PdoAddressBook();
		$pdoAddressBook->SetEmail($email);
		return $pdoAddressBook->GetContacts(0, PHP_INT_MAX);
	}

	private function createCloudAddressBook(array $contacts, string $email) {
		$users = $this->userManager->getByEmail($email);
		if (empty($users)) {
			return;
		}
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
				if (isset($contact->vCard->REV)) {
					try {
						$timeString = $contact->vCard->REV[0]->getJsonValue()[0];
						$timestamp = strtotime($timeString);
					} catch (Throwable $e) {
						// Do nothing
					}
				}
				if (!isset($timestamp)) {
					$timestamp = time();
				}
				$contact->vCard->REV = \gmdate('Ymd\\THis\\Z', $timestamp);
				$this->cardDavBackend->createCard(
					$addressBookId,
					UUIDUtil::getUUID() . '.vcf',
					$contact->vCard->serialize(),
					true
				);
			} catch (Throwable $e) {
				$this->logger->error('Error inserting contact for user: ' . $username . ' ' . $e->getMessage());
			}
		}
	}

	public function migrateContacts(array $emails, $commandOutput = null) {
		$userCount = 0;
		foreach ($emails as $email) {
			$userCount += 1;
			if ($commandOutput) {
				$commandOutput->writeln('Migrating user ' . $userCount . ' with email: '. $email);
			}
			$contacts = $this->getUserContacts($email);
			$numberOfContacts = count($contacts);
			if ($commandOutput) {
				$commandOutput->writeln('Number of contacts for ' . $email . ':' . $numberOfContacts);
			}
			if ($numberOfContacts > 0) {
				$this->createCloudAddressBook($contacts, $email);
			}
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
