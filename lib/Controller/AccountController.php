<?php

/*
   * Copyright 2022 - Murena SAS - tous droits réservés
   */

namespace OCA\EcloudAccounts\Controller;

use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\AppFramework\Http\TemplateResponse;
use OCA\EcloudAccounts\AppInfo\Application;
use OCA\EcloudAccounts\Service\LDAPConnectionService;
use OCP\ISession;
use Psr\Log\LoggerInterface;
use OCA\LdapWriteSupport\Service\Configuration;
use OCP\LDAP\ILDAPProvider;
use Exception;

class AccountController extends Controller {
	protected $appName;
	protected $request;
	// private ISession $session;
	private $LDAPConnectionService;
	private $logger;
	private $configuration;
	private $ldapProvider;
	private $ldapConnect;
	private int $quotaInBytes = 1073741824;
	private int $ldapQuota;

	public function __construct(
		$AppName,
		IRequest $request,
		// ISession $session,
		LDAPConnectionService $LDAPConnectionService,
		LoggerInterface $logger,
		ILDAPProvider $LDAPProvider,
		Configuration $configuration
	) {
		parent::__construct($AppName, $request);
		$this->appName = $AppName;
		// $this->session = $session;
		$this->LDAPConnectionService = $LDAPConnectionService;
		$this->ldapProvider = $LDAPProvider;
		$this->configuration = $configuration;
		$this->logger = $logger;
		$quota = getenv('CLOUD_QUOTA_IN_BYTES');
		if (!$quota) {
			$this->ldapQuota = $this->quotaInBytes;
		} else {
			$this->ldapQuota = intval($quota);
		}
	}

	/**
	 * @NoAdminRequired
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 */
	public function index() {
		return new TemplateResponse(
			Application::APP_ID,
			'signup',
			['appName' => Application::APP_ID],
			TemplateResponse::RENDER_AS_GUEST
		);
	}
	/**
	 * @NoAdminRequired
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 */
	public function create(string $displayname, string $email, string $username, string $password) {
		$connection = $this->LDAPConnectionService->getLDAPConnection();
		$base = $this->LDAPConnectionService->getLDAPBaseUsers()[0];

		// $ldif = 'username={UID},{BASE}';
		$newUserDN = "username=$username," . $base;
		$newUserEntry = [
			'mail' => $email,
			'uid' => $username,
			'displayName' => $displayname,
			'cn' => $username,
			'sn' => $username,
			'userPassword' => $password,
			'objectclass' => 'inetOrgPerson'
		];
		$ret = ldap_add($connection, $newUserDN, $newUserEntry);

		$message = 'Create LDAP user \'{username}\' ({dn})';
		$logMethod = 'info';
		if ($ret === false) {
			$message = 'Unable to create LDAP user \'{username}\' ({dn})';
			$logMethod = 'error';
		}
		$this->logger->$logMethod($message, [
			'app' => Application::APP_ID,
			'username' => $username,
			'dn' => $newUserDN,
		]);

		if (!$ret && $this->configuration->isPreventFallback()) {
			throw new \Exception('Cannot create user: ' . ldap_error($connection), ldap_errno($connection));
		}

		// Set password through ldap password exop, if supported
		try {
			$ret = ldap_exop_passwd($connection, $newUserDN, '', $password);
			if ($ret === false) {
				$message = 'ldap_exop_passwd failed, falling back to ldap_mod_replace to to set password for new user';
				$this->logger->debug($message, ['app' => Application::APP_ID]);

				// Fallback to `userPassword` in case the server does not support exop_passwd
				$ret = ldap_mod_replace($connection, $newUserDN, ['userPassword' => $password]);
				if ($ret === false) {
					$message = 'Failed to set password for new user {dn}';
					$this->logger->error($message, [
						'app' => Application::APP_ID,
						'dn' => $newUserDN,
					]);
				}
			}
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e, 'app' => Application::APP_ID]);
		}
		return [$newUserDN, $newUserEntry];
	}

	// // Create a new LDAP connection
	// $ldap = ldap_connect("ldap.example.com");

	// // Bind to the LDAP server
	// ldap_bind($ldap, "cn=admin,dc=example,dc=com", "secret");

	// // Create a new LDAP user
	// $user = array(
	//   "cn" => "John Doe",
	//   "sn" => "Doe",
	//   "givenName" => "John",
	//   "uid" => "jdoe",
	//   "userPassword" => "secret",
	// );

	// // Add the user to the LDAP server
	// ldap_add($ldap, "cn=John Doe,dc=example,dc=com", $user);

	// // Create a new LDAP group
	// $group = array(
	//   "cn" => "Sales",
	//   "objectClass" => "groupOfNames",
	//   "member" => array("cn=John Doe,dc=example,dc=com"),
	// );

	// // Add the group to the LDAP server
	// ldap_add($ldap, "cn=Sales,dc=example,dc=com", $group);

	// // Close the LDAP connection
	// ldap_close($ldap);

	public function buildNewEntry($username, $password, $base): array {
		// Make sure the parameters don't fool the following algorithm
		if (strpos($username, PHP_EOL) !== false) {
			throw new Exception('Username contains a new line');
		}
		if (strpos($password, PHP_EOL) !== false) {
			throw new Exception('Password contains a new line');
		}
		if (strpos($base, PHP_EOL) !== false) {
			throw new Exception('Base DN contains a new line');
		}

		$ldif = 'dn: username={UID},{BASE}' . PHP_EOL .
			'objectClass: inetOrgPerson' . PHP_EOL .
			'username: {UID}' . PHP_EOL .
			'displayName: {UID}' . PHP_EOL .
			'cn: {UID}' . PHP_EOL .
			'sn: {UID}';

		$ldif = str_replace('{UID}', $username, $ldif);
		$ldif = str_replace('{PWD}', $password, $ldif);
		$ldif = str_replace('{BASE}', $base, $ldif);

		$entry = [];
		$lines = explode(PHP_EOL, $ldif);
		foreach ($lines as $line) {
			$split = explode(':', $line, 2);
			$key = trim($split[0]);
			$value = trim($split[1]);
			if (!isset($entry[$key])) {
				$entry[$key] = $value;
			} elseif (is_array($entry[$key])) {
				$entry[$key][] = $value;
			} else {
				$entry[$key] = [$entry[$key], $value];
			}
		}
		$dn = $entry['dn'];
		unset($entry['dn']);

		$info["cn"] = "John Jones";
		$info["sn"] = "Jones";
		$info["objectclass"] = "person";

		return [$dn, $info];
	}
	public function ensureAttribute(array &$ldif, string $attribute, string $fallbackValue): void {
		$lowerCasedLDIF = array_change_key_case($ldif, CASE_LOWER);
		if (!isset($lowerCasedLDIF[strtolower($attribute)])) {
			$ldif[$attribute] = $fallbackValue;
		}
	}

	/**
	 * @NoAdminRequired
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 */
	// public function recaptcha()
	// {
	// 	session_set_cookie_params(['SameSite' => 'None', 'Secure' => true]);
	// 	session_start();

	// 	$width  = 80;
	// 	$height  = 40;
	// 	$length = 2;
	// 	$liste = '123456789ABCDEFGHIJKLMNPQRSTUVWXYZ';
	// 	$numbers = '123456789';
	// 	$symbols = '+-';
	// 	$code    = '';
	// 	$counter = 0;
	// 	$im = imagecreatetruecolor($width, $height);
	// 	$ns = imagecolorallocate($im, 200, 200, 200); //noise color
	// 	//amount of background noise to add in captcha image
	// 	$noise_level = 13;

	// 	$image = imagecreate($width, $height) or die('Impossible d\'initializer GD');

	// 	for ($i = 0; $i < 10; $i++) {
	// 		imageline(
	// 			$image,
	// 			mt_rand(0, $width),
	// 			mt_rand(0, $height),
	// 			mt_rand(0, $width),
	// 			mt_rand(0, $height),
	// 			imagecolorallocate(
	// 				$image,
	// 				mt_rand(200, 255),
	// 				mt_rand(200, 255),
	// 				mt_rand(200, 255)
	// 			)
	// 		);
	// 	}
	// 	$x = 10 + mt_rand(0, 10);
	// 	$num1 = substr($numbers, rand(0, strlen($numbers) - 1), 1);
	// 	$this->update_image($image, $x, $num1);

	// 	$x += 10 + mt_rand(0, 10);
	// 	$sym = substr($symbols, rand(0, strlen($symbols) - 1), 1);
	// 	$this->update_image($image, $x, $sym);

	// 	$x += 10 + mt_rand(0, 10);
	// 	$num2 = substr($numbers, rand(0, strlen($numbers) - 1), 1);
	// 	$this->update_image($image, $x, $num2);

	// 	# Rotate numbers randomly -15 to +15 degrees
	// 	$image = imagerotate($image, mt_rand(-15, 15), 0);

	// 	$x += 10 + mt_rand(0, 10);
	// 	$this->update_image($image, $x, "=");

	// 	$code = $num1 . $sym . $num2;

	// 	eval("\$code = $code;");

	// 	// Add some noise to the image.
	// 	for ($i = 0; $i < $noise_level; $i++) {
	// 		for ($j = 0; $j < $noise_level; $j++) {
	// 			imagesetpixel(
	// 				$image,
	// 				rand(0, $width),
	// 				rand(0, $height), //make sure the pixels are rcandom and don't overflow out of the image
	// 				$ns
	// 			);
	// 		}
	// 	}

	// 	header('Content-Type: image/png');
	// 	imagepng($image);
	// 	imagedestroy($image);

	// 	$_SESSION['securecode'] = "$code";
	// }

	// function update_image(&$image, $x, $num)
	// {
	// 	imagechar(
	// 		$image,
	// 		mt_rand(4, 5),
	// 		$x,
	// 		mt_rand(5, 20),
	// 		$num,
	// 		imagecolorallocate($image, mt_rand(0, 155), mt_rand(0, 155), mt_rand(0, 155))
	// 	);
	// }
}
