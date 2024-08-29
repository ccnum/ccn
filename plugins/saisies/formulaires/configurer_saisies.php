<?php

// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Saisies du formulaire de configuration de saisies
 * @return array
**/
function formulaires_configurer_saisies_saisies_dist() {
	return [
		[
			'saisie' => 'case',
			'options' => [
				'nom' => 'assets_global',
				'label_case' => '<:saisies:assets_global:>',
				'conteneur_class' => 'pleine_largeur',
			]
		]
	];
}
