<?php

if (!defined('_ECRIRE_INC_VERSION')) {
    return;
}

// PIPELINES
//$GLOBALS['marqueur'] .= ':'.$_COOKIE['mobile'];
//$GLOBALS['spip_pipeline']['pre_propre'] .= '|post_autobr';
//define('_DEBUG_AUTORISER', true);
//define('_AUTOBR', true);
define('_cookie_annee_scolaire', 'laclasse_annee_scolaire');

function annee_rub1($idr) {

    $annee_scolaire = 0;
    $date = sql_getfetsel('id_rubrique', 'spip_rubriques', 'id_rubrique=' . intval($idr));

    if ($date != '') {
        $annee_scolaire = intval(substr($date, 0, 4));
        $mois_scolaire = intval(substr($date, 5, 2));
        if ($mois_scolaire < 9) {
            $annee_scolaire--;
        }
    }

    return $annee_scolaire;
}

// ANNEE
$_annee_cookie = isset($_COOKIE['_cookie_annee_scolaire']) ? intval($_COOKIE['_cookie_annee_scolaire']) : 0;
if ($_annee_cookie > 2011 && $_annee_cookie < 2100) {
    $annee_scolaire = $_annee_cookie;
} else {
    if (intval(date('m')) >= 8) {
        $annee_scolaire = intval(date('Y'));
    } else {
        $annee_scolaire = intval(date('Y')) - 1;
    }
}
unset($_annee_cookie);

if (isset($_GET['annee_scolaire'])) {
    $_annee_get = intval($_GET['annee_scolaire']);
    if ($_annee_get > 2011 && $_annee_get < 2100) {
        $annee_scolaire = $_annee_get;
        setcookie('_cookie_annee_scolaire', $annee_scolaire, 0, '/');
    }
    unset($_annee_get);
}

//Hack temporaire d'indexation d'année
if ((isset($_GET['id_rubrique']))) {
    $id_rub = $_GET['id_rubrique'];
    /*$date = sql_getfetsel("id_parent", "spip_rubriques", "id_rubrique=" . intval($id_rub));

    if ($date != '') {
    $annee_scolaire = intval(substr($date,0,4));
    $mois_scolaire = intval(substr($date,5,2));
    if ($mois_scolaire < 9) $annee_scolaire--;
    }*/
    if ($annee_scolaire == '') {
        $annee_scolaire = 2020;
    }
}

define('_annee_scolaire', $annee_scolaire);
