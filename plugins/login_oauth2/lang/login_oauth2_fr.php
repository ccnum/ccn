<?php
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

$GLOBALS[$GLOBALS['idx_lang']] = [

	// A
	'autorisation' => 'Autorisation',
	'autorisations' => 'Autorisations',
	
	// B
	'bouton_login_avec' => 'Se connecter avec @provider@',
	'bouton_supprimer' => 'Supprimer',

	// C
	'cfg_options_connexion' => 'Options de connexion',
	'cfg_providers' => 'Fournisseurs OAuth2',
	'cfg_enregistre' => 'Configuration enregistrée',
	'cfg_email_verifie' => 'Adresse courriel vérifiée',
	'cfg_activer' => 'Activer',
	'cfg_securisation' => 'Sécurisation',
	'cfg_securisation_email' => 'Courriels vérifiés',
	'cfg_forcer_oauth2' => 'Forcer Oauth2',

	// E
	'erreur_provider_invalide' => 'Fournisseur d\'authentification invalide',
	'erreur_token_absent' => 'Impossible de récupérer le jeton d’authentification',
	'erreur_authentification' => 'Authentification OAuth2 refusée',
	'erreur_email_absent' => 'Votre compte ne fournit pas d’adresse email.',
	'erreur_email_non_verifie' => 'Votre adresse email doit être vérifiée pour vous connecter.',
	'erreur_compte_introuvable' => 'Aucun compte ne correspond à cet email.',
	'erreur_email_multiple' => 'Plusieurs comptes existent avec cette adresse email.',
	'erreur_compte_supprime' => 'Ce compte a été désactivé.',
	'erreur_inconnue' => 'Une erreur est survenue lors de la connexion.',
	'explication_providers' => 'Choisissez les fournisseurs d\'identité autorisés et précisez leurs paramètres techniques',

	// L
	'log_liaison_creee' => 'Liaison créée pour @email@ via @provider@',
	
	// O
	'ou' => 'ou',
	
	// S
	'se_connecter_avec' => 'Se connecter avec',
	
	// T
	'titre_plugin' => 'Connexion OAuth2',
	'titre_page_configurer_login_oauth2' => 'Connexion OAuth2',
	'titre_liaisons' => 'Liaisons OAuth2',

];
