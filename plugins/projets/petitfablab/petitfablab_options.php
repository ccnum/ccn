<?php

if (!defined('_ECRIRE_INC_VERSION')) {
    return;
}

// PIPELINES
//$GLOBALS['marqueur'] .= ':'.$_COOKIE['mobile'];
//$GLOBALS['spip_pipeline']['pre_propre'] .= '|post_autobr';
//define('_DEBUG_AUTORISER', true);
//define('_AUTOBR', true)

// _ANNEE_SCOLAIRE, _DATE_DEBUT, _DATE_FIN sont définis par le plugin ccn (ccn_options.php)

// Adresses des mails de chapitre (valider_chapitre, petitfablab_fonctions.php) :
// valeurs historiques par défaut, surchargeables dans config/mes_options.php.
if (!defined('_PETITFABLAB_MAIL_FROM')) {
	define('_PETITFABLAB_MAIL_FROM', 'noreply@petitfablab.laclasse.com');
}
if (!defined('_PETITFABLAB_MAIL_DESTINATAIRE')) {
	define('_PETITFABLAB_MAIL_DESTINATAIRE', 'petitfablab@gmail.com');
}
// Copie cachée systématique ('' pour aucune)
if (!defined('_PETITFABLAB_MAIL_COPIE')) {
	define('_PETITFABLAB_MAIL_COPIE', 'cmonnet@erasme.org');
}
// Blog du dispositif, cité en fin de mail ('' pour ne pas le citer)
if (!defined('_PETITFABLAB_URL_BLOG')) {
	define('_PETITFABLAB_URL_BLOG', 'https://petit-fablab-ecriture.tumblr.com/');
}
