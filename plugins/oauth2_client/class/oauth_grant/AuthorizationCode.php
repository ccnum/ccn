<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('class/oauth_grant/GrantAbstract');
include_spip('inc/session');

/**
 * Grant OAuth2 : Authorization Code
 *
 * Implémente le flux OAuth2 standard :
 *   1. Redirection utilisateur vers le provider
 *   2. Réception d’un authorization_code
 *   3. Échange code → access_token
 *
 * Compatible PKCE :
 *   - récupère automatiquement le code_verifier
 *     stocké en session via le state
 *
 * Utilisé par oauth2_client_get_token()
 */
class OAuth2ClientGrantAuthorizationCode extends OAuth2ClientGrantAbstract {

	/**
	 * Type de grant OAuth2
	 *
	 * @var string
	 */
	protected string $grant_type = 'authorization_code';

	/**
	 * Construit les paramètres à envoyer au endpoint token.
	 *
	 * Paramètres standards :
	 *   - grant_type
	 *   - code
	 *   - redirect_uri
	 *   - client_id
	 *   - client_secret
	 *
	 * Si PKCE actif :
	 *   - ajoute code_verifier automatiquement
	 *     depuis la session SPIP
	 *
	 * @param array $config
	 * @return array
	 */
	public function getTokenParams(array $config): array {

		$params = [
			'grant_type'	 => $this->grant_type,
			'code'		   => $config['code'] ?? null,
			'redirect_uri'   => $config['redirect_uri'] ?? null,
			'client_id'	  => $config['client_id'] ?? null,
			'client_secret'  => $config['client_secret'] ?? null,
		];

		// --- PKCE (optionnel, obligatoire si challenge utilisé) ---
		if (!empty($config['state']) && !empty($config['app'])) {

			$pkce = $this->getPkceFromState(
				$config['state'],
				$config['app']
			);

			if (!empty($pkce['verifier'])) {
				$params['code_verifier'] = $pkce['verifier'];
			}
		}

		return $params;
	}

	/**
	 * Récupère les informations PKCE associées à un state
	 * depuis la session SPIP.
	 *
	 * Structure attendue :
	 *   $_SESSION['oauth2'][$app][$state]['pkce']
	 *
	 * @param string $state
	 * @param string $app
	 * @return array|null
	 */
	protected function getPkceFromState(string $state, string $app): ?array {

		$oauth2 = session_get('oauth2') ?? [];

		if (empty($oauth2[$app][$state]['pkce'])) {
			return null;
		}

		return $oauth2[$app][$state]['pkce'];
	}
}
