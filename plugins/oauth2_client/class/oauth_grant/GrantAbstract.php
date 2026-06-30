<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Classe abstraite représentant un Grant OAuth2.
 *
 * Un Grant définit :
 *   - le type de flux OAuth2 utilisé
 *   - les paramètres à envoyer au endpoint token
 *   - la méthode HTTP
 *   - les headers éventuels
 *
 * Implémentations concrètes :
 *   - OAuth2ClientGrantAuthorizationCode
 *   - OAuth2ClientGrantClientCredentials
 *   - OAuth2ClientGrantRefreshToken
 *
 * Utilisée par :
 *   oauth2_client_grant_factory()
 *   oauth2_client_get_token()
 */
abstract class OAuth2ClientGrantAbstract {

	/**
	 * Nom du grant OAuth2.
	 *
	 * Exemples :
	 *   - authorization_code
	 *   - client_credentials
	 *   - refresh_token
	 *
	 * @var string
	 */
	protected string $grant_type;

	/**
	 * Construit les paramètres à envoyer
	 * au endpoint token (body POST).
	 *
	 * Chaque grant doit implémenter :
	 *   - les paramètres obligatoires
	 *   - les paramètres optionnels (scope, code_verifier, etc.)
	 *
	 * @param array $config Configuration OAuth2
	 * @return array Paramètres HTTP
	 */
	abstract public function getTokenParams(array $config): array;

	/**
	 * Retourne la méthode HTTP utilisée
	 * pour l'appel au endpoint token.
	 *
	 * Par défaut : POST (standard OAuth2).
	 *
	 * @return string
	 */
	public function getHttpMethod(): string {
		return 'POST';
	}

	/**
	 * Retourne les headers HTTP spécifiques au grant.
	 *
	 * Par défaut :
	 *   Content-Type: application/x-www-form-urlencoded
	 *
	 * Peut être surchargé si nécessaire
	 * (ex: Basic Auth, JSON, etc.).
	 *
	 * @param array $config
	 * @return array
	 */
	public function getHeaders(array $config): array {
		return [
			'Content-Type: application/x-www-form-urlencoded',
		];
	}
}
