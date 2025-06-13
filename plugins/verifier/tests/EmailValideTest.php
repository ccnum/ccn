<?php

namespace Spip\Verifier\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @covers verifier_email_dist()
**/
class EmailValideTest extends TestCase {
	public static function setUpBeforeClass(): void {
		require_once dirname(__DIR__) . '/verifier/email.php';
		require_once dirname(__DIR__) . '/inc/is_email.php';
	}

	public static function dataEmailsValides() {

		$datas = [
			// Source https://en.wikipedia.org/wiki/Email_address#Valid_email_addresses
			[
				'simple@example.com',
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => true,
				]
			],
			[
				'very.common@example.com',
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => true,
				]
			],
			[
				'FirstName.LastName@EasierReading.org', // (case is always ignored after the @ and usually before)
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => true,
				]
			],
			[
				'x@example.com', // (one-letter local-part)
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => true,
				]
			],
			[
				'long.email-address-with-hyphens@and.subdomains.example.com',
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => true,
				]
			],
			[
				'user.name+tag+sorting@example.com', // (may be routed to user.name@example.com inbox depending on mail server)
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'name/surname@example.com', // (slashes are a printable character, and allowed)
				[
					'normal' => false,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'admin@example', // (local domain name with no TLD, although ICANN highly discourages dotless email addresses[32])
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'example@s.example', // (see the List of Internet top-level domains)
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'" "@example.org', // (space between the quotes)
				[
					'normal' => false,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'"john..doe"@example.org', // (quoted double dot)
				[
					'normal' => false,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'mailhost!username@example.org', // (bangified host route used for uucp mailers)
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'"very.(),:;<>[]\".VERY.\"very@\\ \"very\".unusual"@strange.example.com', // (include non-letters character AND multiple at sign, the first one being double quoted)
				[
					'normal' => false,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'user%example.com@example.org', // (% escaped mail route to user@example.com via example.org)
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'user-@example.org', // (local-part ending with non-alphanumeric character from the list of allowed printable characters)
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => true,
				]
			],
			[
				'postmaster@[123.123.123.123]', //(IP addresses are allowed instead of domains when in square brackets, but strongly discouraged)
				[
					'normal' => false,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'postmaster@[IPv6:2001:0db8:85a3:0000:0000:8a2e:0370:7334]', // (IPv6 uses a different syntax)
				[
					'normal' => false,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'_test@[IPv6:2001:0db8:85a3:0000:0000:8a2e:0370:7334]', // (begin with underscore different syntax)
				[
					'normal' => false,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'I❤️CHOCOLATE@example.com', // (emoji are only allowed with SMTPUTF8)
				[
					'normal' => true,
					'rfc5322' => false,
					'strict' => false,
				]
			],

			// https://github.com/apache/commons-validator/blob/master/src/test/java/org/apache/commons/validator/routines/EmailValidatorTest.java
			[
				'jsmith@apache.org',
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => true,
				]
			],
			[
				'jsmith@apache.com',
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => true,
				]
			],
			[
				'jsmith@apache.net',
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => true,
				]
			],
			[
				'jsmith@apache.info',
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => true,
				]
			],
			[
				'someone@yahoo.museum',
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'joe1blow@apache.org',
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => true,
				]
			],
			[
				'joe$blow@apache.org',
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'joe-@apache.org',
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => true,
				]
			],
			[
				'joe_@apache.org',
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => true,
				]
			],
			[
				'joe+@apache.org', // + is valid unquoted
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'joe!@apache.org', // ! is valid unquoted
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'joe*@apache.org', // * is valid unquoted
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'joe\'@apache.org', // ' is valid unquoted
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'joe%45@apache.org', // % is valid unquoted
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'joe?@apache.org', // ? is valid unquoted
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'joe&@apache.org', // & ditto
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'joe=@apache.org', // = ditto
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'+joe@apache.org', // + is valid unquoted
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'!joe@apache.org', // ! is valid unquoted
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'*joe@apache.org', // * is valid unquoted
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'\'joe@apache.org', // ' is valid unquoted
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'%joe45@apache.org', // % is valid unquoted
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'?joe@apache.org', // ? is valid unquoted
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'&joe@apache.org', // & ditto
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'=joe@apache.org', // = ditto
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'+@apache.org', // + is valid unquoted
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'!@apache.org', // ! is valid unquoted
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'*@apache.org', // * is valid unquoted
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'\'@apache.org', // ' is valid unquoted
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'%@apache.org', // % is valid unquoted
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'?@apache.org', // ? is valid unquoted
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'&@apache.org', // & ditto
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'=@apache.org', // = ditto
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'joe.ok@apache.org', // . allowed embedded
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => true,
				]
			],
			[
				'"joe."@apache.org', // Quoted Special characters are valid
				[
					'normal' => false,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'".joe"@apache.org', // Quoted Special characters are valid
				[
					'normal' => false,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'"joe+"@apache.org', // Quoted Special characters are valid
				[
					'normal' => false,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'"joe@"@apache.org', // Quoted Special characters are valid
				[
					'normal' => false,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'"joe!"@apache.org', // Quoted Special characters are valid
				[
					'normal' => false,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'"joe*"@apache.org', // Quoted Special characters are valid
				[
					'normal' => false,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'"joe\'"@apache.org', // Quoted Special characters are valid
				[
					'normal' => false,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'"joe("@apache.org', // Quoted Special characters are valid
				[
					'normal' => false,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'"joe)"@apache.org', // Quoted Special characters are valid
				[
					'normal' => false,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'"joe,"@apache.org', // Quoted Special characters are valid
				[
					'normal' => false,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'"joe%45"@apache.org', // Quoted Special characters are valid
				[
					'normal' => false,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'"joe;"@apache.org', // Quoted Special characters are valid
				[
					'normal' => false,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'"joe?"@apache.org', // Quoted Special characters are valid
				[
					'normal' => false,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'"joe&"@apache.org', // Quoted Special characters are valid
				[
					'normal' => false,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'"joe="@apache.org', // Quoted Special characters are valid
				[
					'normal' => false,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'".."@apache.org', // Quoted Special characters are valid
				[
					'normal' => false,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'"john\\"doe"@apache.org', // escaped quote character valid in quoted string
				[
					'normal' => false,
					'rfc5322' => true,
					'strict' => false,
				]
			],
			[
				'john56789.john56789.john56789.john56789.john56789.john56789.john@example.com',
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => true,
				]
			],
			[
				'\\>escape\\\\special\\^characters\\<@example.com',
				[
					'normal' => false,
					'rfc5322' => false,
					'strict' => false,
				]
			],
			[
				'Abc\\@def@example.com',
				[
					'normal' => false,
					'rfc5322' => false,
					'strict' => false,
				]
			],
			[
				'space\\ monkey@example.com"',
				[
					'normal' => false,
					'rfc5322' => false,
					'strict' => false,
				]
			],

			// Autres cas rencontrés ?
			/*
			[
				'nom+prenom@example.com',
				[
					'normal' => true,
					'rfc5322' => true,
					'strict' => true,
				]
			],*/

		];

		return $datas;
	}

	public static function dataEmailsInvalides() {

		$datas = [
			// Source https://en.wikipedia.org/wiki/Email_address#Valid_email_addresses
			[
				'abc.example.com', // (no @ character)
				[
					'normal' => false,
					'rfc5322' => false,
					'strict' => false,
				],
				'a@b@c@example.com', // (only one @ is allowed outside quotation marks)
				[
					'normal' => false,
					'rfc5322' => false,
					'strict' => false,
				],
				'a"b(c)d,e:f;g<h>i[j\k]l@example.com', // (none of the special characters in this local-part are allowed outside quotation marks)
				[
					'normal' => false,
					'rfc5322' => false,
					'strict' => false,
				],
				'just"not"right@example.com', // (quoted strings must be dot separated or be the only element making up the local-part)
				[
					'normal' => false,
					'rfc5322' => false,
					'strict' => false,
				],
				'this is"not\allowed@example.com', // (spaces, quotes, and backslashes may only exist when within quoted strings and preceded by a backslash)
				[
					'normal' => false,
					'rfc5322' => false,
					'strict' => false,
				],
				'this\ still\"not\\allowed@example.com', // (even if escaped (preceded by a backslash), spaces, quotes, and backslashes must still be contained by quotes)
				[
					'normal' => false,
					'rfc5322' => false,
					'strict' => false,
				],
				'1234567890123456789012345678901234567890123456789012345678901234+x@example.com', // (local-part is longer than 64 characters)
				[
					'normal' => false,
					'rfc5322' => false,
					'strict' => false,
				],
				'i.like.underscores@but_they_are_not_allowed_in_this_part', // (underscore is not allowed in domain part)
				[
					'normal' => false,
					'rfc5322' => false,
					'strict' => false,
				],


				// https://github.com/apache/commons-validator/blob/master/src/test/java/org/apache/commons/validator/routines/EmailValidatorTest.java
				[
					'jsmith@apache.',
					[
						'normal' => false,
						'rfc5322' => false,
						'strict' => false,
					]
				],
				[
					'jsmith@apache.c',
					[
						'normal' => false,
						'rfc5322' => false,
						'strict' => false,
					]
				],
				[
					'someone@yahoo.mu-seum',
					[
						'normal' => false,
						'rfc5322' => false,
						'strict' => false,
					]
				],
				[
					'joe.@apache.org', // . not allowed at end of local part
					[
						'normal' => false,
						'rfc5322' => false,
						'strict' => false,
					]
				],
				[
					'.joe@apache.org', // . not allowed at start of local part
					[
						'normal' => false,
						'rfc5322' => false,
						'strict' => false,
					]
				],
				[
					'.@apache.org', // . not allowed alone
					[
						'normal' => false,
						'rfc5322' => false,
						'strict' => false,
					]
				],
				[
					'joe..ok@apache.org', // .. not allowed embedded
					[
						'normal' => false,
						'rfc5322' => false,
						'strict' => false,
					]
				],
				[
					'..@apache.org', //.. not allowed alone
					[
						'normal' => false,
						'rfc5322' => false,
						'strict' => false,
					]
				],
				[
					'joe(@apache.org',
					[
						'normal' => false,
						'rfc5322' => false,
						'strict' => false,
					]
				],
				[
					'joe)@apache.org',
					[
						'normal' => false,
						'rfc5322' => false,
						'strict' => false,
					]
				],
				[
					'joe,@apache.org',
					[
						'normal' => false,
						'rfc5322' => false,
						'strict' => false,
					]
				],
				[
					'joe;@apache.org',
					[
						'normal' => false,
						'rfc5322' => false,
						'strict' => false,
					]
				],
				[
					'john56789.john56789.john56789.john56789.john56789.john56789.john5@example.com',
					[
						'normal' => false,
						'rfc5322' => false,
						'strict' => false,
					]
				],
				[
					'Abc@def@example.com',
					[
						'normal' => false,
						'rfc5322' => false,
						'strict' => false,
					]
				],

				// Autres cas rencontrés ?
				/*
				[
					'nom+prenom@example.com',
					[
						'normal' => true,
						'rfc5322' => true,
						'strict' => true,
					]
				],*/
			],

		];

		return $datas;
	}


	/**
	 * @dataProvider dataEmailsValides
	 **/
	function testEmailValideNormal($valeur, $expected) {
		$erreur = verifier_email_dist($valeur, ['mode' => 'normal']);
		$actual = ($erreur == '' ? true : false);
		$this->assertEquals($expected['normal'], $actual);
	}

	/**
	 * @dataProvider dataEmailsInvalides
	 **/
	function testEmailInvalideNormal($valeur, $expected) {
		$erreur = verifier_email_dist($valeur, ['mode' => 'normal']);
		$actual = ($erreur == '' ? true : false);
		$this->assertEquals($expected['normal'], $actual);
	}

	/**
	 * @dataProvider dataEmailsValides
	 **/
	function testEmailValideRfc5322($valeur, $expected) {
		$erreur = verifier_email_dist($valeur, ['mode' => 'rfc5322']);
		$actual = ($erreur == '' ? true : false);
		$this->assertEquals($expected['rfc5322'], $actual);
	}

	/**
	 * @dataProvider dataEmailsInvalides
	 **/
	function testEmailInvalideRfc5322($valeur, $expected) {
		$erreur = verifier_email_dist($valeur, ['mode' => 'rfc5322']);
		$actual = ($erreur == '' ? true : false);
		$this->assertEquals($expected['rfc5322'], $actual);
	}


	/**
	 * @dataProvider dataEmailsValides
	 **/
	function testEmailValideStrict($valeur, $expected) {
		$erreur = verifier_email_dist($valeur, ['mode' => 'strict']);
		$actual = ($erreur == '' ? true : false);
		$this->assertEquals($expected['strict'], $actual);
	}

	/**
	 * @dataProvider dataEmailsInvalides
	 **/
	function testEmailInvalideStrict($valeur, $expected) {
		$erreur = verifier_email_dist($valeur, ['mode' => 'strict']);
		$actual = ($erreur == '' ? true : false);
		$this->assertEquals($expected['strict'], $actual);
	}
}
