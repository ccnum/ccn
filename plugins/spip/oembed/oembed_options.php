<?php

if (test_espace_prive()) {
	// Remplir ('_IFRAME_SAFE_DOMAINS') avec les providers
	// uniquement dans l'espace privé car c'est un peu couteux et cela ne sert que là bas
	include_spip('inc/oembed');
	if (!defined('_IFRAME_SAFE_DOMAINS')) {
		define('_IFRAME_SAFE_DOMAINS', oembed_allowed_domains(oembed_lister_providers()));
	}
}
