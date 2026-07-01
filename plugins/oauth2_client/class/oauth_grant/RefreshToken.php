<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('class/oauth_grant/GrantAbstract');

class OAuth2ClientGrantRefreshToken extends OAuth2ClientGrantAbstract {

	protected string $grant_type = 'refresh_token';

	public function getTokenParams(array $config): array {

	$params = [
		'grant_type'     => 'refresh_token',
		'refresh_token' => $config['refresh_token'] ?? null,
	];

	// client authentication (body)
	if (!empty($config['client_id'])) {
		$params['client_id'] = $config['client_id'];
	}
	if (!empty($config['client_secret'])) {
		$params['client_secret'] = $config['client_secret'];
	}

	return $params;
	}
}
