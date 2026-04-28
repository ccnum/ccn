<?php

include_spip('action/editer_objet');
include_spip('base/abstract_sql');

function annee_rub($idr) {

    $annee_scolaire = 0;
    $date = sql_getfetsel('maj', 'spip_rubriques', 'id_rubrique=' . intval($idr));

    if ($date != '') {
        $annee_scolaire = intval(substr($date, 0, 4));
        $mois_scolaire = intval(substr($date, 5, 2));
        if ($mois_scolaire < 9) {
            $annee_scolaire--;
        }
    }

    return $annee_scolaire;
}

function balise_ANNEE_SCOLAIRE_dist($p) {
    if (isset($_GET['annee_scolaire'])) {
        $_annee = intval($_GET['annee_scolaire']);
        if ($_annee > 2011 && $_annee < 2100) {
            $p->code = $_annee;
            return $p;
        }
    }
    if (intval(date('m')) >= 8) {
        $p->code = intval(date('Y'));
    } else {
        $p->code = intval(date('Y')) - 1;
    }
    return $p;
}

function balise_ANNEE_ACTUELLE_dist($p) {
    if (date('m') >= 8) {
        $p->code = date('Y');
    } else {
        $p->code = date('Y') - 1;
    }
    return $p;
}

function balise_NOM_AUTEUR_dist($p) {
    $p->code = "'Violaine Schwartz'";
    return $p;
}

// Si balise_FIN_dist = false -> affichage de la grille sur la page d'accueil
// Si balise_FIN_dist = true -> affichage des couvertures et liens pdf sur la page d'accueil

function balise_FIN_dist($p) {
    $p->code = "'true'";
    return $p;
}

// Si balise_LECTURE_dist = false -> les textes sont masqués dans la vue lecture
// Si balise_LECTURE_dist = true -> les textes sont affichés dans la vue lecture

function balise_LECTURE_dist($p) {
    $p->code = "'true'";
    return $p;
}

// FUNCTION CLEANCUT
function cleanCut($string, $length = 380, $cutString = '(...)') {
    if (strlen($string) <= $length) {
        return $string;
    }
    $str = substr($string, strlen($string) - $length - 7, strlen($string));
    $pos = stripos($str, ' ');
    return $cutString . ($pos !== false ? substr($str, $pos) : $str);
}

/**
 * La fonction prend la date actuelle et l'année scolaire et tente de déduire quelle option rendre
 * sélectionnée par défaut.
 *
 * @param  $annee
 * @param  $mois
 * @param  $annee_scolaire
 * @return string
 */
function afficher_options_date($annee, $mois, $annee_scolaire) {
    $texte = '';
    if (date('m') >= 8) {
        $annee_actuelle = date('Y');
    } else {
        $annee_actuelle = date('Y') - 1;
    }
    if ($mois < 8) {
        $annee--;
    }
    for ($i = $annee_actuelle; $i >= $annee; $i--) {
        $j = $i + 1;
        $texte .= "<option style='' value='$i'";
        if ($i == $annee_scolaire) {
            $texte .= ' selected ';
        }
        $texte .= ">$i/$j</option>";
    }
    return $texte;
}

/**
 * Cette fonction reçoit une chaîne de caractère (un chapitre complet) et doit en retrancher les X derniers caractères.
 * X étant l'entier reçu en deuxième argument. Puis chaque caractère doit être remplacé par un x.
 *
 * -> si le chapitre contient moins de caractères que le nb de caractères à tronquer, on ne renvoie qu'une chaîne vide.
 *
 * @param  string $texteAMasquer
 * @param  int    $nbDeCaracteresATronquerALaFin
 * @return string
 */
function masquerTexteChapitre(string $texteAMasquer = '', int $nbDeCaracteresATronquerALaFin = 325): string {
    if (strlen($texteAMasquer) < $nbDeCaracteresATronquerALaFin) {
        return '';
    }
    $texteTronque = substr($texteAMasquer, 0, strlen($texteAMasquer) - $nbDeCaracteresATronquerALaFin);

    // Remplace tous les caractères sauf les diacritiques.
    // Les RegEx ne semblent pas vouloir fonctionner :/ Je soupçonne un pb d'encodage iso-latin/utf-8. AU SECOURS !
    $caracteresAMasquer = [
        'à',
        'ä',
        'â',
        'À',
        'Ä',
        'Â',
        'ç',
        'Ç',
        'é',
        'è',
        'ë',
        'ê',
        'É',
        'È',
        'Ë',
        'Ê',
        'î',
        'ï',
        'Î',
        'Ï',
        'ô',
        'ö',
        'Ô',
        'Ö',
        'ù',
        'û',
        'ü',
        'Ù',
        'û',
        'ü',
        'ŷ',
        'ÿ',
        'Ŷ',
        'Ÿ',
        'a',
        'b',
        'c',
        'd',
        'e',
        'f',
        'g',
        'h',
        'i',
        'j',
        'k',
        'l',
        'm',
        'n',
        'o',
        'p',
        'q',
        'r',
        's',
        't',
        'u',
        'v',
        'w',
        'x',
        'y',
        'z',
        'A',
        'B',
        'C',
        'D',
        'E',
        'F',
        'G',
        'H',
        'I',
        'J',
        'K',
        'L',
        'M',
        'N',
        'O',
        'P',
        'Q',
        'R',
        'S',
        'T',
        'U',
        'V',
        'W',
        'X',
        'Y',
        'Z  ',
    ];
    return str_replace($caracteresAMasquer, 'X', $texteTronque);
}

/**
 * Cette fonction reçoit une chaîne de caractères (un chapitre) et doit n'en retourner que les X derniers caractères. X
 * étant le nombre de caractères finaux que nous désirons. Enfin avant de renvoyer la fin de chaîne ainsi tronquée, on
 * lui concatènera (à son commencement) la chaîne de caractère éventuelle reçue en troisième argument (qui est sensée
 * symboliser le texte manquant)
 *
 * Ex : recupererDernieresLignesChapitres('toto_titi_tutu_tata', 5, '...') renverra ...titi_tutu_tata
 *
 * -> Si le nombre de caractères voulus est supérieur à la taille du texte, on renverra le texte complet sans la chaîne
 * à concaténer au début.
 *
 * @param  string $texteChapitre
 * @param  int    $nbDeDerniersCaracteresAAfficher
 * @return string
 */
function recupererDernieresLignesChapitres($texteChapitre = '', $nbDeDerniersCaracteresAAfficher = 325, $chaineAConcatenerAuDebut = '(...)') {
    if (strlen($texteChapitre) < $nbDeDerniersCaracteresAAfficher) {
        return $texteChapitre;
    }
    return $chaineAConcatenerAuDebut . substr($texteChapitre, -$nbDeDerniersCaracteresAAfficher);
}


function anneeAAfficher($derniereRubriqueAnneeTrouveeDansSpip = '') {
    if (isset($_GET['annee_scolaire'])) {
        $_annee = intval($_GET['annee_scolaire']);
        if ($_annee > 2011 && $_annee < 2100) {
            return $_annee;
        }
    }
    return $derniereRubriqueAnneeTrouveeDansSpip;
}
