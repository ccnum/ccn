<?php
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

$GLOBALS[$GLOBALS['idx_lang']] = [

	// A
	'autorisation' => 'Authorization',
	'autorisations' => 'Authorizations',
	
	// B
	'bouton_login_avec' => 'Sign in with @provider@',
	'bouton_supprimer' => 'Delete',

	// C
	'cfg_options_connexion' => 'Connection options',
	'cfg_providers' => 'OAuth2 providers',
	'cfg_enregistre' => 'Configuration saved',
	'cfg_email_verifie' => 'Verified email address',
	'cfg_activer' => 'Enable',
	'cfg_securisation' => 'Security',
	'cfg_securisation_email' => 'Verified emails',
	'cfg_forcer_oauth2' => 'Force OAuth2',
	
	// E
	'erreur_provider_invalide' => 'Invalid authentication provider',
	'erreur_token_absent' => 'Unable to retrieve authentication token',
	'erreur_authentification' => 'OAuth2 authentication denied',
	'erreur_email_absent' => 'Your account does not provide an email address.',
	'erreur_email_non_verifie' => 'Your email address must be verified to sign in.',
	'erreur_compte_introuvable' => 'No account matches this email.',
	'erreur_email_multiple' => 'Multiple accounts exist with this email address.',
	'erreur_compte_supprime' => 'This account has been deactivated.',
	'erreur_inconnue' => 'An error occurred during login.',
	'explication_providers' => 'Choose the authorized identity providers and configure their technical settings',

	// L
	'log_liaison_creee' => 'Link created for @email@ via @provider@',
	
	// O
	'ou' => 'or',
	
	// S
	'se_connecter_avec' => 'Sign in with',
	
	// T
	'titre_plugin' => 'OAuth2 Login',
	'titre_page_configurer_login_oauth2' => 'OAuth2 Login',
	'titre_liaisons' => 'OAuth2 Links',

];