<?php

namespace Spip\Verifier\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @covers verifier_telephone_dist()
 * @uses verifier_telephone_pays_patterns
 * @uses verifier_telephone_prefixes_pays_dist
 * @covers verifier_telephone_pays_es_dist
 * @covers verifier_telephone_pays_ch_dist
 * @covers verifier_telephone_pays_be_dist
 * @covers verifier_telephone_pays_fr_dist
 * @covers verifier_telephone_pays_lu_dist
 * @internal
 */

class TelephoneTest extends TestCase {
	public static function setUpBeforeClass(): void {
		require_once dirname(__DIR__) . '/verifier/telephone.php';
	}

	public static function dataTelephone() {
		$erreur_standard = _T('verifier:erreur_telephone');
		return  [
			// les numeros fr

			[ '', '0102030405', ['pays' => 'fr'] ],
			[ $erreur_standard, '010203040506', ['pays' => 'fr'] ],
			[ $erreur_standard, '01020304', ['pays' => 'fr'] ],
			[ '', '+33102030405', ['pays' => 'fr'] ],
			[ $erreur_standard, '+3310203040506', ['pays' => 'fr'] ],
			[ $erreur_standard, '+331020304', ['pays' => 'fr'] ],
			[ '', '0033102030405', ['pays' => 'fr'] ],
			[ $erreur_standard, '003310203040506', ['pays' => 'fr'] ],
			[ $erreur_standard, '00331020304', ['pays' => 'fr'] ],
			[ '', '+33102030405'],
			[ $erreur_standard, '+3310203040506'],
			[ $erreur_standard, '+331020304'],
			[ '', '0033102030405'],
			[ $erreur_standard, '003310203040506'],
			[ $erreur_standard, '00331020304'],
			[ '', '+33102030405', ['format' => 'fixe']],
			[ $erreur_standard, '+33102030405', ['format' => 'mobile']],
			[ '', '+33102030405', ['format' => 'all']],
			[ $erreur_standard, '+33602030405', ['format' => 'fixe']],
			[ '', '+33602030405', ['format' => 'mobile']],
			[ '', '+33602030405', ['format' => 'all']],
			[ $erreur_standard, '+33702030405', ['format' => 'fixe']],
			[ '', '+33702030405', ['format' => 'mobile']],
			[ '', '+33702030405', ['format' => 'all']],

			// verifier que le pays est bien surcharge par le prefixe international
			[ '', '+33102030405', ['format' => 'fixe', 'pays' => 'be']],
			[ $erreur_standard, '+33102030405', ['format' => 'mobile', 'pays' => 'be']],
			[ $erreur_standard, '+33602030405', ['format' => 'fixe', 'pays' => 'be']],
			[ '', '+33602030405', ['format' => 'mobile', 'pays' => 'be']],


			/**
			 * Belgique : https://fr.wikipedia.org/wiki/Num%C3%A9ro_de_t%C3%A9l%C3%A9phone#B
			 Format national 9 chiffres : 0ZZ CC CC CC ou 0Z CCC CC CC
			 Format international : +32 ZZ CC CC CC ou +32 Z CCC CC CC
			 Le premier chiffre dans le format national est toujours le zéro.

			 Il faut toujours composer l'indicatif de zone (Z ou ZZ). L'indicatif de zone fait le plus souvent 2 chiffres, excepté dans les grandes agglomérations où l'on est passé à 1 chiffre. (Bruxelles : 2, Anvers : 3, Liège : 4, Gand : 9). Les 070 et 078 ne sont pas utilisés pour des zones, mais pour des numéros spéciaux à bas prix (078 prix d'un appel local, 070 = 0,30 €/min), seul le 071 concerne bien une zone géographique.
			 GSM (national) 10 chiffres : de 045C CC CC CC à 049C CC CC CC
			 GSM (international) : de +32 45C CC CC CC à +32 49C CC CC CC

			 À l'heure actuelle, il n'est plus possible de déterminer l'opérateur à l'aide du numéro de téléphone portable (les numéros peuvent être portés chez un autre opérateur). Auparavant, les numéros étaient rattachés aux différents opérateurs comme suit : 049C = Orange, 047C = Proximus, 048C = Base.
			 Numéro gratuit : 0800/
			 Numéro payant a minimum 1 euro/min : 090C/

			 */
			[ '', '056 23 45 67', ['pays' => 'be'] ],
			[ '', '02 345 67 79', ['pays' => 'be'] ],
			[ $erreur_standard, '056 23 45 678', ['pays' => 'be'] ],
			[ $erreur_standard, '02 345 67 799', ['pays' => 'be'] ],
			[ $erreur_standard, '056 23 45 6', ['pays' => 'be'] ],
			[ $erreur_standard, '02 345 67 7', ['pays' => 'be'] ],
			[ '', '+3256 23 45 67', ['pays' => 'be'] ],
			[ '', '+322 345 67 79', ['pays' => 'be'] ],
			[ '', '+3256 23 45 67', ['format' => 'fixe'] ],
			[ '', '+322 345 67 79', ['format' => 'fixe'] ],
			[ $erreur_standard, '+3256 23 45 67', ['format' => 'mobile'] ],
			[ $erreur_standard, '+322 345 67 79', ['format' => 'mobile'] ],
			[ '', '+32451 23 45 67', ['format' => 'mobile'] ],
			[ $erreur_standard, '+32451 23 45 67', ['format' => 'fixe'] ],
			[ '', '+32460 23 45 67', ['format' => 'mobile'] ],
			[ $erreur_standard, '+32460 23 45 67', ['format' => 'fixe'] ],
			[ '', '+32471 23 45 67', ['format' => 'mobile'] ],
			[ $erreur_standard, '+32471 23 45 67', ['format' => 'fixe'] ],
			[ '', '+32496277272', ['format' => 'mobile'] ],

			/**
			 * Suisse : https://fr.wikipedia.org/wiki/Num%C3%A9ro_de_t%C3%A9l%C3%A9phone#S
			 Format national : 0CC CCC CC CC
			 Format international : +41 CC CCC CC CC
			 Les numéros de téléphones portables commencent par : 075 (en 2014), 076, 077, 078 ou 079 selon l'opérateur téléphonique.
			 */
			[ '', '012 345 67 89', ['pays' => 'ch'] ],
			[ $erreur_standard, '012 345 67 8', ['pays' => 'ch'] ],
			[ $erreur_standard, '012 345 67 899', ['pays' => 'ch'] ],
			[ '', '+4112 345 67 89'],
			[ $erreur_standard, '+4112 345 67 8'],
			[ $erreur_standard, '+4112 345 67 899'],
			[ '', '+4112 345 67 89', ['format' => 'fixe']],
			[ $erreur_standard, '+4112 345 67 89', ['format' => 'mobile']],
			[ $erreur_standard, '+4175 345 67 89', ['format' => 'fixe']],
			[ '', '+4175 345 67 89', ['format' => 'mobile']],
			[ $erreur_standard, '+4179 345 67 89', ['format' => 'fixe']],
			[ '', '+4179 345 67 89', ['format' => 'mobile']],

			/**
			 * Espagne : https://fr.wikipedia.org/wiki/Numéro_de_téléphone#E
			 Format international : +34 CCC.CC.CC.CC - pour les mobiles espagnols : +34 6CC.CCC.CCC
			 Format national : CCC.CC.CC.CC - pour les mobiles espagnols : 6CC.CCC.CCC
			 En pratique les fixes commencent par un 9 (ou un 8 pour les numeros gratuits) et les mobiles par un 6
			 */
			[ '', '923 45 67 89', ['pays' => 'es'] ],
			[ '', '+34923 45 67 89'],
			[ '', '+34923 45 67 89', ['format' => 'fixe'] ],
			[ $erreur_standard, '+34923 45 67 89', ['format' => 'mobile'] ],
			[ $erreur_standard, '+34623 45 67 89', ['format' => 'fixe'] ],
			[ '', '+34623 45 67 89', ['format' => 'mobile'] ],

			/**
			 * Luxembourg : https://fr.wikipedia.org/wiki/Numéro_de_téléphone#L
			 Format national : C CCCC ou CCCC CCCC ou CC CCCC, où le premier bloc de chiffres indique la zone géographique du numéro.
			 Format international : +352 CC CCCC ou +352 CCCC CCCC ou +352 C CCCC
			 Téléphonie mobile : 6X1 CCC CCC (national) ou +352 6X1 CCC CCC (international), où X peut valoir 2, 6 ou 9. Le numéro fait toujours 9 chiffres (national).
			 */
			[ '', '1 2345', ['pays' => 'lu'] ],
			[ '', '1234 5678', ['pays' => 'lu'] ],
			[ '', '12 3456', ['pays' => 'lu'] ],
			[ $erreur_standard, '12 34567', ['pays' => 'lu'] ],
			[ '', '+3521 2345', ['format' => 'fixe'] ],
			[ '', '+3521234 5678', ['format' => 'fixe'] ],
			[ '', '+35212 3456', ['format' => 'fixe'] ],
			[ $erreur_standard, '+3521 2345', ['format' => 'mobile'] ],
			[ $erreur_standard, '+3521234 5678', ['format' => 'mobile'] ],
			[ $erreur_standard, '+35212 3456', ['format' => 'mobile'] ],
			[ '', '+352621 123 456', ['format' => 'mobile'] ],
			[ '', '+352661 123 456', ['format' => 'mobile'] ],
			[ '', '+352691 123 456', ['format' => 'mobile'] ],
			[ $erreur_standard, '+352620 123 456', ['format' => 'mobile'] ],
			[ $erreur_standard, '+352601 123 456', ['format' => 'mobile'] ],
			[ $erreur_standard, '+352621 123 456', ['format' => 'fixe'] ],
			[ $erreur_standard, '+352661 123 456', ['format' => 'fixe'] ],
			[ $erreur_standard, '+352691 123 456', ['format' => 'fixe'] ],
		];
	}
	/**
	 * @dataProvider dataTelephone
	 */
	public function testTelephone($expected, $input, $options = []) {
		$actual = verifier_telephone_dist($input, $options);
		$this->assertEquals($expected, $actual);
	}
}
