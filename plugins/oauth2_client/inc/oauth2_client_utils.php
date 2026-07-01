<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Construit une chaîne de scope OAuth2 valide.
 *
 * Accepte un scope sous forme :
 *   - de tableau ['openid','email']
 *   - de chaîne 'openid email'
 *
 * Retourne toujours une chaîne séparée par espace,
 * conformément à RFC 6749.
 *
 * @param mixed $scope  Scope OAuth2 (array|string|null)
 *
 * @return string|null  Scope normalisé ou null si vide
 */
function oauth2_client_build_scope($scope) {

	if (empty($scope)) {
		return null;
	}

	if (is_array($scope)) {
		return implode(' ', $scope);
	}

	return (string) $scope;
}


/**
 * Décode une chaîne JSON avec gestion d’erreur.
 *
 * En cas d’erreur JSON, loggue l’erreur et retourne false.
 *
 * @param string $json  Chaîne JSON à décoder
 *
 * @return array|false  Tableau associatif ou false si erreur
 */
function oauth2_client_json_decode($json) {

	if (!is_string($json) || $json === '') {
		return false;
	}
	$data = json_decode($json, true);

	if (json_last_error() !== JSON_ERROR_NONE) {
		spip_log("OAuth2 JSON: erreur ". json_last_error_msg(), 'oauthclient' . _LOG_DEBUG);
		return false;
	}

	return $data;
}


/**
 * Transforme une chaîne snake_case / kebab-case en CamelCase
 */
function oauth2_client_camelize(string $value): string {

	$value = trim($value);

	// Si déjà en CamelCase, on ne touche pas
	if (!str_contains($value, '_') && !str_contains($value, '-')) {
		return ucfirst($value);
	}

	// Normalisation
	$value = strtolower($value);

	// Remplacement des séparateurs
	$value = str_replace(['_', '-'], ' ', $value);

	// CamelCase
	return str_replace(' ', '', ucwords($value));
}