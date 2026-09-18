<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// _ANNEE_SCOLAIRE, _DATE_DEBUT, _DATE_FIN sont définis par le plugin ccn (ccn_options.php)

if (!defined('_FICTIONSV2_ID_BLOG_PEDA')) {
	define('_FICTIONSV2_ID_BLOG_PEDA', 12);
}

// Rubrique "blog auteur" (#229) : à surcharger dans mes_options.php du site une fois la
// rubrique créée (format à valider avec @cmonnet). 0 = aucun effet tant que non défini.
if (!defined('_FICTIONSV2_ID_BLOG_AUTEUR')) {
	define('_FICTIONSV2_ID_BLOG_AUTEUR', 0);
}
