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

function annee_rub1($idr)
{

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
if ((isset($_COOKIE['_cookie_annee_scolaire']))
    && ($_COOKIE['_cookie_annee_scolaire'] != 0)
    && ($_COOKIE['_cookie_annee_scolaire'] != '')
    && ($_COOKIE['_cookie_annee_scolaire'] > 2011)
) {
    $annee_scolaire = $_COOKIE['_cookie_annee_scolaire'];
} else {
    if (date('m') >= '08') {
        $annee_scolaire = date('Y');
    } else {
        $annee_scolaire = date('Y') - 1;
    }
}

if ((isset($_GET['annee_scolaire']))
    && ($_GET['annee_scolaire'] != 0)
    && ($_GET['annee_scolaire'] != '')
) {
    $annee_scolaire = $_GET['annee_scolaire'];
    setcookie('_cookie_annee_scolaire', $annee_scolaire);
}

//Hack temporaire d'indexation d'année
if ((isset($_GET['id_rubrique']))) {
    $id_rub = $_GET['id_rubrique'];
    /*$date = sql_getfetsel("id_parent", "spip_rubriques", "id_rubrique=" . intval($id_rub));

    if ($date != '')
    {
    $annee_scolaire = intval(substr($date,0,4));
    $mois_scolaire = intval(substr($date,5,2));
    if ($mois_scolaire < 9) $annee_scolaire--;
    }*/
    if ($annee_scolaire == '') {
        $annee_scolaire = 2020;
    }
}

define('_annee_scolaire', $annee_scolaire);
