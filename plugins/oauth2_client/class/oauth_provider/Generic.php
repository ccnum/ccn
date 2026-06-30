<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Provider OAuth2 générique
 *
 * Implémente le comportement standard OAuth2 :
 *   - Construction de l’URL d’autorisation
 *   - Appel du endpoint token
 *
 * Compatible :
 *   - OAuth2 pur
 *   - OpenID Connect (via scope openid)
 *
 * Peut fonctionner :
 *   - En mode inline (endpoints fournis dans $config)
 *   - En fallback générique
 *
 * Supporte :
 *   - Authentification client via body (défaut)
 *   - Authentification client via Basic Auth
 */
class OAuth2ClientProviderGeneric {

    /**
     * Endpoint Authorization
     *
     * @var string|null
     */
	protected $authorize_endpoint = null;

    /**
     * Endpoint Token
     *
     * @var string|null
     */
    protected $token_endpoint = null;

    /**
     * Mode d’authentification client
     *
     * Valeurs possibles :
     *   - body  (défaut RFC)
     *   - basic (Authorization: Basic ...)
     *
     * @var string
     */
    protected $client_auth = 'body';

    /**
     * Initialise le provider avec la configuration
     *
     * @param array $config
     */
    public function __construct(array $config = []) {

        if ($this->authorize_endpoint === null && !empty($config['authorize_endpoint'])) {
            $this->authorize_endpoint = $config['authorize_endpoint'];
        }

        if ($this->token_endpoint === null && !empty($config['token_endpoint'])) {
            $this->token_endpoint = $config['token_endpoint'];
        }

        if (!empty($config['client_auth'])) {
            $this->client_auth = $config['client_auth'];
        }
    }

    /**
     * Construit l’URL d’autorisation OAuth2
     *
     * Utilisé pour :
     *   - authorization_code
     *   - PKCE
     *   - OIDC (avec scope openid)
     *
     * Paramètres générés :
     *   - response_type
     *   - client_id
     *   - redirect_uri
     *   - scope
     *   - state
     *   - options supplémentaires (PKCE, nonce, etc.)
     *
     * @param array $config
     * @param array $options
     * @return string|null
     */
    public function buildAuthorizationUrl(array $config, array $options = []) {

        if (empty($config['authorize_endpoint'])) {
            return null;
        }

        $params = array_merge([
            'response_type' => $config['response_type'] ?? 'code',
            'client_id'     => $config['client_id'] ?? null,
            'redirect_uri'  => $config['redirect_uri'] ?? null,
            'scope'         => $config['scope'] ?? null,
            'state'         => $options['state'] ?? null,
        ], $options);

        // Nettoyage des valeurs nulles
        $params = array_filter($params, static function ($v) {
            return $v !== null && $v !== '';
        });

        return $config['authorize_endpoint']
            . (str_contains($config['authorize_endpoint'], '?') ? '&' : '?')
            . http_build_query($params);
    }

    /**
     * Appelle le endpoint token OAuth2
     *
     * Gère :
     *   - authorization_code
     *   - refresh_token
     *   - client_credentials
     *
     * Utilise cURL pour plus de fiabilité
     * (notamment avec Keycloak / OIDC)
     *
     * @param OAuth2ClientGrantAbstract $grant
     * @param array $config
     * @return array Token brut décodé
     */
    public function requestToken(
        OAuth2ClientGrantAbstract $grant,
        array $config
    ): array {

        // Paramètres spécifiques au grant
        $datas = $grant->getTokenParams($config);

        $headers = [
            'Content-Type: application/x-www-form-urlencoded'
        ];

        /**
         * Authentification client
         *
         * Mode "basic" :
         *   Authorization: Basic base64(client_id:client_secret)
         *
         * Dans ce cas, les credentials sont retirés du body
         */
        if ($this->client_auth === 'basic') {

            if (empty($config['client_id']) || empty($config['client_secret'])) {
                spip_log(
                    'OAuth2: client_id / secret manquant (basic auth)',
                    'oauth2_client' . _LOG_ERREUR
                );
                return [];
            }

            $headers[] =
                'Authorization: Basic ' .
                base64_encode($config['client_id'] . ':' . $config['client_secret']);

            unset(
                $datas['client_id'],
                $datas['client_secret']
            );
        }

        /**
         * Appel HTTP via cURL
         */
        $ch = curl_init($this->token_endpoint);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($datas),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 20,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);

        curl_close($ch);

        spip_log(
            'OAuth2: token response http=' . $httpCode .
            ' RAW=' . var_export($response, true) .
            ' CURL_ERROR=' . $error,
            'oauthclient.' . _LOG_DEBUG
        );

        // Gestion erreur HTTP
        if ($httpCode !== 200 || !$response) {
            spip_log(
                'OAuth2: token error http=' . $httpCode,
                'oauthclient.' . _LOG_ERREUR
            );
            return [];
        }

        return json_decode($response, true) ?: [];
    }
}
