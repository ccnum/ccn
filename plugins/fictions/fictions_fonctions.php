<?php

// annee_rub, balise_ANNEE_SCOLAIRE_dist, balise_ANNEE_ACTUELLE_dist, afficher_options_date
// sont définis par le plugin ccn (ccn_fonctions.php)

include_spip('action/editer_objet');
include_spip('base/abstract_sql');

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
    // Si la chaîne est plus courte que la longueur maximale, on la retourne telle quelle
    if (mb_strlen($string) <= $length) {
        return $string;
    }

    // On prend la fin de la chaîne en commençant à la position calculée
    $start = mb_strlen($string) - $length + mb_strlen($cutString);
    $str = mb_substr($string, max(0, $start));

    // On cherche le premier espace pour couper proprement
    $pos = mb_strpos($str, ' ');

    // Si on trouve un espace, on coupe à cet endroit, sinon on prend toute la fin
    if ($pos !== false) {
        $result = $cutString . mb_substr($str, $pos);
    } else {
        $result = $cutString . $str;
    }

    return $result;
}

/**
 * Reçoit un chapitre complet et masque les X derniers caractères en remplaçant
 * toutes les lettres (y compris les caractères accentués) par 'X'.
 *
 * Si le chapitre contient moins de caractères que le nb à tronquer, retourne une chaîne vide.
 *
 * @param  string $texteAMasquer
 * @param  int    $nbDeCaracteresATronquerALaFin
 * @return string
 */
function masquerTexteChapitre(string $texteAMasquer = '', int $nbDeCaracteresATronquerALaFin = 325): string {
    if (mb_strlen($texteAMasquer) < $nbDeCaracteresATronquerALaFin) {
        return '';
    }
    $texteTronque = mb_substr($texteAMasquer, 0, mb_strlen($texteAMasquer) - $nbDeCaracteresATronquerALaFin);

    return preg_replace('/\p{L}/u', 'X', $texteTronque);
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
function recupererDernieresLignesChapitres(string $texteChapitre = '', int $nbDeDerniersCaracteresAAfficher = 325, string $chaineAConcatenerAuDebut = '(...)'): string {
    if (mb_strlen($texteChapitre) < $nbDeDerniersCaracteresAAfficher) {
        return $texteChapitre;
    }
    return $chaineAConcatenerAuDebut . mb_substr($texteChapitre, -$nbDeDerniersCaracteresAAfficher);
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
