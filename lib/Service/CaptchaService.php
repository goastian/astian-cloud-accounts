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
	/**
	 * Generate a CAPTCHA image and return its binary representation.
	 *
	 * @return string The binary representation of the generated CAPTCHA image.
	 */
	public function generateCaptcha(): string {
		// Configuration parameters
		$width = self::WIDTH;
		$height = self::HEIGHT;
		$numbers = self::NUMBERS;
		$symbols = self::SYMBOLS;
		$noiseLevel = self::NOISE_LEVEL;

		// Create the initial image resource
		$im = imagecreatetruecolor($width, $height);
		$ns = imagecolorallocate($im, 200, 200, 200); // Noise color
		$image = imagecreate($width, $height) or die('Unable to initialize GD');

		// Draw random lines on the image
		$this->drawRandomLines($image, 10);

		// Draw the first random character
		$x = 10 + mt_rand(0, 10);
		$num1 = $this->getRandomCharacter($numbers);
		$this->updateImage($image, $x, $num1);

		// Draw a random space and the operator symbol
		$x = $this->drawCharacterWithRandomSpace($image, $x, $symbols);

		// Draw the second random character
		$num2 = $this->getRandomCharacter($numbers);
		$this->updateImage($image, $x, $num2);

		// Rotate the image by a random angle
		$image = imagerotate($image, mt_rand(-15, 15), 0);

		// Draw a random space and the equal sign
		$x = $this->drawCharacterWithRandomSpace($image, $x, "=");

		// Combine the generated code
		$code = $num1 . $symbols . $num2;

		// Evaluate the mathematical expression
		eval("\$code = $code;");

		// Add random noise to the image
		$this->addNoise($image, $noiseLevel);

		// Output the image as PNG
		ob_start();
		header('Content-Type: image/png');
		imagepng($image);
		$imageData = ob_get_clean();

		// Destroy the image resource
		imagedestroy($image);

		// Update session with the operands and operator
		$this->updateSession($num1, $num2, $symbols);

		// Return the binary representation of the generated image
		return $imageData;
	}

	/**
	 * Draws random lines on the given image.
	 *
	 * @param $image The image resource.
	 * @param int $count The number of lines to draw.
	 */
	private function drawRandomLines(&$image, $count) {
		for ($i = 0; $i < $count; $i++) {
			imageline(
				$image,
				mt_rand(0, self::WIDTH),
				mt_rand(0, self::HEIGHT),
				mt_rand(0, self::WIDTH),
				mt_rand(0, self::HEIGHT),
				imagecolorallocate(
					$image,
					mt_rand(200, 255),
					mt_rand(200, 255),
					mt_rand(200, 255)
				)
			);
		}
	}
	
	/**
	 * Get a random character from the given string.
	 *
	 * @param string $string The input string from which to extract a random character.
	 *
	 * @return string The randomly selected character from the input string.
	 */
	private function getRandomCharacter(string $string): string {
		return substr($string, rand(0, strlen($string) - 1), 1);
	}

	
	/**
	 * Draw a character on the image with a random horizontal space.
	 *
	 * @param $image The image resource to draw on.
	 * @param int $x The initial x-coordinate for drawing the character.
	 * @param string $char The character to be drawn on the image.
	 *
	 * @return int The updated x-coordinate after drawing the character.
	 */
	private function drawCharacterWithRandomSpace(&$image, int $x, string $char): int {
		$x += 10 + mt_rand(0, 10);
		$this->updateImage($image, $x, $char);
		return $x;
	}

	
	/**
	 * Add random noise to the image.
	 *
	 * @param $image The image resource to add noise to.
	 * @param int $noiseLevel The number of random pixels to add as noise.
	 *
	 * @return void
	 */
	private function addNoise(&$image, int $noiseLevel): void {
		// Define noise color
		$ns = imagecolorallocate($image, 200, 200, 200);

		// Add random pixels as noise
		for ($i = 0; $i < $noiseLevel; $i++) {
			for ($j = 0; $j < $noiseLevel; $j++) {
				imagesetpixel(
					$image,
					rand(0, self::WIDTH - 1),  // Adjusted range to avoid exceeding image dimensions
					rand(0, self::HEIGHT - 1), // Adjusted range to avoid exceeding image dimensions
					$ns
				);
			}
		}
	}

	
	/**
	 * Update session variables with provided numeric operands and operator symbols.
	 *
	 * @param string $num1 The first numeric operand.
	 * @param string $num2 The second numeric operand.
	 * @param string $symbols The operator symbols.
	 *
	 * @return void
	 */
	private function updateSession(string $num1, string $num2, string $symbols): void {
		$this->session->set('operand1', $num1);
		$this->session->set('operand2', $num2);
		$this->session->set('operator', $symbols);
		$this->session->set('captcha_verified', false);
	}

	
	/**
	 * Update the image with a character at the specified coordinates.
	 *
	 * @param $image The image resource to update.
	 * @param int $x The x-coordinate where the character will be placed.
	 * @param string $num The character to be added to the image.
	 *
	 * @return void
	 */
	private function updateImage(&$image, int $x, string $num): void {
		// Generate a random color for the character
		$color = imagecolorallocate($image, mt_rand(0, 155), mt_rand(0, 155), mt_rand(0, 155));

		// Place the character on the image
		imagechar(
			$image,
			mt_rand(4, 5), // Font size (randomly chosen between 4 and 5)
			$x,
			mt_rand(5, 20), // Vertical position (randomly chosen between 5 and 20)
			$num,
			$color
		);
	}

	

}
