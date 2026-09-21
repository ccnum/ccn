<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// Si le plugin cicas est actif, il faut suspendre le fonctionnement de cioidc
if (defined('_DIR_PLUGIN_CICAS')) {
	include_spip('cicas/action/logout');
} else {
	include_spip('inc/cioidc_logout');
}
