<?php
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function login_oauth2_userinfo_oidc(string $access_token,string $endpoint,string $provider): array {
	include_spip('inc/distant');

	// Tentative standard OAuth2
	$response = recuperer_url($endpoint, [
		'headers' => [
			'Authorization: Bearer ' . $access_token
		]
	]);

	// Fallback query string si échec
	if (!$response || empty($response['status']) || $response['status'] != 200) {
		$url = $endpoint
			. (strpos($endpoint, '?') ? '&' : '?')
			. 'access_token=' . urlencode($access_token);
		$response = recuperer_url($url);
	}

	if (!$response || empty($response['status']) || $response['status'] != 200) {
		return [];
	}

	$data = json_decode($response['page'], true);

	if (!is_array($data)) {
		return [];
	}

	return [
		'subject' => $data['sub'] ?? $data['id'] ?? $data['user_id'] ?? null,
		'email' => isset($data['email']) ? strtolower(trim($data['email'])) : null,
		'email_verified' =>!empty($data['email_verified']),
		'provider' => $provider
	];
}