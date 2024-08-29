<?php

// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function formulaires_saisies_cvt_saisies_dist() {
	include_spip('inc/saisies');

	$saisies = [
		'options' => [
			'texte_submit' => 'Pouet !',
			#'etapes_activer' => true,
			'etapes_suivant' => 'Suivant pouet',
			'etapes_precedent' => 'Précédent pouet',
			'etapes_navigation' => 'on',
			'etapes_presentation' => 'courante',
			'etapes_precedent_suivant_titrer' => 'true'
		],
		[
			'saisie' => 'fieldset',
			'options' => [
				'nom' => 'persos',
				'label' => 'Informations personnelles',
			],
			'saisies' => [
				[
					'saisie' => 'input',
					'options' => [
						'nom' => 'tableau[cle][nom]',
						'label' => 'Nom'
					]
				],
				[
					'saisie' => 'input',
					'options' => [
						'nom' => 'tableau[cle][email]',
						'obligatoire' => 'oui',
						'label' => 'E-mail - declaration de nom en syntaxe html'
					],
					'verifier' => [
						'type' => 'email'
					]
				],
				[
					'saisie' => 'input',
					'options' => [
						'nom' => 'tableau/cle/email2',
						'obligatoire' => 'oui',
						'label' => 'E-mail - déclaration de nom en syntaxe SPIP'
					],
					'verifier' => [
						'type' => 'email'
					]
				],
				[
					'saisie' => 'input',
					'options' => [
						'nom' => 'a_supprimer',
						'label' => 'Un champ à supprimer'
					]
				],
			],
		],
		[
			'saisie' => 'case',
			'options' => [
				'nom' => 'out',
				'label_case' => 'Un champ à l’extérieur des groupes'
			],
		],
		[
			'saisie' => 'radio',
			'options' => [
				'nom' => 'radio avec disable (hors groupe)',
				'data' => [
					'a' => 'a',
					'b' => 'b',
					'c' => 'c',
					'd' => 'd'
				],
				'disable_choix' => ['b', 'c']
			]
		],
		[
			'saisie' => 'fieldset',
			'options' => [
				'nom' => 'aumilieu',
				'label' => 'une étape au milieu'
			],
			'saisies' => [
				[
					'saisie' => 'case',
					'options' => [
						'label' => 'Une case au milieu',
						'nom' => 'case_milieu',
					]
				]
			]
		],
		[
			'saisie' => 'fieldset',
			'options' => [
				'nom' => 'ecrire',
				'label' => 'Des choses à dire',
				'icone' => 'saisies-xx.svg',
				'taille_icone' => '24'
			],
			'saisies' => [
				[
					'saisie' => 'input',
					'options' => [
						'nom' => 'sujet',
						'label' => 'Sujet'
					]
				],
				[
					'saisie' => 'textarea',
					'options' => [
						'nom' => 'message',
						'obligatoire' => 'oui',
						'label' => 'Un message',
						'conteneur_class' => 'pleine_largeur',
					],
					'verifier' => [
						[
							'type' => 'taille',
							'options' => ['min' => 10]
						],
						[
							'type' => 'slug',
						],
					]
				],
			],
		],
	];

	$chemin = saisies_chercher($saisies, 'a_supprimer', true);
	$saisies = saisies_supprimer($saisies, $chemin);
	$saisies = saisies_dupliquer($saisies, 'message');
	$saisies = saisies_deplacer($saisies, 'tableau[cle][email]', 'tableau[cle][nom]');
	//var_dump($saisies);

	return $saisies;
}

function formulaires_saisies_cvt_charger() {
	$contexte = [
		'saisies_texte_submit' => 'Prout !',
	];

	return $contexte;
}
