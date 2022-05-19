<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Listeners;

use Curl;
use Exception;
use OCA\EcloudAccounts\AppInfo\Application;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IUserManager;
use OCP\User\Events\UserDeletedEvent;

require_once 'curl.class.php';

class UserDeletedListener implements IEventListener
{
    private $logger;
    private $config;

    public function __construct(ILogger $logger, IConfig $config, IUserManager $userManager)
    {
        $this->logger = $logger;
        $this->config = $config;
    }


    public function handle(Event $event): void
    {
        if (!($event instanceof UserDeletedEvent)) {
            return;
        }

        $user = $event->getUser();
        $email = $user->getEMailAddress();
        $uid = $user->getUID();

        try {
            $conn = $this->getLDAPConnection($uid);
            $this->deleteAliasEntries($conn, $email);
        }
        catch (Exception $e) {
            $this->logger->error('Error deleting aliases for user '. $uid . ' :' . $e->getMessage());
        }

        $this->logger->info("PostDelete user {user}", array('user' => $uid));
        $this->ecloudDelete(
            $uid,
            $this->config->getSystemValue('e_welcome_domain'),
            $this->config->getSystemValue('e_welcome_secret')
        );
    }

    /**
     * Once NC deleted the account,
     * perform specific ecloud selfhosting actions
     * post delete action is delegated to the welcome container
     *
     * @param $userID string
     * @param $welcomeDomain string main NC domain (welcome container)
     * @param $welcomeSecret string generated at ecloud selfhosting install and added as a custom var in NC's config
     * @return mixed response of the external endpoint
     */
    public function ecloudDelete(string $userID, string $welcomeDomain, string $welcomeSecret)
    {
        $postDeleteUrl = "https://" . $welcomeDomain . "/postDelete.php";
        $curl = new Curl();

        /**
         * send action to docker_welcome
         * Handling the non NC part of deletion process
         */
        try {
            $headers = array(
                'Content-Type: application/json'
            );
            $params = array(
                'sec' => $welcomeSecret,
                'uid' => $userID
            );

            $answer = $curl->post($postDeleteUrl, $params, $headers);

            return json_decode($answer, true);
        } catch (\Exception $e) {
            $this->logger->error('There has been an issue while contacting the external deletion script');
            $this->logger->logException($e, ['app' => Application::APP_ID]);
        }

        return null;
    }

    private function deleteAliasEntries($conn, string $email)
    {
        $aliasBaseDn = getenv('LDAP_ALIASES_BASE_DN');
        $aliasDns = $this->getAliasEntries($conn, $aliasBaseDn, $email);
        foreach($aliasDns as $aliasDn) {
            $deleted = ldap_delete($conn, $aliasDn);
            if(!$deleted) {
                $this->logger->error('Deleting alias ' . $aliasDn . ' for email ' .  $email . ' failed');
            }
        }
    }

    private function getAliasEntries($conn, string $aliasBaseDn, string $email) : array
    {
        $filter = "(mailAddress=$email)";
        $aliasEntries = ldap_search($conn, $aliasBaseDn, $filter);
        if (!$aliasEntries) {
            return [];
        }

        $aliasEntries =  ldap_get_entries($conn, $aliasEntries);
        $aliasEntries = array_filter($aliasEntries, fn($entry) => is_array($entry));
        $aliasEntries = array_map(
            fn ($entry) => $entry['dn'],
            $aliasEntries
        );

        return $aliasEntries;
    }

    private function getLDAPConnection(string $uid)
    {
        $backends = $this->userManager->getBackends();
        $conn = null;
        foreach ($backends as $backend) {
            if ($backend->getBackendName() === 'LDAP') {
                $access = $backend->getLDAPAccess($uid);
                $connection = $access->getConnection();
                $configuration = $connection->getConfiguration();

                if (!$configuration->ldapConfigurationActive) {
                    continue;
                }

                $adminDn = $configuration->ldapAgentName;
                $adminPassword = $configuration->ldapAgentPassword;
                $host = $configuration->ldapHost;
                $port = $configuration->ldapPort;

                $conn = ldap_connect($host, $port);
                ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
                ldap_bind($conn, $adminDn, $adminPassword);

                if (!$conn) {
                    continue;
                }

                return $conn;
            }
        }

        if (!$conn) {
            throw new Exception('Could not connect to LDAP server to delete aliases for user ' . $uid);
        }
    }
}
