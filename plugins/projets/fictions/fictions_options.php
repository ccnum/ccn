<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// _ANNEE_SCOLAIRE, _DATE_DEBUT, _DATE_FIN sont définis par le plugin ccn (ccn_options.php)

// Rubrique "blog auteur" (#229) : à surcharger dans mes_options.php du site une fois la
// rubrique créée (format à valider avec @cmonnet). 0 = aucun effet tant que non défini.
if (!defined('_FICTIONSV2_ID_BLOG_AUTEUR')) {
	define('_FICTIONSV2_ID_BLOG_AUTEUR', 0);
}

// À partir de cette année scolaire, le premier chapitre de chaque histoire n'est plus
// une copie par histoire mais l'article unique de la rubrique de l'année portant le
// mot-clé "chapitre1" (cf fictions_id_chapitre1()).
if (!defined('_FICTIONSV2_ANNEE_CHAPITRE1_COMMUN')) {
	define('_FICTIONSV2_ANNEE_CHAPITRE1_COMMUN', 2026);
}
