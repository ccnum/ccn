<?php

// Prepare les messages d'aide de la page de configuration du plugin

if (!defined("_ECRIRE_INC_VERSION")){
	return;
}

// Noter les erreurs pour les afficher dans le panneau de config
// BUG: la modif de config se faisant apres le passage dans inc/autoriser,
// si de nouvelles erreurs apparaissent suite a une modif elles ne seront
// affichees qu'au hit suivant
include_spip('inc/autoriser');
if (empty($GLOBALS['autorite_erreurs'])){
	if (!empty($GLOBALS['meta']['autorite_erreurs'])){
		include_spip('inc/meta');
		effacer_meta('autorite_erreurs');
		spip_log('Autorite : OK', 'autorite');
	}
} else {
	if (empty($GLOBALS['meta']['autorite_erreurs'])
		or serialize($GLOBALS['autorite_erreurs'])!=$GLOBALS['meta']['autorite_erreurs']){
		include_spip('inc/meta');
		ecrire_meta('autorite_erreurs', serialize($GLOBALS['autorite_erreurs']));
		spip_log('Erreur autorite : ' . implode(', ', $GLOBALS['autorite_erreurs']), 'autorite');
	}
}

/**
 * Qui sont les webmestres ?
 * pour le squelette cfg_autorite
 * @param $void
 * @return string
 */
function liste_webmestres($void){
	$webmestres = array();
	include_spip('inc/texte');
	include_spip('base/abstract_sql');

	// on trouve l'information dans la table auteurs
	$auteurs = sql_allfetsel("*", "spip_auteurs", "webmestre='oui'");
	foreach ($auteurs as $qui){
		if (autoriser('webmestre', '', '', $qui)){
			$webmestres[$qui['id_auteur']] = typo($qui['nom']);
		}
	}

	return implode(', ', $webmestres);
}
