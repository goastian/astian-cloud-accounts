<?php


namespace OCA\EcloudAccounts\Service;

use OCP\IConfig;

require_once '../curl.class.php';

class ShopAccountService {

    private $config;
    private $appName;

    public function __construct($appName, IConfig $config)
    {

        $shopUsername = getenv("WP_SHOP_USERNAME");
        $shopPassword = getenv("WP_SHOP_PASS");
        $shopUrl = getenv("WP_SHOP_URL");

        $this->appName = $appName;
        $this->shopUserUrl = $shopUrl . "/wp-json/wp/v2/users";
        $this->shopCredentials = base64_encode($shopUsername . ":" . $shopPassword);
        $this->shopReassignUserId = getenv('WP_REASSIGN_USER_ID');
        $this->config = $config;
    }

    public function setShopDeletePreference($userId, bool $delete) {
        $this->config->setUserValue($userId, $this->appName, 'delete_shop_account', $delete);
    }

    public function setShopEmailPostDelete($userId, string $shopEmailPostDelete) {
        $this->config->setUserValue($userId, $this->appName, 'shop_email_post_delete', $shopEmailPostDelete);
    }

    public function getShopDeletePreference($userId) {
        return $this->config->getUserValue($userId, $this->appName, 'delete_shop_account', true);
    }

    public function getShopEmailPreference($userId) {
        $recoveryEmail = $this->config->getUserValue($userId, 'email-recovery', 'recovery-email');

        return $this->config->getUserValue($userId, $this->appName, 'shop_email_post_delete', $recoveryEmail);  
    }

    private function getUsersFromShop(string $searchTerm): ?array
    {
        $curl = new Curl();
        $headers = [
            "cache-control: no-cache",
            "content-type: application/json",
            "Authorization: Basic " . $this->shopCredentials
        ];
        
        try {
            $answer = $curl->get($this->shopUserUrl, ['search' => $searchTerm], $headers);
            return json_decode($answer, true);
        }
        catch(Exception $e) {
            $this->logger->error('There was an issue querying shop for users');
            $this->logger->logException($e, ['app' => Application::APP_ID]);
        }
    }

    private function getUserFromShop(string $email) {
        $users = $this->getUsersFromShop($email);
        if(empty($users)) {
            return;
        }
        if(count($users) > 1) {
            $this->logger->error('More than one user in WP results when deleting user with email ' . $email);
            return;
        }
        return $users[0];

    }

    private function deleteUserFromShop(string $email) {
        $user = $this->getUserFromShop($email);
    
        if($user && $this->isUserOIDC($user)) {
            $curl = new Curl();
            
            $headers = [
                "cache-control: no-cache",
                "content-type: application/json",
                "Authorization: Basic " . $this->shopCredentials
            ];
            $params = [
                'force' => true,
                'reassign' => $this->shopReassignUserId
            ];
            $deleteUrl = $this->shopUserUrl . '/' . $user['id'];

            try {
                $answer = $curl->delete($deleteUrl, $params, $headers);
                $answer = json_decode($answer, true);

                if(!$answer['deleted']) {
                    throw new Exception("User not deleted at WP ". $user['id'] );
                }
            }
            catch(Exception $e) {
                $this->logger->error('Error deleting user at WP with ID ' . $user['id']);
                $this->logger->logException($e, ['app' => Application::APP_ID]);
            }
        }
    } 

    private function isUserOIDC(array $user) {
        return !empty($user['openid-connect-generic-last-user-claim']);
    }
}