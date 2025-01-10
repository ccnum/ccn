<?php

namespace Spip\Verifier\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @covers verifier_date_dist()
 * @covers normaliser_date_datetime_dist()
 * @covers normaliser_date_date_dist()
 * @covers normaliser_date_date_ou_datetime_dist()
 * @uses verifier_date_format_spip2php()
 * @internal
 */

class DateTest extends TestCase {
	public static function setUpBeforeClass(): void {
		require_once dirname(__DIR__) . '/verifier/date.php';
	}

	public static function dataVerifierDate() {

		$erreur_standard = _T('verifier:erreur_date_format');
		$erreur_date_inexistante = _T('verifier:erreur_date');
		return [
			// Dates seules
			//ajm
			[$erreur_date_inexistante,'2021/22/03',['format' => 'amj']],// Pas de 22ème mois!
			[$erreur_standard, '03/03/2021',['format' => 'amj']],// Une date mal formatée
			['','2021/03/22',['format' => 'amj']],
			//jma
			[$erreur_date_inexistante,'03/22/2021',['format' => 'jma']],// Pas de 22ème mois!
			['','22/03/2021',['format' => 'jma']],
			//mja
			[$erreur_date_inexistante,'22/03/2021',['format' => 'mja']],// Pas de 22ème mois!
			['','03/22/2021',['format' => 'mja']],
			// Deja formaté en mysql
			['','2021-03-22 12:11:10',['format' => 'mja', 'normaliser' => 'datetime']],
			// Avec les heures
			//Amj
			['',['date' => '2021/03/22', 'heure' => '20:00'],['format' => 'amj']],
			[_T('verifier:erreur_heure'),['date' => '2021/03/22', 'heure' => '20:68'],['format' => 'amj']],//Pas de 68'eme minutes
			[_T('verifier:erreur_heure'),['date' => '2021/03/22', 'heure' => '25:01'],['format' => 'amj']],//Pas de 25ème heure
		];
	}

	/**
	 * @dataProvider dataVerifierDate
	 */
	public function testVerifierDate($expected, $input, $options = []) {
		$actual = verifier_date_dist($input, $options);
		$this->assertEquals($expected, $actual);
	}

	public static function dataNormaliserDateDatetime() {
	return [

		// Dates seules
		//ajm
		['2021-03-22 00:00:00','2021/03/22',['format' => 'amj'], ''],
		//jma
		['2021-03-22 00:00:00','22/03/2021',['format' => 'jma'], ''],
		//mja
		['2021-03-22 00:00:00','03/22/2021',['format' => 'mja'], ''],

		// Avec les heures
		//Amj
		['2021-03-22 20:00:00','2021/03/22', ['heure' => '20:00', 'format' => 'amj'], ''],
	];

	}
	/**
	 * @dataProvider dataNormaliserDateDatetime
	 */
	public function testNormaliserDateDatetime($expected, $input, $options = []) {
		$erreurs = '';
		$actual = normaliser_date_datetime_dist($input, $options, $erreurs);
		$this->assertEquals($expected, $actual);
	}


	public static function dataNormaliserDateDate() {
	return [

		// Dates seules
		//ajm
		['2021-03-22','2021/03/22',['format' => 'amj'], ''],
		//jma
		['2021-03-22','22/03/2021',['format' => 'jma'], ''],
		//mja
		['2021-03-22','03/22/2021',['format' => 'mja'], ''],

	];

	}
	/**
	 * @dataProvider dataNormaliserDateDate
	 */
	public function testNormaliserDateDate($expected, $input, $options = []) {
		$erreurs = '';
		$actual = normaliser_date_date_dist($input, $options, $erreurs);
		$this->assertEquals($expected, $actual);
	}


	public static function dataNormaliserDateDateOuDatetime() {
	return [

		// En mode SQL
		'sql_datetime' => [
			// Expected
			'2021-03-22 20:00:00',
			// Input
			'2021/03/22',
			// Options
			[
				'heure' => '20:00',
				'format' => 'amj',
				'_saisie' => [
					'options' => [
						'sql' => 'datetime DEFAULT \'0000-00-00 00:00:00\' NOT NULL'
					]
				]
			]
		],
		'sql_date' => [
			// Expected
			'2021-03-22',
			// Input
			'2021/03/22 ',
			// Options
			[
				'heure' => '20:00',
				'format' => 'amj',
				'_saisie' => [
					'options' => [
						'sql' => 'date DEFAULT \'0000-00-00\' NOT NULL'
					]
				]
			]
		],
		// En mode "config"
		// Dates seules
		//ajm
		'config_date' => ['2021-03-22','2021/03/22',['format' => 'amj'], ''],
		//jma

		// Avec les heures
		//Amj
		'config_datetime' => ['2021-03-22 20:00:00','2021/03/22', ['heure' => '20:00', 'format' => 'amj'], ''],
	];

	}


	/**
	 * @dataProvider dataNormaliserDateDateOuDatetime
	 */
	public function testNormaliserDateDateOuDatetime($expected, $input, $options = []) {
		$erreurs = '';
		$actual = normaliser_date_date_ou_datetime_dist($input, $options, $erreurs);
		$this->assertEquals($expected, $actual);
	}
}
