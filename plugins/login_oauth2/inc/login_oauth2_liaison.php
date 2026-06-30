<?php
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Tente de connecter un utilisateur OAuth2
 */
function login_oauth2_connecter_utilisateur($user) {
	spip_log('login_oauth2_connecter_utilisateur()','login_oauth.'._LOG_DEBUG);
	spip_log($user,'login_oauth.'._LOG_DEBUG);

	$provider = $user['provider'] ?? null;
	$subject  = $user['subject'] ?? null;
	$email	= isset($user['email']) ? trim(strtolower($user['email'])) : null;
	$email_verified = $user['email_verified'] ?? false;

	// Sécurité minimale
	if (!$provider || !$subject) {
		login_oauth2_rediriger_erreur('erreur_provider_invalide');
	}
	
	// Récupération du contexte du token (autorization, refresh...)
	$context = $user['oauth2_context'] ?? [];
	$grant_type = $context['grant_type'] ?? null;

	// Recherche liaison existante
	$liaison = sql_fetsel(
		'*',
		'spip_auteurs_oauth2',
		[
			'provider=' . sql_quote($provider),
			'subject=' . sql_quote($subject)
		]
	);

	// Identification de l'auteur SPIP
	if ($liaison && !empty($liaison['id_auteur'])) {
		$id_auteur = intval($liaison['id_auteur']);
		$statut = sql_getfetsel(
			'statut',
			'spip_auteurs',
			'id_auteur=' . $id_auteur
		);

	// Auteur SPIP "à la poubelle"
	if ($statut === '5poubelle') {
		spip_log("Connexion OAuth refusée : auteur en poubelle id_auteur=".$id_auteur,'login_oauth.'._LOG_WARNING);
		login_oauth2_rediriger_erreur('erreur_compte_supprime');
		}

	// Mise à jour de la liaison uniquement si une nouvelle autorisation est donnée
	if(in_array($grant_type, ['authorization_code', 'refresh_token'])){
			sql_updateq(
			'spip_auteurs_oauth2',
			[
				'email'            => $email,
				'date_liaison'     => date('Y-m-d H:i:s'),
				'grant_type' => $grant_type
			],
			[
				'provider=' . sql_quote($provider),
				'subject=' . sql_quote($subject)		]
		);
		spip_log("Mise à jour liaison id_auteur=$id_auteur provider=$provider",'login_oauth.'._LOG_INFO);

	}
	spip_log("Connexion OAuth id_auteur=$id_auteur provider=$provider",'login_oauth.'._LOG_INFO);

	return login_oauth2_finaliser_connexion($id_auteur);
	}

	// Email obligatoire
	if (!$email) {
		login_oauth2_rediriger_erreur('erreur_email_absent');
		}

	// Vérification email confirmé (option)
	$exiger_email_verifie = (lire_config('login_oauth2/exiger_email_verifie', 0) == 1);

	if ($exiger_email_verifie && !$email_verified) {
		login_oauth2_rediriger_erreur('erreur_email_non_verifie');
		}

	// Recherche auteur par email
	$auteurs = sql_allfetsel(
		'id_auteur',
		'spip_auteurs',
		[
			'email=' . sql_quote($email),
			"statut NOT IN ('5poubelle','nouveau')"
		]
	);

	// Aucun auteur
	if (!$auteurs) {
		login_oauth2_rediriger_erreur('erreur_auteur_introuvable');
		}

	// Plusieurs auteurs alors refus
	if (count($auteurs) > 1) {
		spip_log("Connexion OAuth refusée : email multiple ($email)",'login_oauth.'._LOG_WARNING);
		login_oauth2_rediriger_erreur('erreur_email_multiple');
		}

	$id_auteur = intval($auteurs[0]['id_auteur']);

	// Création liaison OAuth si une nouvelle autorisation est donnée (normalement ici toujours...)
	if(in_array($grant_type, ['authorization_code', 'refresh_token'])){
		sql_insertq(
			'spip_auteurs_oauth2',
			[
				'id_auteur'	=> $id_auteur,
				'provider'	 => $provider,
				'subject'	  => $subject,
				'email'		=> $email,
				'date_liaison' => date('Y-m-d H:i:s'),
				'grant_type' => $grant_type
			]
		);
	}

	spip_log(_T('login_oauth2:log_liaison_creee',['email' => $email,'provider' => $provider]),'login_oauth.'._LOG_INFO);
	return login_oauth2_finaliser_connexion($id_auteur);
}

/**
 * Finalise la connexion SPIP
 */
function login_oauth2_finaliser_connexion($id_auteur) {

	if (!$id_auteur) {
		return false;
	}

	include_spip('inc/auth');
	include_spip('inc/session');

	// Charger l'auteur complet
	$auteur = sql_fetsel(
		'*',
		'spip_auteurs',
		'id_auteur=' . intval($id_auteur)
	);

	if (!$auteur) {
		return false;
	}

	// Connexion SPIP
	auth_loger($auteur);

	// Sécurité : forcer la session
	session_set('id_auteur', $id_auteur);

	// Redirection
	include_spip('inc/headers');
	$redirect = _request('redirect') ?: generer_url_ecrire();
	redirige_par_entete($redirect);
	exit;
}