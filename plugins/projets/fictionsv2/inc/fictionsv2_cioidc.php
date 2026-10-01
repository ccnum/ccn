<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Reconnaissance, à la connexion SSO (CIOIDC / ENT laclasse.com), d'un prof inscrit au
 * projet de l'année (groupe libre ENT "Fictions <année>"), appelée depuis
 * fictionsv2_cioidc_userinfo() dans fictionsv2_pipelines.php.
 *
 * Les histoires ne sont plus créées ici (#520) : une par compte enseignant ne
 * correspondait pas aux participants (classes, #519). Elles se créent depuis la liste
 * des participants de l'année (inc/fictionsv2_histoires.php).
 **/

include_spip('fictionsv2_fonctions');

// Normalise un attribut multivalué : arrive en objet unique (pas en tableau) quand il
// n'y a qu'une seule valeur.
function fictionsv2_cioidc_normaliser_liste($valeur) {
	if (is_object($valeur)) {
		return [$valeur];
	}
	return is_array($valeur) ? $valeur : [];
}

// Minuscules sans accents, espaces ni ponctuation : "Fictions 2026" => "fictions2026".
function fictionsv2_cioidc_normaliser_nom(string $nom) {
	include_spip('inc/charsets');
	return preg_replace('/[^a-z0-9]/', '', strtolower(translitteration($nom)));
}

// Inscrit au projet de CE site pour l'année : un groupe libre dont le nom COMMENCE par
// "<site> <année>" (ex: "Fictions 2026" pour nom_site "fictions.laclasse.com"), même
// règle que thematique_cioidc_groupes_libres_pertinents().
function fictionsv2_cioidc_est_inscrit(array $groupes_libres, string $nom_site, int $annee) {
	$site = fictionsv2_cioidc_normaliser_nom(explode('.', $nom_site)[0]);
	if (!$site) {
		return false;
	}
	$prefixe = $site . $annee;
	foreach ($groupes_libres as $groupe) {
		if (str_starts_with(fictionsv2_cioidc_normaliser_nom($groupe->name ?? ''), $prefixe)) {
			return true;
		}
	}
	return false;
}
