<?php

function valider_chapitre($id_article, $id_rubrique) {
	include_spip('action/editer_objet');
	include_spip('inc/autoriser');

	// Publication
	autoriser_exception('modifier', 'article', $id_article);
	sql_update('spip_articles', ["statut" => "publie"], 'id_article=' . intval($id_article));
	autoriser_exception('modifier', 'article', $id_article, false);

	$envoyer_mail = charger_fonction('envoyer_mail', 'inc');
	// mail
	$bcc = sql_getfetsel("soustitre", "spip_articles", "id_article = " . $id_article);
	$sujet = 'Vous venez d\'écrire un chapitre !';
	$html = "Bonjour,";
	$html .= "<br />Merci d'avoir participé au petit fablab d'écriture !";
	$html .= "<br />Accédez dès maintenant à votre chapitre en ligne : http://petitfablab.laclasse.com/spip.php?page=lecture&id_rubrique=" . $id_rubrique . ". Un deuxième message vous préviendra lorsque votre histoire sera disponible.";
	$html .= "<br />A très bientôt";
	$html .= "<br /><br />Suivez nos actualités sur Twitter @petitfablab ou sur le blog https://petit-fablab-ecriture.tumblr.com/";
	$html .= "<br />Le petit fablab d'écriture est un dispositif imaginé par Erasme, laboratoire d'innovation ouverte de la Métropole de Lyon, en collaboration avec la Villa Gillet.";
	$html .= "<br />Retrouvez le Petit Fab Lab d'écriture à Lyon aux Assises Internationales du Roman et à Grenoble à La Casemate et au salon Experimenta.";

	$contenu_html = recuperer_fond('emails/texte', ['html' => $html]);
	$corps = [
		'html' => $contenu_html,
		'from' => 'noreply@petitfablab.laclasse.com',
		'nom_envoyeur' => 'Petit Fab Lab d\'écriture',
		'bcc' => ['cmonnet@erasme.org', $bcc]
	];
	if (isset($bcc) && ($bcc != "") && (filter_var($bcc, FILTER_VALIDATE_EMAIL))) {
		$envoyer_mail("petitfablab@gmail.com", $sujet, $corps);
	}

	// Si 5ème chapitre
	$n = sql_countsel("titre", "spip_articles", ["statut=" . sql_quote('publie'), "id_rubrique=" . intval($id_rubrique)]);
	if ($n >= 5) {
		$id_parent = sql_getfetsel("id_parent", "spip_rubriques", "id_rubrique=" . intval($id_rubrique));
		$rub_hist = creer_histoire($id_parent);
		$bcc = ['cmonnet@erasme.org'];
		if ($resultats = sql_allfetsel("soustitre", "spip_articles", "id_rubrique = " . intval($id_rubrique))) {
			// boucler sur les resultats
			foreach ($resultats as $res) {
				if (filter_var($res['soustitre'], FILTER_VALIDATE_EMAIL)) {
					$bcc[] = $res['soustitre'];
				}
			}
		}

		$sujet = 'Votre histoire est en ligne !';
		$html = "Bonjour à tous,";
		$html .= "<br />Félicitations votre histoire est en ligne.";
		$html .= "<br />Discutez de l'édition de votre histoire avec vos co-auteurs par retour de mail : http://petitfablab.laclasse.com/spip.php?page=lecture&id_rubrique=" . $id_rubrique;
		$html .= "<br />A très bientôt";
		$html .= "<br /><br />Suivez nos actualités sur Twitter @petitfablab ou sur le blog https://petit-fablab-ecriture.tumblr.com/";
		$html .= "<br />Le petit fablab d'écriture est un dispositif imaginé par Erasme, laboratoire d'innovation ouverte de la Métropole de Lyon, en collaboration avec la Villa Gillet.";
		$html .= "<br />Retrouvez le Petit Fab Lab d'écriture à Lyon aux Assises Internationales du Roman et à Grenoble à La Casemate et au salon Experimenta. ";

		$contenu_html = recuperer_fond('emails/texte', ['html' => $html]);
		$corps = [
			'html' => $contenu_html,
			'from' => 'noreply@petitfablab.laclasse.com',
			'nom_envoyeur' => 'Petit Fab Lab d\'écriture',
			'bcc' => $bcc
		];
		$envoyer_mail("petitfablab@gmail.com", $sujet, $corps);
	}

	// return if last chapitre
	if (isset($rub_hist)) {
		return $rub_hist;
	}
}

function annee_rub($idr) {

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
	if ((isset($_GET['annee_scolaire'])) && ($_GET['annee_scolaire'] != 0) && ($_GET['annee_scolaire'] != '')) {
		$p->code = $_GET['annee_scolaire'];
	} else {
		if (date('m') >= 8) {
			$p->code = date('Y');
		} else {
			$p->code = date('Y') - 1;
		}
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
	return $cutString . substr($str, stripos($str, ' '));
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
		$annee = $annee--;
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
	return $chaineAConcatenerAuDebut . substr($texteChapitre, strlen($texteChapitre) - $nbDeDerniersCaracteresAAfficher, -1);
}


function anneeAAfficher($derniereRubriqueAnneeTrouveeDansSpip = '') {
	if (isset($_GET['annee_scolaire'])) {
		return $_GET['annee_scolaire'];
	}
	return  $derniereRubriqueAnneeTrouveeDansSpip;
}
