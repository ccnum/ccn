<?php

namespace Spip\Verifier\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @covers verifier_entier_dist()
**/
class EntierTest extends TestCase {
	public static function setUpBeforeClass(): void {
		require_once dirname(__DIR__) . '/verifier/entier.php';
	}

	public static function dataEntier() {
		return [
			'entier' => [
				// Expected
				'',
				// Valeur
				'12'
				//
			],
			'flottant' => [
				// Expected
				_T('verifier:erreur_entier'),
				// Valeur
				'10.33'
				//
			],
			'chaine' => [
				// Expected
				_T('verifier:erreur_entier'),
				// Valeur
				'ceci_n_est_pas_un_entier'
				//
			],
			'min_ok' => [
				// Expected
				'',
				// Valeur
				'12',
				// Options
				[
					'min' => '10'
				]
			],
			'min_pasok' => [
				// Expected
				_T('verifier:erreur_entier_min', ['min' => '10']),
				// Valeur
				'8',
				// Options
				[
					'min' => '10'
				]
			],
			'max_ok' => [
				// Expected
				'',
				// Valeur
				'12',
				// Options
				[
					'max' => '20'
				]
			],
			'max_pasok' => [
				// Expected
				_T('verifier:erreur_entier_max', ['max' => '20']),
				// Valeur
				'28',
				// Options
				[
					'max' => '20'
				]
			],
			'min_max_ok' => [
				// Expected
				'',
				// Valeur
				'12',
				// Options
				[
					'min' => '10',
					'max' => '20'
				]
			],
			'min_max_pasok' => [
				// Expected
				_T('verifier:erreur_entier_entre', ['min' => '10', 'max' => '20']),
				// Valeur
				'28',
				// Options
				[
					'min' => '10',
					'max' => '20'
				]
			]
		];
	}

	/**
	 * @dataProvider dataEntier
	**/
	function testEntier($expected, $valeur, $options = []) {
		$actual = verifier_entier_dist($valeur, $options);
		$this->assertEquals($expected, $actual);
	}
}
