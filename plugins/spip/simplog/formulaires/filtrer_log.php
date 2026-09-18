<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Chargement des données : le formulaire propose de filtrer le log sur les gravités.
 *
 * @param string $niveau      Liste des gravités à afficher, séparées par une virgule.
 * @param array  $niveaux_log Liste des gravités incluses dans le fichier de log affiché.
 *
 * @return array Tableau des données à charger par le formulaire.
 *               - `niveau`       : (saisie) liste des gravités à afficher
 *               - `_niveaux_log` : (affichage) liste des gravités incluses dans le log
 */
function formulaires_filtrer_log_charger_dist(string $niveau, array $niveaux_log) : array {
	$valeurs = [];

	// On transmet la liste des niveaux de gravite utilisables pour le log
	$valeurs['_niveaux_log'] = $niveaux_log;

	// On met la liste des gravites à filtrer en tableau
	// - si la liste est vide, c'est qu'on veut voir tous les logs, on sélectionne donc tous les niveaux possibles
	if ($niveau) {
		$valeurs['niveau'] = explode(',', $niveau);
	} else {
		$valeurs['niveau'] = $niveaux_log;
	}

	return $valeurs;
}

/**
 * Vérification des saisies : il faut toujours saisir au moins une gravité.
 *
 * @param string $niveau      Liste des gravités à afficher, séparées par une virgule.
 * @param array  $niveaux_log Liste des gravités incluses dans le fichier de log affiché.
 *
 * @return array Tableau des erreurs éventuelles
 */
function formulaires_filtrer_log_verifier_dist(string $niveau, array $niveaux_log) : array {
	$erreurs = [];

	// Vérifier qu'on a saisi au moins une gravité
	if (!_request('niveau')) {
		$erreurs['niveau'] = _T('info_obligatoire');
	}

	return $erreurs;
}

/**
 * Exécution du formulaire : la liste des gravités compilées est fournie en paramètre de la page.
 *
 * @param string $niveau      Liste des gravités à afficher, séparées par une virgule.
 * @param array  $niveaux_log Liste des gravités incluses dans le fichier de log affiché.
 *
 * @return array Tableau retourné par le formulaire contenant essentiellement l'url avec les niveaux de gravité.
 */
function formulaires_filtrer_log_traiter_dist(string $niveau, array $niveaux_log) : array {
	// On renvoie sur la page en cours en ajoutant dans l'url la liste des niveaux demandés
	$retour = ['editable' => true];
	$retour['redirect'] = parametre_url(self(), 'niveau', implode(',', _request('niveau')));

	return $retour;
}
