<?php
namespace OCA\EcloudDropAccount\Hooks;
use OCA\EcloudDropAccount\AppInfo\Application;
use OCP\IUserManager;
use OCP\ILogger;
use OCP\IUser;
use OCP\IConfig;


require_once 'curl.class.php';
use Curl;

class UserHooks {

    private $userManager;
    private $logger;

    public function __construct(IUserManager $userManager, ILogger $logger,  IConfig $config){
        $this->userManager = $userManager;
        $this->logger = $logger;
        $this->config = $config;
    }

    public function register() {

    	
    	
    	/**
    	 * when auto delete action is done by user, fire postDelete hook 
    	 * to send postDelete actions to be done for /e/ specific setup
    	 * 
    	 * username in ecloud-selfhost setup IS in the form user@$DOMAIN
    	 * 
    	 */
    	  
        $callback = function(IUser $user) {
            
			$externalDelete = $this->ecloudDelete($user->getUID());

        };
        $this->userManager->listen('\OC\User', 'postDelete', $callback);
    }		

    /**
     * Once NC deleted account datas
     * do specific ecloud selfhosting actions 
     * post delete action is delegated welcome container
     * 
     * CHECK : compare user account and domain, to be sure it's identical
     * actually only comparing $trusted_domains[0] >> main domain
     * 
     * TODO : handle account deletion with multiple trusted domains!!
     * 
     */
    public function ecloudDelete($userID) {

			// build welcome domain url from main NC domain
			$welcomeDomain = "https://".$this->config->getSystemValue('e_welcome_domain');
			$postDeleteScript = "/postDelete.php";

	    	/**
	    	 * welcome secret is generated at ecloud selfhosting install
	    	 * and added as a custom var in NC's config
	    	 */
	    	$welcomeSecret = $this->config->getSystemValue('e_welcome_secret');

	    	$curl = new Curl();

	    	/**
	    	 * send action to  docker_welcome
	    	 * Handling the non NC part of deletion process 
	    	 */
	    	try {

				$headers = array(
			    'Content-Type: application/json'
			)	;
	    		$params = array(
	    			'sec' => $welcomeSecret,
	    			'uid' => $userID
	    		);

	    		$url = $welcomeDomain.$postDeleteScript;

				$answer = $curl->post($url,$params,$headers);
				
				return json_decode($answer,true);



			} catch (\Exception $e) {
				$this->logger->error('There has been an issue while contacting the external deletion script');
				$this->logger->logException($e, ['app' => Application::APP_NAME]);
			}

    }

}