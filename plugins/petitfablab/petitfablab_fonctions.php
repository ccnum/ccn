<?php

function cookie($nom, $valeur) {
	setcookie($nom, $valeur, time() + 10 * 24 * 3600);
}

function filtre_creer_histoire($id) {
	include_spip('action/editer_objet');
	include_spip('action/editer_rubrique');
	include_spip('inc/autoriser');
	$id_prologue = sql_getfetsel("id_article", "spip_articles", "`titre` LIKE '%prologue%' AND `statut` LIKE 'publie' AND id_rubrique = " . $id, "", "RAND()");

	//Rubrique
	$id_rub = rubrique_inserer($id);
	autoriser_exception('modifier', 'rubrique', $id_rub);
	objet_modifier('rubrique', $id_rub, ["titre" => "Histoire " . $id_rub, "descriptif" => $id_prologue]);
	objet_instituer('rubrique', $id_rub, ['id_parent' => $id, 'statut' => 'publie']);

	//Prologue
	/*
    $art = objet_inserer('article',$id_rub);
    autoriser_exception('modifier', 'article', $art);
    objet_modifier('article', $art,  array('titre'=>'Prologue','texte'=> $texte));
    sql_update('spip_articles', array("`statut`"=>"'publie'"), "id_article=".$art);
    autoriser_exception('modifier', 'article', $art, false);
    */

	//Cloture exceptions
	autoriser_exception('modifier', 'rubrique', $id_rub, false);

	//Cookie
	cookie('rub_hist', $id_rub);

	return $id_rub;
}

function valider_chapitre($id_article, $id_rubrique) {
	include_spip('action/editer_objet');
	include_spip('inc/autoriser');

	//Publication
	autoriser_exception('modifier', 'article', $id_article);
	sql_update('spip_articles', array("`statut`" => "'publie'"), 'id_article=' . intval($id_article));
	autoriser_exception('modifier', 'article', $id_article, false);

	//mail
	$to = sql_getfetsel("soustitre", "spip_articles", "id_article = " . $id_article);
	$subject = 'Vous venez d\'écrire un chapitre !';
	$message = "Bonjour,\r\n\r\nMerci d'avoir participé au petit fablab d'écriture !\r\nAccédez dès maintenant à votre chapitre en ligne : http://air.laclasse.com/spip.php?scenario=jeu&page=lecture&id_rubrique=" . $id_rubrique . ". Un deuxième message vous préviendra lorsque votre histoire sera disponible.\r\n\r\nA très bientôt\r\n\r\nSuivez nos actualités sur Twitter @petitfablab ou sur le blog https://petit-fablab-ecriture.tumblr.com/ \r\n\r\nLe petit fablab d'écriture est un dispositif imaginé par Erasme, laboratoire d'innovation ouverte de la Métropole de Lyon, en collaboration avec la Villa Gillet. \r\nRetrouvez le Petit Fab Lab d'écriture à Lyon aux Assises Internationales du Roman et à Grenoble à La Casemate et au salon Experimenta.";
	$headers = "From: petitfablab@gmail.com" . "\r\n" .
		"Reply-To: petitfablab@gmail.com" . "\r\n" .
		"Bcc: pvincent@erasme.org, petitfablab@gmail.com" . "\r\n" .
		"Content-Type: text/plain; charset='utf-8'" . "\r\n" .
		"X-Mailer: PHP/" . phpversion();
	if (isset($to) && ($to != "") && (filter_var($to, FILTER_VALIDATE_EMAIL))) {
		mail($to, $subject, $message, $headers);
	}

	//Si 5ème chapitre
	if ($res = sql_select("titre", "spip_articles", "`statut` LIKE 'publie' AND id_rubrique=" . $id_rubrique)) {
		$n = sql_count($res);
	}

	if ($n >= 5) {
		$id_parent = sql_getfetsel("id_parent", "spip_rubriques", "id_rubrique=" . intval($id_rubrique));
		$rub_hist = creer_histoire($id_parent);
		$to = '';
		if ($resultats = sql_select("soustitre", "spip_articles", "id_rubrique = " . intval($id_rubrique))) {
			// boucler sur les resultats
			while ($res = sql_fetch($resultats)) {
				if (filter_var($res['soustitre'], FILTER_VALIDATE_EMAIL)) {
					$to .= $res['soustitre'] . ",";
				}
			}
		}

		$subject = 'Votre histoire est en ligne !';
		$message = "Bonjour à tous,\r\n\r\nFélicitations votre histoire est en ligne.\r\nDiscutez de l'édition de votre histoire avec vos co-auteurs par retour de mail : http://air.laclasse.com/spip.php?scenario=jeu&page=lecture&id_rubrique=" . $id_rubrique . "\r\n\r\nA très bientôt\r\n\r\nSuivez nos actualités sur Twitter @petitfablab ou sur le blog https://petit-fablab-ecriture.tumblr.com/ \r\n\r\nLe petit fablab d'écriture est un dispositif imaginé par Erasme, laboratoire d'innovation ouverte de la Métropole de Lyon, en collaboration avec la Villa Gillet.  \r\nRetrouvez le Petit Fab Lab d'écriture à Lyon aux Assises Internationales du Roman et à Grenoble à La Casemate et au salon Experimenta. ";
		$headers = "From: petitfablab@gmail.com" . "\r\n" .
			"Reply-To: petitfablab@gmail.com" . "\r\n" .
			"Bcc: pvincent@erasme.org, petitfablab@gmail.com" . "\r\n" .
			"Content-Type: text/plain; charset='utf-8'" . "\r\n" .
			"X-Mailer: PHP/" . phpversion();
		//$to = "pvincent@erasme.org";
		if ((isset($to)) && ($to != "")) {
			mail($to, $subject, $message, $headers);
		}
	}

	//return if last chapitre
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


// FILTRE SOUSTRATION

function balise_SOUSTRACTION_dist($p) {
	$a = interprete_argument_balise(1, $p);
	$b = interprete_argument_balise(2, $p);

	if ($a == '' || $b == '') {
		$p->code = '\'#SOUSTRACTION[Manque argument]\'';
	} else {
		$p->code = '(' . $a . '-' . $b . ')';
	}

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
