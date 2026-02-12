<?php

/**
 * Reconstruire la saisie à partir de l'environnement
 * pour le cas de l'emploi de #SAISIE
 **/
function saisies_saisie_from_env(array $env): array {
	if (isset($env['saisie'])) {
		return $env['saisie'];
	}

	$type_saisie = $env['type_saisie'];
	unset($env['type_saisie']);
	unset($env['date']);
	unset($env['lang']);
	unset($env['erreurs']);
	unset($env['valeur']);
	unset($env['date_default']);
	unset($env['date_redac']);
	unset($env['date_redac_default']);
	$saisie = [
		'saisie' => $type_saisie,
		'options' => array_merge(
			['nom' => $env['nom'] ?? ''],
			$env
		)
	];
	return $saisie;
}
