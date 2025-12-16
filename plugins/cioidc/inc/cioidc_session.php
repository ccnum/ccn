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

/**
 * Declenchement de l'authentification OIDC puis redirection
 */
include_spip('inc/headers');
include_spip('inc/session');
include_spip('inc/cookie');
include_spip('inc/texte');
include_spip('base/abstract_sql');

include_spip('inc/cioidc_commun');

/**
 * A ce stade, l'utilisateur a ete authentifie par le serveur OIDC
 *
 * @param string $ci_oidc_userid
 * @param string $cioidc_id_token
 * @param int $ci_id_serveur_auth
 * @param array $cioidc_tableau_pipeline
 * @return string
 */
function cioidc_session($ci_oidc_userid = '', $cioidc_id_token = '', $ci_id_serveur_auth = 0 , $cioidc_tableau_pipeline = []) {

	// redirection par defaut
	$ciredirect = generer_url_public('');

	// lire la configuration du plugin
	cioidc_lire_meta();

	if ($ci_oidc_userid) {
		$auteur = [];
		$auteur = cioidc_verifier_identifiant($ci_oidc_userid);

		if (!isset($auteur['id_auteur'])) {
			// Lire la configuration pour cette session
			$tableau_config = cioidc_lire_meta(0, false, true);

			// Existe-t-il un UID claim additionnel ?
			$cioidc_uid_claim_additionnel = cioidc_uid_claim_additionnel();
			if ($cioidc_uid_claim_additionnel
				&& isset($cioidc_tableau_pipeline['data'][$cioidc_uid_claim_additionnel])
				&& $cioidc_tableau_pipeline['data'][$cioidc_uid_claim_additionnel] != $ci_oidc_userid
			) {
				$auteur = cioidc_verifier_identifiant($cioidc_tableau_pipeline['data'][$cioidc_uid_claim_additionnel]);
			}

			if (!isset($auteur['id_auteur'])) {
				// compatibilite avec les anciennes adresses email
				if ($tableau_config['uid_champ_spip'] == 'email') {
					$ci_pos = strpos($ci_oidc_userid, '@');
					if ($ci_pos && $ci_pos > 0) {
						$ci_tableau_email = explode('@', $ci_oidc_userid);
						$ci_nom_mail = strtolower($ci_tableau_email[0]);
						$ci_domaine_mail = strtolower($ci_tableau_email[1]);

						// compatibilite avec les anciennes adresses email
						$cioidc_mail_compatible = cioidc_mail_compatible();

						foreach ($cioidc_mail_compatible as $cle => $valeur) {
							if ($ci_domaine_mail == $valeur) {
								$auteur = cioidc_verifier_identifiant($ci_nom_mail . '@' . $cle);
								if (isset($auteur['id_auteur'])) {
									break;
								}
							}
						}
					}
				}
			}
		}

		// Si l'authentification sur ce serveur OIDC a reussi mais que l'auteur n'existe pas dans SPIP
		// le creer automatiquement si le parametrage l'autorise
		if (!isset($auteur['id_auteur']) && isset($tableau_config['creer_auteur']) && $tableau_config['creer_auteur']) {
			$c = [];
			$c['login'] = '';
			$c['pass'] = '';
			$c['webmestre'] = 'non';
			$c['statut'] = $tableau_config['creer_auteur'];
			$c['source'] = 'oidc';

			if ($tableau_config['uid_champ_spip'] == '' || $tableau_config['uid_champ_spip'] == 'email') {
				$c['email'] = strtolower($ci_oidc_userid);
				$ci_tableau_cioidc_uid = explode('@', $ci_oidc_userid);
				$c['nom'] = strtolower($ci_tableau_cioidc_uid[0]);
				$c['login'] = $c['nom'];
			} else {
				$c['nom'] = $ci_oidc_userid;
				$c['login'] = $ci_oidc_userid;
			}

			// si le userinfo contient 'name' (cela peut dépendre des scopes utilisés),
			// utiliser son contenu comme 'nom' pour l'auteur dans SPIP
			if (isset($cioidc_tableau_pipeline['data']['name']) && $cioidc_tableau_pipeline['data']['name']) {
				$c['nom'] = $cioidc_tableau_pipeline['data']['name'];
			}

			// important (suite aux tests)
			$couples = $c;

			// inserer l'auteur
			$id_auteur = sql_insertq('spip_auteurs', $couples);

			// tracer le cas echeant
			if (defined('_DIR_PLUGIN_CITRACE')) {
				if ($tableau_config['uid_champ_spip'] == '' || $tableau_config['uid_champ_spip'] == 'email') {
					$commentaire = interdire_scripts(supprimer_numero($couples['nom']))
						. ' (' . interdire_scripts($couples['email']) . ')' . ' - statut:' . $couples['statut'];
				} else {
					$commentaire = interdire_scripts(supprimer_numero($couples['nom']))
						. ' (' . interdire_scripts($couples['login']) . ')' . ' - statut:' . $couples['statut'];
				}
				if ($citrace = charger_fonction('citrace', 'inc')) {
					$citrace('auteur', $id_auteur, "creation automatique de l'auteur", $commentaire);
				}
			}

			// seconde tentative
			$auteur = cioidc_verifier_identifiant($ci_oidc_userid);
		}


		if (!isset($auteur['id_auteur'])) {
			// Envoyer au pipeline
			$auteur = pipeline('cioidc_auteur', $cioidc_tableau_pipeline);
		}

		if (isset($auteur['id_auteur'])) {
			// URL cible de l'operation de connexion
			$cible = cioidc_url_cible();

			//  bloquer ici le visiteur qui tente d'abuser de ses droits
			if (isset($auteur['statut'])) {
				if (cioidc_is_url_prive($cible)) {
					if ($auteur['statut'] == '6forum') {
						$ciredirect = generer_url_public('cioidc_erreur3');
						// redirection immediate
						redirige_par_entete($ciredirect);
					}
				}
			}

			// memorise ci_id_serveur_auth a cause des redirections
			if ($ci_id_serveur_auth) {
				$auteur['cioidc_id_serveur'] = $ci_id_serveur_auth;
			}

			// memoriser le id_token
			if ($cioidc_id_token) {
				$auteur['cioidc_id_token'] = $cioidc_id_token;
			}

			// on a ete authentifie, construire la session
			// en gerant la duree demandee pour son cookie
			$session = charger_fonction('session', 'inc');
			$session($auteur);

			// Si on est connecte, envoyer vers la destination
			if ($cible) {
				$ciredirect = $cible;
			}
		} else {
			// Si l'auteur a un compte OIDC qui n'existe pas dans la base SPIP
			$ciredirect = generer_url_public('cioidc_erreur2');
		}
	} else {
		$ciredirect = generer_url_public('cioidc_erreur1');
	}


	if ($ciredirect) {
		if (isset($_COOKIE['cioidc_redirect'])) {
			// SPIP 3.2 n'accepte pas l'option 'httponly'
			if ($GLOBALS['spip_version_branche'] >= 4.2) {
				spip_setcookie('cioidc_redirect', '', [
					'expires' => time() - 3600,
					'httponly' => true,
				]);
			} else {
				spip_setcookie('cioidc_redirect', '', time() - 3600);
			}
		}

		include_spip('inc/headers');
		redirige_par_entete($ciredirect);
	}

	return $ciredirect;
}
