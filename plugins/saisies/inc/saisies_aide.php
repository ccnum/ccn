<?php

/**
 * Gestion de l'aide des saisies
 *
 * @package SPIP\Saisies\Aide
 **/

// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Génère une page d'aide listant toutes les saisies et leurs options
 *
 * Retourne le résultat du squelette `inclure/saisies_aide` auquel
 * on a transmis toutes les saisies connues.
 *
 * @return string Code HTML
 */
function saisies_generer_aide() {
	// On a déjà la liste par saisie
	$saisies = saisies_lister_disponibles('saisies', false);

	// On construit une liste par options
	$options = [];
	$options_dev = [];
	foreach (['options_dev' => &$options_dev, 'options' => &$options] as $nom_type_options => &$type_options) {
		foreach ($saisies as $type_saisie => $saisie) {
			if (!isset($saisie[$nom_type_options])) {
				continue;
			}
			$options_saisie = saisies_lister_par_nom($saisie[$nom_type_options], false);
			if (isset($options_saisie['datas'])) {//Datas devient data
				$options_saisie['data'] = $options_saisie['datas'];
				unset($options_saisie['datas']);
			}
			foreach ($options_saisie as $nom => $option) {
				if (isset($option['options']['datas'])) {
					$option['options']['data'] = $option['options']['datas'];
					unset($option['options']['datas']);
				}
				// Si l'option n'existe pas encore
				if (!isset($type_options[$nom])) {
					$type_options[$nom] = _T_ou_typo($option['options']);
				}
				// On ajoute toujours par qui c'est utilisé
				$type_options[$nom]['utilisee_par'][] = $type_saisie;
			}
			ksort($options_saisie);
			$saisies[$type_saisie][$nom_type_options] = $options_saisie;
		}
		ksort($type_options);
	}
	return recuperer_fond(
		'inclure/saisies_aide',
		[
			'saisies' => saisies_regrouper_disponibles_par_categories($saisies),
			'options' => $options,
			'options_dev' => $options_dev
		]
	);
}
