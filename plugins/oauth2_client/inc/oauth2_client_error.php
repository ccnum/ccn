<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Enregistre une erreur OAuth/OIDC en session
 * puis redirige vers une URL propre.
 *
 * @param string $message_key clé de message (UX)
 * @param string|null $url
 */
function oauth2_client_error_redirect(string $code, bool $invalidate = false, ?string $app = null): void {

    include_spip('inc/session');

    // suppression du token si demandé
    if ($invalidate && $app) {
        include_spip('inc/oauth2_client_token');
        oauth2_client_delete_token($app);
    }

    // journaliser l'erreur
    spip_log("OAuth2 error: " . $code . ($app ? " (app=$app)" : ""),'oauthclient.' . _LOG_ERREUR);

    // stocker l'erreur en session
    session_set('oauth2_error', $code);

    // nettoyage de l'URL de callback
    if (function_exists('oauth2_client_clean_callback_url')) {
        oauth2_client_clean_callback_url();
    }
}
/**
 * Récupère puis efface l’erreur OAuth2 stockée en session.
 *
 * Implémente un mécanisme de type "flash message" :
 *   - lit la valeur session 'oauth2_error'
 *   - la supprime immédiatement
 *   - retourne le message une seule fois
 *
 * Permet d’afficher une erreur après redirection
 * sans la conserver pour les requêtes suivantes.
 *
 * @return string|null  Code ou message d’erreur OAuth2,
 *                      null si aucune erreur
 */
function oauth2_client_get_error(): ?string {

	include_spip('inc/session');

	$error = session_get('oauth2_error');
	session_set('oauth2_error', null);

	return $error;
}
