<?php

/**
 * Plugin Spip-Bonux
 * Le plugin qui lave plus SPIP que SPIP
 * (c) 2008 Mathieu Marcillaud, Cedric Morin, Tetue
 * Licence GPL
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Recuperer les champs date_xx et heure_xx, verifier leur coherence et les reformater
 * La fonction est initialement une fonction helper pour les formulaires utilisant le prive/formulaires/dateur pour la saisie de date ou date+heure
 * elle est implémentée au départ dans le plugin organiseur, puis dupliquée dans les plugins agenda et bonux
 *
 * Elle a aussi été utilisée pour la récupération de date sur des formulaires utilisant une saisies/date sans l'option horaire
 * Au passage à saisies 6 les saisies date utilisent un input[type=date] qui envoie la date au format iso YYYY-MM-DD
 * Le dateur du privé est voué à disparaitre et à être remplacé par des input[type=date]
 * La fonction prend donc en charge aussi les dates postées au format iso et l'utilisation éventuelle de saisies/date avec option horaire
 * Lorsqu'on utilise saisies il est plutôt conseillé d'utiliser l'API vérifier pour récupérer ce qui est posté et le remettre en forme
 * @see https://git.spip.net/spip-contrib-extensions/saisies/-/blob/master/UPGRADE_6.0.md?ref_type=heads
 *
 * @param string $suffixe
 * @param bool $horaire
 * @param array $erreurs
 * @return int
 */
function verifier_corriger_date_saisie($suffixe, $horaire, &$erreurs) {
	include_spip('inc/filtres');
	// saisies post un array date+heure si on utilise les horaires
	$name_date = 'date' . ($suffixe ? '_' . $suffixe : '');
	$name_heure = 'heure' . ($suffixe ? '_' . $suffixe : '');
	$date_post = _request($name_date);
	$heure = null;
	// le dateur et saisies jusqu'a v5 postent en 'd/m/Y'
	$format_date = 'd/m/Y';
	if (is_array($date_post) && !empty($date_post['date'])) {
		$date_complete = $date = $date_post['date'];
		// saisies v6 post la date direct en YYYY-MM-DD
		if (preg_match(',\d\d\d\d-\d\d-\d\d,', $date)) {
			$format_date = 'Y-m-d';
		}
		if ($horaire) {
			$heure = ($date_post['heure'] ?? '');
			$date_complete .= ' ' . ($heure ? $heure . ':00' : '');
		}
	} else {
		$date_complete = $date = $date_post;
		// saisies v6 post la date direct en YYYY-MM-DD
		if (preg_match(',\d\d\d\d-\d\d-\d\d,', $date)) {
			$format_date = 'Y-m-d';
		}
		if ($horaire) {
			$heure = trim(_request($name_heure) ?? '');
			$date_complete .= ' ' . ($heure ? $heure . ':00' : '');
		}
	}
	// saisies v6 post la date direct en YYYY-MM-DD
	// saisies jusqu'a v5 poste un dd/mm/YYYY
	// recup_date retrouve l'un comme l'autre
	$r = recup_date($date_complete);
	if (!$r) {
		return '';
	}
	$time = null;
	if (!$time = mktime(0, 0, 0, $r[1], $r[2], $r[0])) {
		$erreurs[$name_date] = _T('spip_bonux:erreur_date');
	} elseif (!$time = mktime($r[3], $r[4], $r[5], $r[1], $r[2], $r[0])) {
		$erreurs[$name_date] = _T('spip_bonux:erreur_heure');
	}
	if ($time) {
		if (trim($date) !== ($d = date($format_date, $time))) {
			$erreurs[$name_date] = _T('spip_bonux:erreur_date_corrigee');
			if (is_array($date_post)) {
				$date_post['date'] = $d;
			} else {
				$date_post = $d;
			}
			set_request($name_date, $date_post);
		}
		if ($horaire and trim($heure) !== ($h = date('H:i', $time))) {
			if (is_array($date_post)) {
				$date_post['heure'] = $h;
				set_request($name_date, $date_post);
				$erreurs[$name_date] = _T('spip_bonux:erreur_heure_corrigee');
			} else {
				$erreurs[$name_heure] = _T('spip_bonux:erreur_heure_corrigee');
				set_request($name_heure, $h);
			}
		}
	}
	return $time;
}
