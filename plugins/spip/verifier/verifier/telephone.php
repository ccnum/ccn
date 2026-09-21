<?php

/**
 * API de vérification : vérification de la validité d'un numéro de téléphone
 *
 * @plugin     verifier
 * @copyright  2018
 * @author     Les Développements Durables
 * @licence    GNU/GPL
 */

// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Vérifie un numéro de téléphone. Pour l'instant seulement avec le schéma français.
 *
 * @param string $valeur
 *   La valeur à vérifier.
 * @param array $options
 *   pays
 * @return string
 *   Retourne une chaine vide si c'est valide, sinon une chaine expliquant l'erreur.
 */
function verifier_telephone_dist($valeur, $options = []) {
	if (!empty($options['message_erreur_defaut'])) {
		$erreur = $options['message_erreur_defaut'];
	}
	else {
		$erreur = _T('verifier:erreur_telephone');
	}
	if (!is_string($valeur)) {
		return $erreur;
	}
	$ok = '';

	// On accepte differentes notations, les points, les tirets, les espaces, les slashes
	$tel = preg_replace('#\.|/|-| #', '', $valeur);

	// Pour les prefixes, on accepte les notations +33 et 0033
	// si on trouve un indicatif de pays, il est prioritaire sur le pays par defaut passe en option
	$telephone_prefixes_pays = charger_fonction('telephone_prefixes_pays', 'verifier');
	$prefixes = $telephone_prefixes_pays();
	if ($options['prefixes_pays'] ?? '') {
		$prefixes += $options['prefixes_pays'];
	}
	foreach ($prefixes as $prefix => $code_pays) {
		$regexp = '/^(\+|00)' . $prefix . '/';
		if (preg_match($regexp, $tel)) {
			$options['pays'] = $code_pays;
			// on normalise le prefixe, mais on ne le remplace par par zero, car ça n'est valable que pour certains pays et ça casse la verif sur d'autres
			$tel = preg_replace($regexp, '+' . $prefix, $tel);
			break;
		}
	}

	// si on connait le pays (par option ou par indicatif) et qu'on a une fonction de verification pour ce pays on utilise
	$pays = (isset($options['pays']) ? strtolower($options['pays']) : null);
	$verifier_telephone_pays = ($pays ? charger_fonction('telephone_pays_' . $options['pays'], 'verifier', true) : false);
	if ($verifier_telephone_pays) {
		$option_verif_pays = [];
		if (isset($options['format'])) {
			$option_verif_pays['format'] = $options['format'];
		}
		if ($e = $verifier_telephone_pays($tel, $erreur, $option_verif_pays)) {
			return $e;
		}
		return $ok;
	}

	// On interdit les 000 etc. mais je pense qu'on peut faire plus malin
	// On interdit egalement les "numéros" tout en lettres
	// TODO finaliser les numéros à la con
	if (intval($tel) == 0) {
		return $erreur;
	}

	return $ok;
}


function verifier_telephone_prefixes_pays_dist() {

	$indicatifs = [
		'32' => 'be',
		'33' => 'fr',
		'34' => 'es',
		'352' => 'lu',
		'41' => 'ch',
	];

	return $indicatifs;
}

/**
 * Verification generique
 * @param array $patterns
 *   liste des regexp en fonction du format : fixe, mobile, all
 * @param string $tel
 *   le numero a verifier
 * @param string $message_erreur_defaut
 * @param array $options
 * @return string
 */
function verifier_telephone_pays_patterns($patterns, $tel, $message_erreur_defaut, $options = []) {
	$format = $options['format'] ?? 'all';

	foreach (['fixe', 'mobile', 'all'] as $format_test) {
		// si on a pas trouve pattern pour le format demande, on finira par le test 'all'
		if ($format === $format_test || $format_test === 'all') {
			// si on a une regexp pour ce format, il faut et il suffit de la matcher
			if (isset($patterns[$format])) {
				if (!preg_match($patterns[$format], $tel)) {
					return $message_erreur_defaut;
				}
				return '';
			}

			// sinon il faut au moins matcher la regexp 'all' si elle est fournie :
			if (isset($patterns['all'])) {
				if (!preg_match($patterns['all'], $tel)) {
					return $message_erreur_defaut;
				}
				// si on voulait un numero generique, on est bon
				if ($format_test === 'all') {
					return '';
				}
			}

			// regarder si on matche un autre des formats connus :
			$has_other_match = false;
			foreach ($patterns as $what => $pattern) {
				if (!in_array($what, [$format, 'all']) && preg_match($pattern, $tel)) {
					$has_other_match = $what;
				}
			}
			// si on est en train de tester all : il faut qu'un des format connu matche
			if ($format_test === 'all') {
				if ($has_other_match === false) {
					return $message_erreur_defaut;
				}
			}
			// sinon il faut qu'aucun des autres formats connus ne matche
			else {
				if ($has_other_match !== false) {
					return $message_erreur_defaut;
				}
			}
			// et donc on doit etre bon (ou en tout cas on a fait de notre mieux)
			return '';
		}
	}
	return '';
}

/*
 * Verification par pays
 * Merci d'ajouter un jeu de tests dans tests/verifier_telephone pour chaque pays ajoute ici
 * (il suffit de suivre le modele de ceux existants)
 */

function verifier_telephone_pays_ch_dist($tel, $message_erreur_defaut, $options = []) {
	$patterns = [
		'all' => '/^(0|\+41)[0-9]{9}$/',
		'mobile' => '/^(0|\+41)7[5-9][0-9]{7}$/',
	];
	return verifier_telephone_pays_patterns($patterns, $tel, $message_erreur_defaut, $options);
}

function verifier_telephone_pays_es_dist($tel, $message_erreur_defaut, $options = []) {
	$patterns = [
		'all' => '/^(\+34)?[689][0-9]{8}$/',
		'mobile' => '/^(\+34)?[6][0-9]{8}$/',
	];
	return verifier_telephone_pays_patterns($patterns, $tel, $message_erreur_defaut, $options);
}

function verifier_telephone_pays_fr_dist($tel, $message_erreur_defaut, $options = []) {
	$patterns = [
		'all' => '/^(0|\+33)[1-9][0-9]{8}$/',
		'mobile' => '/^(0|\+33)[6-7][0-9]{8}$/',
	];
	return verifier_telephone_pays_patterns($patterns, $tel, $message_erreur_defaut, $options);
}

function verifier_telephone_pays_be_dist($tel, $message_erreur_defaut, $options = []) {
	// Patterns
	$patterns = [
		'fixe' => '/^(0|\+32)[0-9]{8}$/',
		'mobile' => '/^(0|\+32)4([56789]\d)[0-9]{6}$/',
	];
	return verifier_telephone_pays_patterns($patterns, $tel, $message_erreur_defaut, $options);
}

function verifier_telephone_pays_lu_dist($tel, $message_erreur_defaut, $options = []) {
	$patterns = [
		'fixe' => '/^(\+352)?[0-9]{5}([0-9]|[0-9][0-9][0-9])?$/',
		'mobile' => '/^(\+352)?6[269]1[0-9]{6}$/',
	];
	return verifier_telephone_pays_patterns($patterns, $tel, $message_erreur_defaut, $options);
}
