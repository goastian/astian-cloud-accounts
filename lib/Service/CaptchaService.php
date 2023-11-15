<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Service;

use OCP\ISession;

class CaptchaService {
	private $session;
	public const WIDTH = 80;
	public const HEIGHT = 40;
	public const NUMBERS = '123456789';
	public const SYMBOLS = '+-';
	public const NOISE_LEVEL = 13;

	public function __construct(ISession $session) {
		$this->session = $session;
	}
	

	public function generateCaptcha(): string {
		$width = self::WIDTH;
		$height = self::HEIGHT;
		$numbers = self::NUMBERS;
		$symbols = self::SYMBOLS;
		$noise_level = self::NOISE_LEVEL;
		$code = '';
		
		$im = imagecreatetruecolor($width, $height);
		$ns = imagecolorallocate($im, 200, 200, 200); // noise color
		// amount of background noise to add in captcha image
		
	
		$image = imagecreate($width, $height) or die('Unable to initialize GD');
	
		for ($i = 0; $i < 10; $i++) {
			imageline(
				$image,
				mt_rand(0, $width),
				mt_rand(0, $height),
				mt_rand(0, $width),
				mt_rand(0, $height),
				imagecolorallocate(
					$image,
					mt_rand(200, 255),
					mt_rand(200, 255),
					mt_rand(200, 255)
				)
			);
		}
	
		$x = 10 + mt_rand(0, 10);
		$num1 = substr($numbers, rand(0, strlen($numbers) - 1), 1);
		$this->updateImage($image, $x, $num1);
	
		$x += 10 + mt_rand(0, 10);
		$sym = substr($symbols, rand(0, strlen($symbols) - 1), 1);
		$this->updateImage($image, $x, $sym);
	
		$x += 10 + mt_rand(0, 10);
		$num2 = substr($numbers, rand(0, strlen($numbers) - 1), 1);
		$this->updateImage($image, $x, $num2);
	
		// Rotate numbers randomly -15 to +15 degrees
		$image = imagerotate($image, mt_rand(-15, 15), 0);
	
		$x += 10 + mt_rand(0, 10);
		$this->updateImage($image, $x, "=");
	
		$code = $num1 . $sym . $num2;
	
		eval("\$code = $code;");
	
		// Add some noise to the image.
		for ($i = 0; $i < $noise_level; $i++) {
			for ($j = 0; $j < $noise_level; $j++) {
				imagesetpixel(
					$image,
					rand(0, $width),
					rand(0, $height), // make sure the pixels are random and don't overflow out of the image
					$ns
				);
			}
		}
	
		ob_start();
		header('Content-Type: image/png');
		imagepng($image);
		$imageData = ob_get_clean();
	
		imagedestroy($image);
	
		$this->session->set('operand1', $num1);
		$this->session->set('operand2', $num2);
		$this->session->set('operator', $sym);
		$this->session->set('captcha_verified', false);
	
		return $imageData;
	}
	
	public function updateImage(&$image, $x, $num) {
		imagechar(
			$image,
			mt_rand(4, 5),
			$x,
			mt_rand(5, 20),
			$num,
			imagecolorallocate($image, mt_rand(0, 155), mt_rand(0, 155), mt_rand(0, 155))
		);
	}

}
