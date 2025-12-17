<?php

/**
 * Plugin CIOIDC
 * @copyright 2024 MTECT
 * @author Christophe IMBERTI (cf. CPI art L121-1)
 * @license GNU/GPLv3
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/cioidc_commun');

/**
 * Charger
 *
 * @return array
 */
function formulaires_cioidc_mode_auth_charger_dist() {

	if (!autoriser('configurer', '_cioidc')) {
		return false;
	}
	$valeurs = [];
	$ciedit = true;
	$ciedit_mode_auth = true;
	$ciedit_titre_se_connecter = true;
	$ciedit_nom_serveur_spip = true;

	$tableau_config = cioidc_lire_meta();
	$valeurs['mode_auth'] = $tableau_config['mode_auth'];
	$valeurs['titre_se_connecter'] = $tableau_config['titre_se_connecter'];
	$valeurs['nom_serveur_spip'] = $tableau_config['nom_serveur_spip'];

	if (defined('_CIOIDC_TITRE_SE_CONNECTER')) {
		if (cioidc_filtrer_titre_se_connecter(_CIOIDC_TITRE_SE_CONNECTER, true)) {
			$ciedit_titre_se_connecter = false;
		}
	}
	if (defined('_CIOIDC_NOM_SERVEUR_SPIP')) {
		if (cioidc_filtrer_nom_serveur_spip(_CIOIDC_NOM_SERVEUR_SPIP, true)) {
			$ciedit_nom_serveur_spip = false;
		}
	}

	if (defined('_CIOIDC_MODE_AUTH')) {
		if (in_array(_CIOIDC_MODE_AUTH, ['oidc', 'hybride', 'spip'])) {
			$ciedit = false;
			$ciedit_mode_auth = false;
			$ciedit_titre_se_connecter = false;
			$ciedit_nom_serveur_spip = false;
		}
	}

	$valeurs['ciedit'] = $ciedit;
	$valeurs['ciedit_mode_auth'] = $ciedit_mode_auth;
	$valeurs['ciedit_titre_se_connecter'] = $ciedit_titre_se_connecter;
	$valeurs['ciedit_nom_serveur_spip'] = $ciedit_nom_serveur_spip;

	return $valeurs;
}

/**
 * Verifier
 *
 * @return array
 */
function formulaires_cioidc_mode_auth_verifier_dist() {
	$erreurs = [];

	$valeur_saisie = _request('mode_auth');
	if (!$valeur_saisie) {
		$erreurs['mode_auth'] = _T('info_obligatoire');
	}
	if ($valeur_saisie && !in_array($valeur_saisie, ['oidc', 'hybride', 'spip'])) {
		$erreurs['mode_auth'] = _T('cioidc:valeur_incorrecte');
	}

	$valeur_saisie = _request('titre_se_connecter');
	if (!$valeur_saisie) {
		$erreurs['titre_se_connecter'] = _T('info_obligatoire');
	}
	if ($valeur_saisie && !cioidc_filtrer_titre_se_connecter($valeur_saisie, true)) {
		$erreurs['titre_se_connecter'] = _T('cioidc:erreur_incorrect');
	}

	$valeur_saisie = _request('nom_serveur_spip');
	if (!$valeur_saisie) {
		$erreurs['nom_serveur_spip'] = _T('info_obligatoire');
	}
	if ($valeur_saisie && !cioidc_filtrer_nom_serveur_spip($valeur_saisie, true)) {
		$erreurs['nom_serveur_spip'] = _T('cioidc:erreur_incorrect');
	}

	return $erreurs;
}

/**
 * Traiter
 *
 * @return array
 */
function formulaires_cioidc_mode_auth_traiter_dist() {
	$res = [];

	$tableau_config = cioidc_lire_meta();

	// ne pas enregistrer si configuration par fichier
	$ciedit = true;
	if (defined('_CIOIDC_MODE_AUTH')) {
		if (in_array(_CIOIDC_MODE_AUTH, ['oidc', 'hybride', 'spip'])) {
			$ciedit = false;
		}
	}

	if ($ciedit) {
		$enregistrer = false;

		$valeur_saisie = _request('mode_auth');
		if ($valeur_saisie && in_array($valeur_saisie, ['oidc', 'hybride', 'spip'])) {
			if ($tableau_config['mode_auth'] != $valeur_saisie) {
				$tableau_config['mode_auth'] = $valeur_saisie;
				$enregistrer = true;
			}
		}
		$valeur_saisie = _request('titre_se_connecter');
		if ($valeur_saisie && cioidc_filtrer_titre_se_connecter($valeur_saisie, true)) {
			if ($tableau_config['titre_se_connecter'] != $valeur_saisie) {
				$tableau_config['titre_se_connecter'] = $valeur_saisie;
				$enregistrer = true;
			}
		}
		$valeur_saisie = _request('nom_serveur_spip');
		if ($valeur_saisie && cioidc_filtrer_nom_serveur_spip($valeur_saisie, true)) {
			if ($tableau_config['nom_serveur_spip'] != $valeur_saisie) {
				$tableau_config['nom_serveur_spip'] = $valeur_saisie;
				$enregistrer = true;
			}
		}

		if ($enregistrer) {
			include_spip('inc/meta');
			ecrire_meta('cioidc', @serialize($tableau_config));
		}
	}

	$res['message_ok'] = _T('config_info_enregistree');
	$res['redirect'] = '';

	return $res;
}
