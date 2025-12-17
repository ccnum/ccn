<?php

/* * *************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
  \************************************************************************** */

/**
 * Action pour déconnecter une personne authentifiée
 * (avec des ajouts pour le plugin CIOIDC)
 *
 * @package SPIP\Core\Authentification
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


// include the class autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use Jumbojett\OpenIDConnectClient;

include_spip('inc/cioidc_commun');
include_spip('inc/cookie');

/**
 * Se déloger
 *
 * Pour éviter les CSRF on passe par une étape de confirmation si pas de jeton fourni
 * avec un autosubmit js pour ne pas compliquer l'expérience utilisateur
 *
 * Déconnecte l'utilisateur en cours et le redirige sur l'URL indiquée par
 * l'argument de l'action sécurisée, et sinon sur la page d'accueil
 * de l'espace public.
 *
 */
function action_logout_dist() {
	$logout = _request('logout');
	$url = securiser_redirect_action(_request('url'));

//------- Debut ajout CI -----
	// cas particulier, logout dans l'espace public
//	if ($logout == 'public' and !$url) {
//		$url = url_de_base();
//	}
	// configuration OIDC

	$ci_id_serveur_auth = 0;

	// Cas avec des serveurs additionnels
	$ci_nbre_serveurs_additionnels = cioidc_nombre_serveurs_additionnels();
	if ($ci_nbre_serveurs_additionnels >= 1) {
		// authentification demandee par un clic sur le lien
		if (isset($_COOKIE['cioidc_choix']) && intval(isset($_COOKIE['cioidc_choix'])) >= 1) {
			$ci_id_serveur_auth = intval($_COOKIE['cioidc_choix']);
		}
	}

	$config_oidc = cioidc_configuration_serveur_oidc($ci_id_serveur_auth);

	$ciauthoidc = false;
	$cioidc_mode_auth = cioidc_mode_auth();
	if ($cioidc_mode_auth == 'oidc' || isset($_COOKIE['cioidc_sso'])) {
		if ($config_oidc) {
			$ciauthoidc = true;
		}
	}
//------- Fin ajout CI -----
	// seul le loge peut se deloger (mais id_auteur peut valoir 0 apres une restauration avortee)
	if (
			isset($GLOBALS['visiteur_session']['id_auteur'])
			&& is_numeric($GLOBALS['visiteur_session']['id_auteur'])
			// des sessions anonymes avec id_auteur=0 existent, mais elle n'ont pas de statut : double check
			&& isset($GLOBALS['visiteur_session']['statut'])
	) {
		// il faut un jeton pour fermer la session (eviter les CSRF)
		if (
			!($jeton = _request('jeton'))
			|| !verifier_jeton_logout($jeton, $GLOBALS['visiteur_session'])
		) {
			$jeton = generer_jeton_logout($GLOBALS['visiteur_session']);
			$action = generer_url_action('logout', "jeton=$jeton");
			$action = parametre_url($action, 'logout', _request('logout'));
			$action = parametre_url($action, 'url', _request('url'));
			include_spip('inc/minipres');
			include_spip('inc/filtres');
			$texte = bouton_action(_T('spip:icone_deconnecter'), $action);
			$texte = "<div class='boutons'>$texte</div>";
			$texte .= '<script type="text/javascript">document.write("<style>body{visibility:hidden;}</style>");window.document.forms[0].submit();</script>';
			$res = minipres(_T('spip:icone_deconnecter'), $texte, ['all_inline' => true]);
			echo $res;

			return;
		}

		include_spip('inc/auth');
		auth_trace($GLOBALS['visiteur_session'], '0000-00-00 00:00:00');
		// le logout explicite vaut destruction de toutes les sessions
		if (isset($_COOKIE['spip_session'])) {
			$session = charger_fonction('session', 'inc');
			$session($GLOBALS['visiteur_session']['id_auteur']);
			// SPIP 3.2 n'accepte pas un tableau pour les options dans spip_setcookie
			if ($GLOBALS['spip_version_branche'] >= 4.2) {
				spip_setcookie('spip_session', $_COOKIE['spip_session'], [
					'expires' => time() - 3600,
					'httponly' => true,
				]);
			} else {
				spip_setcookie('spip_session', $_COOKIE['spip_session'], time() - 3600);
			}
		}

//------- Debut ajout CI -----
//
		// si authentification http, et que la personne est loge,
		// pour se deconnecter, il faut proposer un nouveau formulaire de connexion http
		/*
		  if (
		  isset($_SERVER['PHP_AUTH_USER'])
		  and !$GLOBALS['ignore_auth_http']
		  and $GLOBALS['auth_can_disconnect']
		  ) {
		  ask_php_auth(
		  _T('login_deconnexion_ok'),
		  _T('login_verifiez_navigateur'),
		  _T('login_retour_public'),
		  'redirect=' . _DIR_RESTREINT_ABS,
		  _T('login_test_navigateur'),
		  true
		  );
		  }
		 */


		if ($ciauthoidc) {
			include_spip('inc/cioidc_commun');

			// Enlever les cookies
			if (isset($_COOKIE['cioidc_sso'])) {
				// SPIP 3.2 n'accepte pas l'option 'httponly'
				if ($GLOBALS['spip_version_branche'] >= 4.2) {
					spip_setcookie('cioidc_sso', '', [
						'expires' => time() - 3600,
						'httponly' => true,
					]);
				} else {
					spip_setcookie('cioidc_sso', '', time() - 3600);
				}
			}
			if (isset($_COOKIE['cioidc_choix'])) {
				// SPIP 3.2 n'accepte pas l'option 'httponly'
				if ($GLOBALS['spip_version_branche'] >= 4.2) {
					spip_setcookie('cioidc_choix', '', [
						'expires' => time() - 3600,
						'httponly' => true,
					]);
				} else {
					spip_setcookie('cioidc_choix', '', time() - 3600);
				}
			}

			// Determiner l'url retour
			$ci_url_retour = cioidc_url_retour($url);

			// configuration OIDC
			if ($config_oidc) {
				$oidc = new OpenIDConnectClient(
					$config_oidc['url_serveur'],
					$config_oidc['client_nom'],
					$config_oidc['client_secret']
				);

				// Well Known Config (pour éviter d'interroger à chaque fois le serveur d'authentification)
				$well_known_config = cioidc_well_known_config($ci_id_serveur_auth);
				if ($well_known_config) {
					foreach ($well_known_config as $key => $value) {
						$oidc->providerConfigParam([$key => $value]);
					}
				}

				// Ne pas utiliser client_secret_basic
				$oidc->providerConfigParam(['token_endpoint_auth_methods_supported' => []]);

				// acr
				if (isset($config_oidc['acr']) && $config_oidc['acr']) {
					$oidc->addAuthParam(['acr_values' => $config_oidc['acr']]);
				}

				// Http Proxy
				if (isset($config_oidc['http_proxy']) && $config_oidc['http_proxy'] == 'oui') {
					include_spip('inc/distant');
					$http_proxy = need_proxy($config_oidc['url_serveur']);
					if ($http_proxy) {
						$oidc->setHttpProxy($http_proxy);
					}
				}

				// logout de OpenID Connect
				if (isset($GLOBALS['visiteur_session']['cioidc_id_token'])) {
					try {
						$oidc->signOut($GLOBALS['visiteur_session']['cioidc_id_token'], $ci_url_retour);
					} catch(Exception $e){
						spip_log($e, 'cioidc_'._LOG_ERREUR);

						$ciredirect = generer_url_public('cioidc_erreur4');
						include_spip('inc/headers');
						redirige_par_entete($ciredirect);
					}
				}
			}
		}
//------- Fin ajout CI -----
	}

//------- Debut ajout CI -----
	if (!$ciauthoidc) {
//------- Fin ajout CI -----
		// Rediriger en contrant le cache navigateur (Safari3)
		include_spip('inc/headers');
//------- Debut ajout CI -----
		if ($GLOBALS['spip_version_branche'] >= 4.2) {
//------- Fin ajout CI -----
			redirige_par_entete($url
			? parametre_url($url, 'var_hasard', uniqid(random_int(0, mt_getrandmax())), '&')
			: generer_url_public('login'));
//------- Debut ajout CI -----
		} else {
			redirige_par_entete($url ? parametre_url($url, 'var_hasard', uniqid(rand()), '&') : generer_url_public('login'));
		}
//------- Fin ajout CI -----
//------- Debut ajout CI -----
	}
//------- Fin ajout CI -----
}

/**
 * Generer un jeton de logout personnel et ephemere
 *
 * @param array $session
 * @param null|string $alea
 * @return string
 */
function generer_jeton_logout($session, $alea = null) {
	if (is_null($alea)) {
		include_spip('inc/acces');
		$alea = charger_aleas();
	}

	$jeton = md5($session['date_session']
			. $session['id_auteur']
			. $session['statut']
			. $alea);

	return $jeton;
}

/**
 * Verifier que le jeton de logout est bon
 *
 * Il faut verifier avec alea_ephemere_ancien si pas bon avec alea_ephemere
 * pour gerer le cas de la rotation d'alea
 *
 * @param string $jeton
 * @param array $session
 * @return bool
 */
function verifier_jeton_logout($jeton, $session) {
	if (generer_jeton_logout($session) === $jeton) {
		return true;
	}

	if (generer_jeton_logout($session, $GLOBALS['meta']['alea_ephemere_ancien']) === $jeton) {
		return true;
	}

	return false;
}
