<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Events;

use Curl;
use OCA\EcloudAccounts\AppInfo\Application;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IConfig;
use OCP\ILogger;
use OCP\User\Events\UserDeletedEvent;

require_once 'curl.class.php';

class UserDeletedListener implements IEventListener
{

    private $logger;
    private $config;

    public function __construct(ILogger $logger, IConfig $config)
    {
        $this->logger = $logger;
        $this->config = $config;
    }

    public function handle(Event $event): void
    {
        if (!($event instanceof UserDeletedEvent)) {
            return;
        }

        $uid = $event->getUser()->getUID();
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
            $this->logger->logException($e, ['app' => Application::APP_NAME]);
        }

        return null;
    }
}
