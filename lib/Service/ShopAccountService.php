<?php


namespace OCA\EcloudAccounts\Service;

use Curl;
use Exception;
use OCP\IConfig;
use OCA\EcloudAccounts\AppInfo\Application;
use OCA\EcloudAccounts\Service\CurlService;

class ShopAccountService {

    private $config;
    private $appName;
    private $curl;

    public function __construct($appName, IConfig $config, CurlService $curlService)
    {

        $shopUsername = getenv("WP_SHOP_USERNAME");
        $shopPassword = getenv("WP_SHOP_PASS");
        $shopUrl = getenv("WP_SHOP_URL");

        $this->appName = $appName;
        $this->shopUserUrl = $shopUrl . "/wp-json/wp/v2/users";
        $this->shopOrdersUrl = $shopUrl . "/wp-json/wc/v3/orders";
        $this->shopCredentials = base64_encode($shopUsername . ":" . $shopPassword);
        $this->shopReassignUserId = getenv('WP_REASSIGN_USER_ID');
        $this->config = $config;
        $this->curl = $curlService;
    }

    public function setShopDeletePreference($userId, bool $delete) {
        $this->config->setUserValue($userId, $this->appName, 'delete_shop_account', intval($delete));
    }

    public function setShopEmailPostDeletePreference($userId, string $shopEmailPostDelete) {
        $this->config->setUserValue($userId, $this->appName, 'shop_email_post_delete', $shopEmailPostDelete);
    }

    public function getShopDeletePreference($userId) {
        return boolval($this->config->getUserValue($userId, $this->appName, 'delete_shop_account', false));
    }

    public function getShopEmailPostDeletePreference($userId) {
        $recoveryEmail = $this->config->getUserValue($userId, 'email-recovery', 'recovery-email');

        return $this->config->getUserValue($userId, $this->appName, 'shop_email_post_delete', $recoveryEmail);  
    }

    public function getOrders(int $userId): ?array {
        try {
            return $this->callShopAPI($this->shopOrdersUrl, 'GET', ['customer' => $userId]);
        }
        catch(Exception $e) {
            $this->logger->error('There was an issue querying shop for orders for user ' . strval($userId));
            $this->logger->logException($e, ['app' => Application::APP_ID]);
        }
        return null;
    }

    public function getUsers(string $searchTerm): ?array
    {
        try {
            return $this->callShopAPI($this->shopUserUrl, 'GET', ['search' => $searchTerm]);
        }
        catch(Exception $e) {
            $this->logger->error('There was an issue querying shop for users');
            $this->logger->logException($e, ['app' => Application::APP_ID]);
        }
        return null;
    }

    public function getUser(string $email) : ?array {
        $users = $this->getUsers($email);
        if(empty($users)) {
            return null;
        }
        if(count($users) > 1) {
            $this->logger->error('More than one user in WP results when deleting user with email ' . $email);
            return null;
        }
        return $users[0];

    }

    public function deleteUser(int $userId) : void {    
        $params = [
            'force' => true,
            'reassign' => $this->shopReassignUserId
        ];
        $deleteUrl = $this->shopUserUrl . '/' . strval($userId);

        try {
            $answer = $this->callShopAPI($deleteUrl, 'DELETE', $params);

            if(!$answer['deleted']) {
                throw new Exception('Unknown error while deleting!');
            }
        }
        catch(Exception $e) {
            $this->logger->error('Error deleting user at WP with ID ' . $userId);
            $this->logger->logException($e, ['app' => Application::APP_ID]);
        }
        
    } 

    public function updateUserEmail(int $userId, string $email) : void {
        $updateUrl = $this->shopUserUrl . '/' . strval($userId);

        $params = [
            'email' => $email
        ];

        try {
            $answer = $this->callShopAPI($updateUrl, 'POST', $params);
                
            if($answer['email'] !== $email) {
                throw new Exception('Unknown error while updating!');
            }
        }
        catch(Exception $e) {
            $this->logger->error('Error updating user email at WP with ID ' . $userId . ' and new email ' . $email);
            $this->logger->logException($e, ['app' => Application::APP_ID]);
        }
    }

    private function callShopAPI(string $url, string $method, array $data = []) {
            
        $headers = [
            "cache-control: no-cache",
            "content-type: application/json",
            "Authorization: Basic " . $this->shopCredentials
        ];

        if($method === 'GET') {
            $answer = $this->curl->get($url, $data, $headers);
        }

        if($method === 'DELETE') {
            $answer = $this->curl->delete($url, $data, $headers);
        }

        if ($method === 'POST') {
            $answer = $this->curl->post($url, $data, $headers);
        }

        $answer = json_decode($answer, true);
        if(isset($answer['code']) && isset($answer['message'])) {
            throw new Exception($answer['message']);
        }

        return $answer;
    }

    public function isUserOIDC(array $user) {
        return !empty($user['openid-connect-generic-last-user-claim']);
    }
}