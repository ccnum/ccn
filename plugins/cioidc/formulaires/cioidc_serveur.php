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

include_spip('inc/actions');
include_spip('inc/editer');
include_spip('inc/cioidc_commun');

/**
 * Charger
 *
 * @param int|string $id_serveur
 * @return array
 */
function formulaires_cioidc_serveur_charger_dist($id_serveur = 0) {

	$valeurs = [];

	if ( !in_array(_request('exec'), array('configurer_cioidc', 'cioidc_serveur')) OR !test_espace_prive() ) {
		return;
	}

	$id_serveur = cioidc_serveur_id_serveur_safe($id_serveur);
	if (!$id_serveur) {
		return;
	}
	
	
	if (!autoriser('configurer', '_cioidc')) {
		return;
	}
	if (intval($id_serveur) >= 1) {
		// serveur OpenID Connect additionnel
		$valeurs = cioidc_lire_serveur_additionnel(intval($id_serveur));
	} elseif ($id_serveur == 'initial') {
		// serveur OpenID Connect
		$tableau_config = cioidc_lire_meta(0, true);
		$champs = cioidc_tableau_des_champs();
		foreach ($champs as $champ) {
			if (isset($tableau_config[$champ])) {
				$valeurs[$champ] = $tableau_config[$champ];
			}
		}
	} else {
		// nouveau serveur OpenID Connect additionnel
		$valeurs_par_defaut = cioidc_tableau_des_valeurs_par_defaut();
		$champs = cioidc_tableau_des_champs();
		foreach ($champs as $champ) {
			if (isset($valeurs_par_defaut[$champ])) {
				$valeurs[$champ] = $valeurs_par_defaut[$champ];
			}
		}
	}

	$ciedit = true;
	if (intval($id_serveur) >= 1) {
		// serveur OpenID Connect additionnel
		if (defined('_CIOIDC_SERVEURS_ADDITIONNELS')) {
			$tableau_serveurs_additionnels = _CIOIDC_SERVEURS_ADDITIONNELS;
			if (isset($tableau_serveurs_additionnels[intval($id_serveur)]['url_serveur'])) {
				if (cioidc_filtrer_url_serveur($tableau_serveurs_additionnels[intval($id_serveur)]['url_serveur'], true)) {
					$ciedit = false;
				}
			}
		}
	} elseif ($id_serveur == 'initial') {
		if (defined('_CIOIDC_URL_SERVEUR')) {
			if (cioidc_filtrer_url_serveur(_CIOIDC_URL_SERVEUR, true)) {
				$ciedit = false;
			}
		}
	}

	$valeurs['ciedit'] = $ciedit;
	$valeurs['id_serveur'] = $id_serveur;
	$valeurs['_hidden'] = "<input type='hidden' name='id_serveur' value='" . $id_serveur . "' />";

	// cas particulier du client_secret :
	// ne pas charger le client_secret dans le formulaire
	// (mettre un X à la place de chaque caractere du client secret)
	if (isset($valeurs['client_secret']) && $valeurs['client_secret']) {
		$longueur = strlen($valeurs['client_secret']);
		if ($longueur > 0) {
			$valeurs['client_secret'] = str_repeat("X", $longueur);
		}
	}
	
	return $valeurs;
}

/**
 * Verifier
 *
 * @param int|string $id_serveur
 * @return array
 */
function formulaires_cioidc_serveur_verifier_dist($id_serveur = 0) {
	$erreurs = [];

	$champs = cioidc_tableau_des_champs();
	$champs_obligatoires = cioidc_tableau_des_champs_obligatoires();
	$champs_optimisation = [
		'authorization_endpoint',
		'token_endpoint',
		'userinfo_endpoint',
		'end_session_endpoint',
		'jwks_uri',
		'token_endpoint_auth_methods_supported'
	];
	$champs_optimisation_renseignes = [];

	foreach ($champs_obligatoires as $champ_obligatoire) {
		$valeur_saisie = _request($champ_obligatoire);
		if (!$valeur_saisie) {
			$erreurs[$champ_obligatoire] = _T('info_obligatoire');
		}
	}

	foreach ($champs as $champ) {
		$valeur_saisie = _request($champ);
		if ($valeur_saisie && !cioidc_verifier($champ, $valeur_saisie)) {
			$erreurs[$champ] = _T('cioidc:erreur_incorrect');
		}
		if ($valeur_saisie && in_array($champ,$champs_optimisation)) {
			$champs_optimisation_renseignes[] = $champ;
		}
	}

	// partie optimisation partiellement renseignée
	if ($champs_optimisation_renseignes && (count($champs_optimisation_renseignes) != count($champs_optimisation))) {
		$diff = array_diff($champs_optimisation, $champs_optimisation_renseignes);
		foreach ($diff as $champ) {
			$erreurs[$champ] = _T('cioidc:erreur_incorrect');
		}
	}
	
	return $erreurs;
}

/**
 * Traiter
 *
 * @param int|string $id_serveur
 * @return array
 */
function formulaires_cioidc_serveur_traiter_dist($id_serveur = 0) {
	$res = [];
	$c = [];
	$redirect = '';
	$id_serveur = cioidc_serveur_id_serveur_safe($id_serveur);

	// cas d'un nouveau serveur additionnel
	if ($id_serveur == 'nouveau') {
		$id_serveur = cioidc_nombre_serveurs_additionnels() + 1;
	}

	$champs = cioidc_tableau_des_champs();
	foreach ($champs as $champ) {
		$c[$champ] = _request($champ);
	}

	// cas particulier des scopes
	// enlever les espaces
	// enlever la valeur 'openid'
	$champ = 'scopes';
	if (isset($c[$champ]) && $c[$champ]) {
		$tableau_cible = [];
		$tableau_scopes = explode(',', $c[$champ]);

		foreach ($tableau_scopes as $scope) {
			$scope = str_replace(["'", '"'], '', $scope);
			if ($scope != 'openid') {
				$tableau_cible[] = $scope;
			}
		}

		if ($tableau_cible) {
			$c[$champ] = implode(',', $tableau_cible);
		} else {
			$c[$champ] = '';
		}
	}

	// cas particulier de client_secret :
	// si la valeur saisie contient que des X, alors conserver la valeur actuelle en BDD
	$champ = 'client_secret';
	if (isset($c[$champ]) && $c[$champ]) {
		$copie_client_secret = $c[$champ];
		if (substr_count($copie_client_secret, 'X') == strlen($c[$champ])) {
			if (intval($id_serveur) >= 1) {
				// serveur OpenID Connect additionnel
				$valeurs = cioidc_lire_serveur_additionnel(intval($id_serveur));
			} elseif ($id_serveur == 'initial') {
				// serveur OpenID Connect
				$valeurs = cioidc_lire_meta(0, true);
			}
			if (isset($valeurs[$champ]) && $valeurs[$champ]) {
				$c[$champ] = $valeurs[$champ];
			} else {
				$c[$champ] = '';
			}
		}
	}
	

	// ne pas enregistrer si configuration par fichier
	$ciedit = true;
	if (defined('_CIOIDC_URL_SERVEUR')) {
		if (cioidc_filtrer_url_serveur(_CIOIDC_URL_SERVEUR, true)) {
			$ciedit = false;
		}
	}

	if ($ciedit) {
		if (intval($id_serveur) >= 1) {
			// serveur OpenID Connect additionnel
			$redirect = generer_url_ecrire('cioidc_serveurs');
			cioidc_ecrire_serveur_additionnel($id_serveur, $c);
		} elseif ($id_serveur == 'initial') {
			// serveur OpenID Connect
			$tableau_config = cioidc_lire_meta(0, true);
			$redirect = generer_url_ecrire('configurer_cioidc');
			foreach ($champs as $champ) {
				$tableau_config[$champ] = $c[$champ];
			}
			include_spip('inc/meta');
			ecrire_meta('cioidc', @serialize($tableau_config));
		}
	}

	$res['message_ok'] = _T('config_info_enregistree');
	if ($redirect) {
		$res['redirect'] = $redirect;
	}
	
	return $res;
}

function cioidc_serveur_id_serveur_safe($id_serveur) {
	$id_serveur_safe = '';

	if (intval($id_serveur)>=1) {
		$id_serveur_safe = intval($id_serveur);
	} elseif ($id_serveur == 'initial') {
		$id_serveur_safe = $id_serveur;
	} elseif ($id_serveur == 'nouveau') {
		$id_serveur_safe = $id_serveur;
	}

	return $id_serveur_safe;
}
