<?php

/*
   * Copyright 2022 - Murena SAS - tous droits réservés
   */

namespace OCA\EcloudAccounts\Controller;

use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\AppFramework\Http\TemplateResponse;
use OCA\EcloudAccounts\AppInfo\Application;
use OCP\ISession;

class AccountController extends Controller
{
	protected $appName;
	protected $request;
	// private ISession $session;


	public function __construct(
		$AppName,
		IRequest $request,
		ISession $session
	) {
		parent::__construct($AppName, $request);
		$this->appName = $AppName;
		// $this->session = $session; 
	}

	/**
	 * @NoAdminRequired
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 */
	public function index()
	{
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
