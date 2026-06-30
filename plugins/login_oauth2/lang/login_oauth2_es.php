<?php
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

$GLOBALS[$GLOBALS['idx_lang']] = [

	// A
	'autorisation' => 'Autorización',
	'autorisations' => 'Autorizaciones',
	
	// B
	'bouton_login_avec' => 'Conectarse con @provider@',
	'bouton_supprimer' => 'Eliminar',

	// C
	'cfg_options_connexion' => 'Opciones de conexión',
	'cfg_providers' => 'Proveedores OAuth2',
	'cfg_enregistre' => 'Configuración guardada',
	'cfg_email_verifie' => 'Dirección de correo verificada',
	'cfg_activer' => 'Activar',
	'cfg_securisation' => 'Seguridad',
	'cfg_securisation_email' => 'Correos verificados',
	'cfg_forcer_oauth2' => 'Forzar OAuth2',
	
	// E
	'erreur_provider_invalide' => 'Proveedor de autenticación no válido',
	'erreur_token_absent' => 'No se pudo obtener el token de autenticación',
	'erreur_authentification' => 'Autenticación OAuth2 rechazada',
	'erreur_email_absent' => 'Su cuenta no proporciona una dirección de correo electrónico.',
	'erreur_email_non_verifie' => 'Su dirección de correo electrónico debe estar verificada para iniciar sesión.',
	'erreur_compte_introuvable' => 'Ninguna cuenta corresponde a este correo electrónico.',
	'erreur_email_multiple' => 'Existen varias cuentas con esta dirección de correo electrónico.',
	'erreur_compte_supprime' => 'Esta cuenta ha sido desactivada.',
	'erreur_inconnue' => 'Se produjo un error durante el inicio de sesión.',
	'explication_providers' => 'Elija los proveedores de identidad autorizados y configure sus parámetros técnicos',

	// L
	'log_liaison_creee' => 'Vinculación creada para @email@ mediante @provider@',
	
	// O
	'ou' => 'o',
	
	// S
	'se_connecter_avec' => 'Conectarse con',
	
	// T
	'titre_plugin' => 'Conexión OAuth2',
	'titre_page_configurer_login_oauth2' => 'Conexión OAuth2',
	'titre_liaisons' => 'Vinculaciones OAuth2',

];