<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('class/oauth_provider/Generic');

/**
 * Provider spécifique Dropbox
 *
 * Particularités Dropbox :
 *   - Endpoints fixes
 *   - Paramètre token_access_type=offline requis
 *     pour obtenir un refresh_token
 *   - Authentification client dans le body
 *
 * OAuth2 pur (pas OIDC)
 */
class OAuth2ClientProviderDropbox extends OAuth2ClientProviderGeneric {

    /**
     * Endpoint Authorization Dropbox
     */
    protected $authorize_endpoint = 'https://www.dropbox.com/oauth2/authorize';

    /**
     * Endpoint Token Dropbox
     */
    protected $token_endpoint = 'https://api.dropboxapi.com/oauth2/token';

    /**
     * Construit l’URL d’autorisation Dropbox
     *
     * Dropbox impose :
     *   - token_access_type=offline pour refresh_token
     *
     * @param array $config
     * @param array $options
     * @return string
     */
    public function buildAuthorizationUrl(array $config, array $options = []): string {

        $params = [
            'response_type'     => 'code',
            'client_id'         => $config['client_id'],
            'redirect_uri'      => $config['redirect_uri'],
            'token_access_type' => 'offline',
        ];

        if (!empty($options['state'])) {
            $params['state'] = $options['state'];
        }

        return $this->authorize_endpoint . '?' . http_build_query($params);
    }

    /**
     * Échange code → token
     *
     * Dropbox :
     *   - accepte credentials dans le body
     *   - ne nécessite pas Basic Auth
     *
     * @param OAuth2ClientGrantAbstract $grant
     * @param array $config
     * @return array
     */
    public function requestToken(
        OAuth2ClientGrantAbstract $grant,
        array $config
    ): array {

        $datas = $grant->getTokenParams($config);

        include_spip('inc/distant');

        $res = recuperer_url($this->token_endpoint, [
            'method'  => 'POST',
            'datas'   => $datas,
            'headers' => [
                'Content-Type: application/x-www-form-urlencoded'
            ],
        ]);

        spip_log(
            'Dropbox TOKEN REQUEST datas=' . var_export($datas, true),
            'oauthclient.' . _LOG_DEBUG
        );

        spip_log(
            'Dropbox TOKEN RESPONSE=' . var_export($res, true),
            'oauthclient.' . _LOG_DEBUG
        );

        return json_decode($res['page'] ?? '', true) ?: [];
    }
}
