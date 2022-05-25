<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Listeners;

use Exception;
use Curl;
use OCA\EcloudAccounts\AppInfo\Application;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\ILogger;
use OCP\IConfig;
use OCP\User\Events\BeforeUserDeletedEvent;
use OCA\EcloudAccounts\Service\LDAPConnectionService;

require_once 'curl.class.php';

class BeforeUserDeletedListener implements IEventListener
{
    private $logger;
    private $config;
    private $LDAPConnectionService;

    public function __construct(ILogger $logger, IConfig $config, LDAPConnectionService $LDAPConnectionService)
    {
        $this->logger = $logger;
        $this->config = $config;
        $this->LDAPConnectionService = $LDAPConnectionService;
    }


    public function handle(Event $event): void
    {
        if (!($event instanceof BeforeUserDeletedEvent)) {
            return;
        }

        $user = $event->getUser();
        $email = $user->getEMailAddress();
        $uid = $user->getUID();
        $isUserOnLDAP = $this->LDAPConnectionService->isUserOnLDAPBackend($user);

        $this->logger->info("PostDelete user {user}", array('user' => $uid));
        $this->ecloudDelete(
            $uid,
            $this->config->getSystemValue('e_welcome_domain'),
            $this->config->getSystemValue('e_welcome_secret'),
            $email,
            $isUserOnLDAP
        );

        try {
            if ($this->LDAPConnectionService->isLDAPEnabled() && $isUserOnLDAP) {
                $conn = $this->LDAPConnectionService->getLDAPConnection();
                $this->deleteAliasEntries($conn, $email);
                $this->LDAPConnectionService->closeLDAPConnection($conn);
            }
        } catch (Exception $e) {
            $this->logger->error('Error deleting aliases for user '. $uid . ' :' . $e->getMessage());
        }
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
    public function ecloudDelete(string $userID, string $welcomeDomain, string $welcomeSecret, string $email, bool $isUserOnLDAP = false)
    {
        $endpoint = '/postDelete.php';
        if ($isUserOnLDAP) {
            $endpoint = '/postDeleteLDAP.php';
        }
        $postDeleteUrl = "https://" . $welcomeDomain . $endpoint;
        $curl = new Curl();

        /**
         * send action to docker_welcome
         * Handling the non NC part of deletion process
         */
        try {
            $params = [
                'sec' => $welcomeSecret,
                'uid' => $userID,
                'email' => $email
            ];
            
            $headers = array(
                'Content-Type: application/json'
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
        foreach ($aliasDns as $aliasDn) {
            $deleted = ldap_delete($conn, $aliasDn);
            if (!$deleted) {
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
        $aliasEntries = array_filter($aliasEntries, fn ($entry) => is_array($entry));
        $aliasEntries = array_map(
            fn ($entry) => $entry['dn'],
            $aliasEntries
        );

        return $aliasEntries;
    }
}
