<?php

function valider_chapitre($id_article, $id_rubrique) {
	include_spip('action/editer_objet');
	include_spip('inc/autoriser');

	// Seul un auteur de l'article peut valider son propre chapitre : l'id_article
	// vient de l'URL, et autoriser_exception() ci-dessous lève tout contrôle d'accès.
	$id_auteur = (int) ($GLOBALS['visiteur_session']['id_auteur'] ?? 0);
	if (
		!$id_auteur
		or !sql_countsel('spip_auteurs_liens', [
			"objet='article'",
			'id_objet=' . intval($id_article),
			'id_auteur=' . $id_auteur,
		])
	) {
		return '';
	}
	// Jeton posé par formulaires/editer_article.php au retour du formulaire : la
	// publication suit un GET, il faut prouver qu'il vient bien de ce formulaire.
	include_spip('inc/securiser_action');
	if (!verifier_action_auteur('valider_chapitre-' . intval($id_article), (string) _request('valider'))) {
		return '';
	}

	// Publication
	autoriser_exception('modifier', 'article', $id_article);
	objet_modifier('article', intval($id_article), ['statut' => 'publie']);
	autoriser_exception('modifier', 'article', $id_article, false);

	// mail à l'auteur du chapitre (soustitre = son email), seulement s'il est valide
	$bcc = sql_getfetsel("soustitre", "spip_articles", "id_article = " . intval($id_article));
	if ($bcc && filter_var($bcc, FILTER_VALIDATE_EMAIL)) {
		$html = "Bonjour,";
		$html .= _T('petitfablab:mail_merci_participation');
		$html .= _T('petitfablab:mail_acceder_chapitre', ['url' => petitfablab_url_lecture($id_rubrique)]);
		petitfablab_envoyer_mail(_T('petitfablab:sujet_chapitre_publie'), $html, [$bcc]);
	}

	// Si 5ème chapitre
	$n = sql_countsel("titre", "spip_articles", ["statut=" . sql_quote('publie'), "id_rubrique=" . intval($id_rubrique)]);
	if ($n == 5) {
		$id_parent = sql_getfetsel("id_parent", "spip_rubriques", "id_rubrique=" . intval($id_rubrique));
		$rub_hist = creer_histoire($id_parent);
		$bcc = [];
		if ($resultats = sql_allfetsel("soustitre", "spip_articles", "id_rubrique = " . intval($id_rubrique))) {
			// boucler sur les resultats
			foreach ($resultats as $res) {
				if (filter_var($res['soustitre'], FILTER_VALIDATE_EMAIL)) {
					$bcc[] = $res['soustitre'];
				}
			}
		}

		$html = _T('petitfablab:mail_bonjour_tous');
		$html .= _T('petitfablab:mail_felicitations');
		$html .= _T('petitfablab:mail_discutez_edition', ['url' => petitfablab_url_lecture($id_rubrique)]);
		petitfablab_envoyer_mail(_T('petitfablab:sujet_histoire_en_ligne'), $html, $bcc);
	}

	// return if last chapitre
	if (isset($rub_hist)) {
		return $rub_hist;
	}
}

/**
 * Envoie un mail du dispositif : $html (début du message) complété de la
 * formule de fin commune, au destinataire du dispositif, $bcc en copie cachée
 * (avec _PETITFABLAB_MAIL_COPIE).
 */
function petitfablab_envoyer_mail(string $sujet, string $html, array $bcc): void {
	$html .= _T('petitfablab:mail_a_bientot');
	$html .= _T('petitfablab:mail_description_dispositif');
	if (_PETITFABLAB_URL_BLOG) {
		$html .= _T('petitfablab:mail_suivez_blog', ['url' => _PETITFABLAB_URL_BLOG]);
	}
	$envoyer_mail = charger_fonction('envoyer_mail', 'inc');
	$envoyer_mail(_PETITFABLAB_MAIL_DESTINATAIRE, $sujet, [
		'html' => recuperer_fond('emails/texte', ['html' => $html]),
		'from' => _PETITFABLAB_MAIL_FROM,
		'nom_envoyeur' => _T('petitfablab:nom_envoyeur'),
		'bcc' => array_values(array_unique(array_filter(array_merge([_PETITFABLAB_MAIL_COPIE], $bcc)))),
	]);
}

/**
 * URL absolue de la page de lecture d'une histoire, sur le site courant (et donc
 * en https s'il l'est) plutôt qu'un http://petitfablab.laclasse.com en dur.
 */
function petitfablab_url_lecture($id_rubrique) {
	return url_absolue(generer_url_public('lecture', 'id_rubrique=' . intval($id_rubrique), true));
}

// annee_rub, balise_ANNEE_SCOLAIRE_dist, balise_ANNEE_ACTUELLE_dist, afficher_options_date
// sont définis par le plugin ccn (ccn_fonctions.php)
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

/**
 * Filtre pour couper le texte à l'affichage
 */
function filtre_cleanCut($string, $length = 380, $cutString = '(...)') {
	if (strlen($string) <= $length) {
		return $string;
	}
	$str = substr($string, strlen($string) - $length - 7, strlen($string));
	return $cutString . substr($str, stripos($str, ' '));
}
