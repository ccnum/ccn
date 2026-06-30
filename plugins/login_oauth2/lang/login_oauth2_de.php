<?php
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

$GLOBALS[$GLOBALS['idx_lang']] = [

	// A
	'autorisation' => 'Autorisierung',
	'autorisations' => 'Autorisierungen',
	
	// B
	'bouton_login_avec' => 'Anmelden mit @provider@',
	'bouton_supprimer' => 'Löschen',

	// C
	'cfg_options_connexion' => 'Verbindungsoptionen',
	'cfg_providers' => 'OAuth2-Anbieter',
	'cfg_enregistre' => 'Konfiguration gespeichert',
	'cfg_email_verifie' => 'Bestätigte E-Mail-Adresse',
	'cfg_activer' => 'Aktivieren',
	'cfg_securisation' => 'Sicherheit',
	'cfg_securisation_email' => 'Bestätigte E-Mails',
	'cfg_forcer_oauth2' => 'OAuth2 erzwingen',

	// E
	'erreur_provider_invalide' => 'Ungültiger Authentifizierungsanbieter',
	'erreur_token_absent' => 'Authentifizierungstoken konnte nicht abgerufen werden',
	'erreur_authentification' => 'OAuth2-Authentifizierung abgelehnt',
	'erreur_email_absent' => 'Ihr Konto stellt keine E-Mail-Adresse bereit.',
	'erreur_email_non_verifie' => 'Ihre E-Mail-Adresse muss bestätigt sein, um sich anzumelden.',
	'erreur_compte_introuvable' => 'Kein Konto entspricht dieser E-Mail-Adresse.',
	'erreur_email_multiple' => 'Mehrere Konten existieren mit dieser E-Mail-Adresse.',
	'erreur_compte_supprime' => 'Dieses Konto wurde deaktiviert.',
	'erreur_inconnue' => 'Beim Anmelden ist ein Fehler aufgetreten.',
	'explication_providers' => 'Wählen Sie die autorisierten Identitätsanbieter und konfigurieren Sie deren technische Parameter',

	// L
	'log_liaison_creee' => 'Verknüpfung für @email@ über @provider@ erstellt',
	
	// O
	'ou' => 'oder',
	
	// S
	'se_connecter_avec' => 'Anmelden mit',
	
	// T
	'titre_plugin' => 'OAuth2-Anmeldung',
	'titre_page_configurer_login_oauth2' => 'OAuth2-Anmeldung',
	'titre_liaisons' => 'OAuth2-Verknüpfungen',

];