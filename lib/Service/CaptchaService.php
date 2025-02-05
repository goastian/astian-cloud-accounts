<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Service;

use OCP\ISession;

class CaptchaService {
	private $session;
	private const WIDTH = 80;
	private const HEIGHT = 40;
	private const NUMBERS = '123456789';
	private const SYMBOLS = '+-';
	private const NOISE_LEVEL = 13;
	public const CAPTCHA_RESULT_KEY = 'captcha_result';

	public function __construct(ISession $session) {
		$this->session = $session;
	}
	/**
	 * Generate a captcha image and return its binary representation.
	 *
	 * @return string|null Binary representation of the generated image, or null on failure.
	 */
	public function generateCaptcha(): ?string {
		// Configuration parameters
		$width = self::WIDTH;
		$height = self::HEIGHT;
		$numbers = self::NUMBERS;
		$symbols = self::SYMBOLS;
		$noiseLevel = self::NOISE_LEVEL;
	
		// Create the initial image resource
		$im = imagecreatetruecolor($width, $height);
		$ns = imagecolorallocate($im, 200, 200, 200); // Noise color
		$image = imagecreate($width, $height);
		if (!$image) {
			return null;
		}
	
		// Draw random lines on the image
		$this->drawRandomLines($image, 10);
	
		$x = 10 + mt_rand(0, 10);
		$num1 = $this->getRandomCharacter($numbers);
		$this->updateImage($image, $x, $num1);
	
		$x += 10 + mt_rand(0, 10);
		$sym = $this->getRandomCharacter($symbols);
		$this->updateImage($image, $x, $sym);
	
		$x += 10 + mt_rand(0, 10);
		$num2 = $this->getRandomCharacter($numbers);
		$this->updateImage($image, $x, $num2);
	
		// Rotate the image by a random angle
		$image = imagerotate($image, mt_rand(-15, 15), 0);
	
		// Draw a random space and the equal sign
		$x = $this->drawCharacterWithRandomSpace($image, $x, "=");
	
		// Combine the generated code
		$code = $num1 . $sym . $num2;
	
		// Evaluate the mathematical expression
		eval("\$code = $code;");
	
		// Add random noise to the image
		$this->addNoise($image, $noiseLevel);
	
		// Output the image as PNG into a variable
		ob_start();
		imagepng($image);
		$imageData = ob_get_clean();
	
		// Destroy the image resource
		imagedestroy($image);
	
		$num1 = intval($num1);
		$num2 = intval($num2);
		// Calculate result
		$result = $this->calculateResult($num1, $num2, $sym);
	
		// Update session with the result
		$this->updateSession($result);
	
		// Return the binary representation of the generated image
		return $imageData;
	}
	/**
	 * Calculate the result of a mathematical operation.
	 *
	 * @param int $operand1 The first operand.
	 * @param int $operand2 The second operand.
	 * @param string $operator The mathematical operator ('+' or '-').
	 *
	 * @return int The result of calcuulated
	 */
	private function calculateResult(int $operand1, int $operand2, string $operator): int {
		
		switch ($operator) {
			case '+':
				return $operand1 + $operand2;
			case '-':
				return $operand1 - $operand2;
			default:
				return 0;
		}
		
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
	 * @param string $captchaResult Captcha Result.
	 *
	 * @return void
	 */
	private function updateSession(float $captchaResult): void {
		$this->session->set(self::CAPTCHA_RESULT_KEY, $captchaResult);
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
