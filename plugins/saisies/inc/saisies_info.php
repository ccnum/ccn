<?php

/**
 * Obtenir des informations sur une saisie précise
 *
 **/

/**
 * La saisie renvoie t-elle un tableau?
 * note: on teste saisie par saisie, et non pas type de saisie par type de saisie, car certains types (`selection` par ex.) peuvent, en fonction des options, être tabulaire ou pas.
 * @param $saisie
 * @return return bool true si la saisie est tabulaire, false sinon
 **/
function saisies_saisie_est_tabulaire($saisie) {
	if (in_array($saisie['saisie'], ['checkbox', 'selection_multiple', 'choix_grille'])) {
		$est_tabulaire = true;
	} else {
		if ($saisie['saisie'] === 'selection' && ($saisie['options']['multiple'] ?? '')) {
			$est_tabulaire =  true;
		} else {
			$est_tabulaire = false;
		}
	}
	return pipeline(
		'saisie_est_tabulaire',
		['args' => $saisie, 'data' => $est_tabulaire]
	);
}

/**
 * La saisie remplie-t-elle `$_FILES` ?
 * note: on teste saisie par saisie, et non pas type de saisie par type de saisie, car certains types (`input` par ex.) peuvent, en fonction des options, être fichier ou pas.
 * @param array $saisie
 * @return bool
 **/
function saisies_saisie_est_fichier($saisie) {
	$file = (
		(($saisie['saisie'] === 'input') && ($saisie['options']['type'] ?? '') === 'file')
		|| $saisie['saisie'] === 'fichiers'
	);

	return pipeline(
		'saisie_est_fichier',
		['args' => $saisie, 'data' => $file]
	);
}

/**
 * Indique si une saisie à sa valeur gelée
 * - soit par option disabled avec envoi cachée
 * - soit par option readonly
 * @param array $description description de la saisie
 * @return bool true si gélée, false sinon)
 **/
function saisies_saisie_est_gelee(array $description): bool {
	$options = $description['options'];
	//As t-on bloqué d'une manière ou d'une autre la valeur postée?
	if (($options['readonly'] ?? '') || (($options['disable'] ?? '') && ($options['disable_avec_post'] ?? ''))) {
		return true;
	} else {
		return false;
	}
}

/**
 * @deprecated saisies_verifier_gel_saisie
 * Renommée en saisies_saisie_est_gelee
 * @param array $description
 * @return bool
**/
function saisies_verifier_gel_saisie(array $description): bool {

	trigger_error('fonction `saisie_verifier_gel_saisie()` depréciée, utiliser à la place `saisies_saisie_est_gelee()`', E_USER_DEPRECATED);
	spip_log('fonction `saisie_verifier_gel_saisie()` depréciée, utiliser à la place `saisies_saisie_est_gelee()`', 'deprecated_saisies');
	return saisies_saisie_est_gelee($description);
}

/**
 * Renvoie true si la saisie est un conteneur de sous saisies, qu'elle contienne effectivement des sous saisies ou pas
 * @param array $saisie
 * @return bool
 **/
function saisies_saisie_est_avec_sous_saisies(array $saisie): bool {
	$type = $saisie['saisie'] ?? '';
	if (include_spip("saisies/$type")) {
		$f = $type . '_est_avec_sous_saisies';
		if (function_exists($f)) {
			return $f($saisie);
		}
	}
	return false;
}


/**
 * Renvoie true si la saisie peut recevoir un label, qu'elle contienne effectivement un label ou pas
 * @param array $saisie
 * @return bool
 **/
function saisies_saisie_est_labelisable(array $saisie): bool {
	$type = $saisie['saisie'] ?? '';
	if (include_spip("saisies/$type")) {
		$f = $type . '_est_labelisable';
		if (function_exists($f)) {
			return $f($saisie);
		}
	}
	return true;
}


/**
 * Renvoie true si la saisie correspond à un champ au sens HTML
 * @param array $saisie
 * @return bool
 **/
function saisies_saisie_est_champ(array $saisie): bool {
	$type = $saisie['saisie'] ?? '';
	if (include_spip("saisies/$type")) {
		$f = $type . '_est_champ';
		if (function_exists($f)) {
			return $f($saisie);
		}
	}
	return true;
}

/**
 * Renvoi le label de la saisie
 * la plupart du temps c'est juste le champ label
 * mais pour certaines saisies c'est autre chose
 * @param array $saisie
 * @return string
 **/
function saisies_saisie_get_label(array $saisie): string {
	$type = $saisie['saisie'] ?? '';
	if (include_spip("saisies/$type")) {
		$f = $type . '_get_label';
		if (function_exists($f)) {
			return $f($saisie);
		}
	}
	return $saisie['options']['label'] ?? '';
}
