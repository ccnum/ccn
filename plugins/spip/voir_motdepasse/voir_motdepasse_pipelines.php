<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function  voir_motdepasse_formulaire_fond($flux) {
	// On regarde rapidement si la page a des input password
	if (strpos($flux['data'], 'type="password"') !== false || strpos($flux['data'], "type='password'") !== false) {
		$lang = json_encode([
			'masquer_label' => _T('voir_motdepasse:masquer_label'),
			'masquer_title' => _T('voir_motdepasse:masquer_title'),
			'voir_label' => _T('voir_motdepasse:voir_label'),
			'voir_title' => _T('voir_motdepasse:voir_title'),
		]);

		$insert = '<script type="module">'
			. 'window.spipConfig = window.spipConfig || {};'
			. 'window.spipConfig.voir_motdepasse = {lang: ' . $lang . '};'
			. '</script>' . "\n";

		$scripts = ['javascript/voir_motdepasse_all.min.js'];
		// debug version
		// $scripts = ['javascript/hideShowPassword.js', 'javascript/voir_motdepasse.js'];
		foreach ($scripts as $script) {
			$insert .= '<script type="module" src="'
				. timestamp(find_in_path($script)) . '"></script>'
				. "\n";
		}

		$flux['data'] .= $insert;
	}

	return $flux;
}
