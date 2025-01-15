<?php

/**
 * API de vérification : vérification de la validité d'un slug
 *
 * Slug : court texte utilisable [...] pour décrire et identifier une ressource.
 * https://en.wikipedia.org/wiki/Clean_URL#Slug
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
 * Vérifie la validité d'un slug
 *
 * Un slug est un court texte utilisé pour identifier une ressource.
 * Il ne contient que des charactères alphanumérique ou un séparateur (par défaut, un underscore)
 * Donc ni charactère accentué, ponctuation ou espace blanc.
 *
 * Permet de normaliser la valeur, ou alternativement de ne faire qu'une suggestion dans le message d'erreur.
 *
 * @note
 *   La normalisation nécéssite pour l'instant le plugin Bonux
 *
 * @param string $valeur
 *   La valeur à vérifier.
 * @param array $options
 *   - (bool) normaliser          : pour convertir automatiquement la chaîne au bon format.
 *   - (bool) normaliser_suggerer : pour suggérer la chaîne normalisée au lieu de la modifier (alternative à l'option précédente).
 *   - (string) separateur        : un ou plusieurs charactères acceptés pour séparer les mots (sans espace), défaut = underscore
 *                                  Si multiples, on prend le 1er pour normaliser
 *   - (int) longueur_maxi        : nombre maximal de charactères, défaut = 60
 * @param null $valeur_normalisee
 *   Si normalisation à faire, la variable sera remplie par la chaîne normalisée.
 *   Ex. : « Ô, toi, l’écureuil ! » devient « o_toi_l_ecureuil »
 * @return string
 *   Retourne une chaine vide si c'est valide, sinon une chaine expliquant l'erreur.
 */
function verifier_slug_dist($valeur, $options = [], &$valeur_normalisee = null) {

	// On reprend les options par défaut de slugify() car on veut afficher ces valeurs dans les messages d'erreurs
	$options_defaut = [
		'separateur'    => '_',
		'longueur_maxi' => 60,
	];
	$options       = array_merge($options_defaut, $options);
	$erreur        = '';
	$erreurs       = []; // On permet d'afficher plusieurs erreurs séparées par des retours ligne
	$normaliser    = !empty($options['normaliser']);
	$suggerer      = !empty($options['normaliser_suggerer']);
	$separateurs   = $options['separateur'] ?? [];
	$separateur    = $separateurs[0] ?? '';
	$longueur_maxi = (int) $options['longueur_maxi'];
	$longueur      = strlen($valeur);

	// 1) Vérifier la longueur si pas de normalisation
	if (
		$longueur > $longueur_maxi
		&& (
			!$normaliser
			|| $suggerer
		)
	) {
		$erreurs[] = _T('verifier:erreur_slug_longueur_maxi', ['nb_max' => $longueur_maxi, 'nb' => $longueur]);
	}

	// 2) Vérifier le format
	// Format = mots composés de charactères alphanumériques en minuscules séparés par un charactère alternatif
	$pattern_sep = (strlen($separateurs) > 1 ? '[' . preg_quote($separateurs) . ']' : $separateur);
	$is_slug = preg_match("/^[a-z0-9]+(?:{$pattern_sep}[a-z0-9]+)*\$/", $valeur);
	if (
		!$is_slug
		&& !$normaliser
		&& !$suggerer
	) {
		$erreurs[] = _T('verifier:erreur_slug', ['separateur' => $separateurs]);
	}

	// 3) Normaliser ou faire une suggestion
	// Format = idem précédent mais limité à un seul type de séparateur
	$is_slug_normaliser = preg_match("/^[a-z0-9]+(?:{$separateur}[a-z0-9]+)*\$/", $valeur);
	if (
		!$is_slug_normaliser
		&& ($normaliser || $suggerer)
	) {
		$options_normaliser = [
			'separateur'    => $separateur,
			'longueur_maxi' => $longueur_maxi,
		];
		$options_normaliser = array_filter($options_normaliser);
		include_spip('inc/filtres');
		$valeur_normalisee = identifiant_slug($valeur, '', $options_normaliser);

		// En cas de suggestion, ne pas retourner la valeur par référence
		if ($suggerer) {
			$erreurs[] = _T('verifier:erreur_slug', ['separateur' => $separateurs]);
			$erreurs[] = _T('verifier:erreur_slug_normaliser_suggerer', ['valeur' => $valeur_normalisee]);
			$valeur_normalisee = null;
		}
	}

	// Des erreurs ?
	if (count($erreurs) > 0) {
		$erreur = implode('<br>', $erreurs);
	}

	return $erreur;
}
