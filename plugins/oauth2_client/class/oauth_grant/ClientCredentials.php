<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('class/oauth_grant/GrantAbstract');

/**
 * Grant OAuth2 : Client Credentials
 *
 * Flux machine-to-machine (sans utilisateur).
 *
 * Utilisé lorsque l'application :
 *   - agit en son propre nom
 *   - ne nécessite pas de consentement utilisateur
 *   - ne manipule pas de refresh_token
 *
 * Cas typiques :
 *   - API backend
 *   - services internes
 *   - synchronisations automatisées
 *
 * ⚠️ Non compatible OIDC (pas d'id_token, pas de nonce).
 *
 * Utilisé par oauth2_client_get_token()
 */
class OAuth2ClientGrantClientCredentials extends OAuth2ClientGrantAbstract {

	/**
	 * Type de grant OAuth2
	 *
	 * @var string
	 */
	protected string $grant_type = 'client_credentials';

	/**
	 * Construit les paramètres à envoyer au endpoint token.
	 *
	 * Paramètres standards :
	 *   - grant_type
	 *   - client_id
	 *   - client_secret
	 *
	 * Optionnel :
	 *   - scope (si fourni dans la configuration)
	 *
	 * @param array $config
	 * @return array
	 */
	public function getTokenParams(array $config): array {

		$params = [
			'grant_type'    => $this->grant_type,
			'client_id'     => $config['client_id'] ?? null,
			'client_secret' => $config['client_secret'] ?? null,
		];

		if (!empty($config['scope'])) {
			$params['scope'] = $config['scope'];
		}

		return $params;
	}
}
