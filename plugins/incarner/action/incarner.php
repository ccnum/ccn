<?php

function action_incarner_dist() {
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	$arg = explode(':', $arg, 2);
	$action = array_shift($arg);

	include_spip('inc/headers');
	include_spip('incarner_fonctions');
	include_spip('incarner_pipelines');
	include_spip('inc/auth');
	include_spip('inc/cookie');

	switch ($action) {
		// verifier qu'on a envoye un login licite
		case 'login':
			if ($login = array_shift($arg)) {
				// que l'on sait identifier via auth/incarner
				if ($auteur = auth_identifier_login($login, '')
					and is_array($auteur)) {
					if (autoriser('incarner', 'auteur', $auteur['id_auteur'])) {

						// tout est bon, logeons l'incarnation et attribuons une cle
						$id_auteur_session = ($GLOBALS['visiteur_session']['id_auteur'] ?? 0);
						spip_log(
							"DEBUT Incarnation compte auteur $login #" . $auteur['id_auteur'] . " par #$id_auteur_session",
							'incarner' . _LOG_INFO_IMPORTANTE
						);

						// envoyer un mail de securite si configure
						incarner_notifier_nouvelle_incarnation($auteur);

						incarner_renouveler_cle($id_auteur_session, $auteur['id_auteur']);

						// et on loge le nouvel auteur
						auth_loger($auteur);

						/* Si on vient de se loger dans l'espace privé avec un login qui n'y est
						 * pas autorisé, on redirige vers la page d'accueil, pour éviter un
						 * message d'erreur inutile. */
						if (test_espace_prive() and (!autoriser('ecrire'))) {
							redirige_par_entete(url_de_base());
						}

						pipeline('trig_incarner_redirection', [
							'args' => [
								'action' => $action,
								'auteur' => $auteur,
							],
						]);
						return;
					}

				}
			}
			break;
		case 'rollback':
		case 'logout':
			if ($id_auteur = incarner_racine_incarnation()) {

				include_spip('base/abstract_sql');
				$login = sql_getfetsel('login', 'spip_auteurs', 'id_auteur=' . intval($id_auteur));
				if ($auteur = auth_identifier_login($login, '')
					and is_array($auteur)) {

					$id_auteur_session = ($GLOBALS['visiteur_session']['id_auteur'] ?? 0);
					spip_log(
						"FIN Incarnation compte auteur #$id_auteur_session par $login #$id_auteur",
						'incarner' . _LOG_INFO_IMPORTANTE
					);

					// d'abord deloger l'auteur incarne
					auth_trace($GLOBALS['visiteur_session'], '0000-00-00 00:00:00');
					// destruction des sessions
					if (isset($_COOKIE['spip_session'])) {
						$session = charger_fonction('session', 'inc');
						$session($GLOBALS['visiteur_session']['id_auteur']);
						spip_setcookie('spip_session', $_COOKIE['spip_session'], time() - 3600);
					}

					// puis reloger l'auteur d'origine (pour finir sur le bon cookie d'admin si besoin)
					auth_loger($auteur);
					incarner_invalider_cle();

					if ($action === 'logout') {
						auth_trace($GLOBALS['visiteur_session'], '0000-00-00 00:00:00');
						// destruction des sessions
						if (isset($_COOKIE['spip_session'])) {
							$session = charger_fonction('session', 'inc');
							$session($GLOBALS['visiteur_session']['id_auteur']);
							spip_setcookie('spip_session', $_COOKIE['spip_session'], time() - 3600);
						}

						if (test_espace_prive()) {
							$GLOBALS['redirect'] = url_de_base();
						}
					}
					pipeline('trig_incarner_redirection', [
						'args' => [
							'action' => $action,
							'auteur' => $auteur,
						],
					]);
					return;
				}
			}
			break;
	}

	http_response_code(403);
	incarner_invalider_cle();
	include_spip('inc/minipres');
	echo minipres();
	exit();
}
