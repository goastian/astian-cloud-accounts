<?php
namespace OCA\EcloudDropAccount\Hooks;
use OCA\EcloudDropAccount\AppInfo\Application;
use OCP\IUserManager;
use OCP\ILogger;
use OCP\IUser;
use OCP\IUserSession;
use OCP\IConfig;


require_once 'curl.class.php';
use Curl;

class UserHooks {

    private $userManager;
    private $logger;

    public function __construct(IUserManager $userManager, ILogger $logger, IUserSession $userSession, IConfig $config){
        $this->userManager = $userManager;
        $this->logger = $logger;
        $this->userSession = $userSession;
        $this->config = $config;
    }

    public function register() {

    	
    	

        $callback = function(IUser $user) {
            // when auto delete action is done by user, fire postDelete hook 
            // to send postDelete actions to be done for /e/ specific setup



            $user = $this->userSession->getUser();
			//username IS in the form user@$DOMAIN
			$username = $user->getUID();
			$domains = $this->config->getSystemValue('trusted_domains');
			


			$externalDelete = $this->ecloudDelete($username,$domains);

            
        };
        $this->userManager->listen('\OC\User', 'postDelete', $callback);
    }		

    public function ecloudDelete($userID,$trusted_domains) {
    	// once NC deleted account datas
    	// do specific ecloud selfhosting actions 
    	// post delete action is delegated welcome container


    	// CHECK : compare user account and domain, to be sure it's identical
    	// actually only comparing $trusted_domains[0] >> main domain
    	// TODO : handle account deletion with multiple trusted domains!!


		if (strpos($userID, $trusted_domains[0]) == false) {
		    // user's login AND domain do not match; exit

		    return FALSE;
		} else {

			// build welcome domain url from main NC domain
			$welcomeDomain = "http://welcome.".$trusted_domains[0];

	    	//$welcomeDomain = "http://localhost";
	    	$postDeleteScript = "/e.welcome/postDelete.php";
	    	// welcome secret is generated at ecloud selfhosting install
	    	// and added as a custom var in NC's config
	    	$welcomeSecret = $this->config->getSystemValue('e_welcome_secret');

	    	

	    	$curl = new Curl();

	    	// send action to  docker_welcome
	    	//Handling the non NC part of deletion process 

	    	try {

				$headers = array(
			    'Content-Type: application/json'
			)	;
	    		$params = array(
	    			'sec' => $welcomeSecret,
	    			'uid' => $userID,
	    			'domain' => $trusted_domains[0]
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

}