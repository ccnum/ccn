<?php

namespace Spip\Verifier\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @covers verifier_email_dist()
**/
class EmailExplodeTest extends TestCase {
	public static function setUpBeforeClass(): void {
		require_once dirname(__DIR__) . '/verifier/email.php';
	}

	public static function dataEmails() {

		$datas = [
			[
				'simple@example.com',
				[
					'simple@example.com',
				]
			],
			[
				'simple@example.com, john@example.org',
				[
					'simple@example.com',
					'john@example.org',
				]
			],
			[
				'simple@example.com, john@example.org, ',
				[
					'simple@example.com',
					'john@example.org',
				]
			],
			[
				'"very.(),:;<>[]\".VERY.\"very@\\ \"very\".unusual"@strange.example.com',
				[
					'"very.(),:;<>[]\".VERY.\"very@\\ \"very\".unusual"@strange.example.com',
				]
			],
			[
				'"joe,"@apache.org',
				[
					'"joe,"@apache.org',
				]
			],
			[
				'"joe,"@apache.org, ',
				[
					'"joe,"@apache.org',
				]
			],
			[
				'"joe,"@apache.org, "joe2,"@apache.org',
				[
					'"joe,"@apache.org',
					'"joe2,"@apache.org',
				]
			],
		];

		return $datas;
	}


	/**
	 * @dataProvider dataEmails
	 **/
	function testEmailExplode($valeur, $expected) {
		$actual = verifier_email_explode_emails($valeur);
		$this->assertEquals($expected, $actual);
	}
}
