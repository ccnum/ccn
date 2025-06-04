<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// cicas
function fictions_cicas($flux) {
	if (is_array($flux) and isset($flux['args'])) {
		spip_log($flux['args'], 'test_fictions');
	}

	return $flux;
}
