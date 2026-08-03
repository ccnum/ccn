<?php

/**************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2010                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/actions');
include_spip('inc/editer');

// http://doc.spip.org/@inc_editer_article_dist
function formulaires_public_editer_article_charger_dist(
	$id_article = 'new',
	$id_rubrique = 0,
	$retour = '',
	$lier_trad = 0,
	$config_fonc = 'articles_edit_config',
	$row = [],
	$hidden = ''
) {
	$valeurs = formulaires_editer_objet_charger(
		'article',
		$id_article,
		$id_rubrique,
		$lier_trad,
		$retour,
		$config_fonc,
		$row,
		$hidden
	);
	// il faut enlever l'id_rubrique car la saisie se fait sur id_parent
	// et id_rubrique peut etre passe dans l'url comme rubrique parent initiale
	// et sera perdue si elle est supposee saisie
	if (is_array($valeurs)) {
		unset($valeurs['id_rubrique']);
	}
	// Pré-cocher la case si le mot-clé "livrable" est déjà associé à cet article
	if (is_array($valeurs) && intval($id_article) > 0) {
		$id_mot = sql_getfetsel('id_mot', 'spip_mots', "titre='livrable'");
		if ($id_mot) {
			$lie = sql_getfetsel(
				'id_objet',
				'spip_mots_liens',
				'id_mot=' . intval($id_mot) . " AND objet='article' AND id_objet=" . intval($id_article)
			);
			$valeurs['attendre_livrable'] = $lie ? 'oui' : '';
		}
	}
	return $valeurs;
}

// Choix par defaut des options de presentation
// http://doc.spip.org/@articles_edit_config
function articles_edit_config($row) {
	global $spip_ecran, $spip_lang, $spip_display;

	$config = $GLOBALS['meta'];
	$config['lignes'] = ($spip_ecran == 'large') ? 8 : 5;
	$config['langue'] = $spip_lang;

	return $config;
}

function formulaires_public_editer_article_verifier_dist(
	$id_article = 'new',
	$id_rubrique = 0,
	$retour = '',
	$lier_trad = 0,
	$config_fonc = 'articles_edit_config',
	$row = [],
	$hidden = ''
) {
	$erreurs = formulaires_editer_objet_verifier('article', $id_article, ['titre']);
	if (empty($erreurs['titre']) && strlen(_request('titre')) > 50) {
		$erreurs['titre'] = 'Le titre ne peut pas dépasser 50 caractères.';
	}
	return $erreurs;
}

function formulaires_public_editer_article_traiter_dist(
	$id_article = 'new',
	$id_rubrique = 0,
	$retour = '',
	$lier_trad = 0,
	$config_fonc = 'articles_edit_config',
	$row = [],
	$hidden = ''
) {
	$res = formulaires_editer_objet_traiter(
		'article',
		$id_article,
		$id_rubrique,
		$lier_trad,
		$retour,
		$config_fonc,
		$row,
		$hidden
	);
	// Ajout du champ id_consigne — vérifié que la consigne existe ET que l'utilisateur appartient au même secteur
	$id_consigne = intval(_request('id_consigne'));
	if ($id_consigne > 0) {
		$id_auteur = intval($GLOBALS['visiteur_session']['id_auteur'] ?? 0);
		$id_rubrique_consigne = sql_getfetsel('id_rubrique', 'spip_articles', 'id_article=' . $id_consigne);

		if (!$id_rubrique_consigne || !$id_auteur) {
			$id_consigne = 0;
		} else {
			// Vérifie que la rubrique de la consigne est bien taggée "consignes"
			$id_mot_consignes = sql_getfetsel('id_mot', 'spip_mots', "titre='consignes'");
			$rubrique_est_consigne = $id_mot_consignes ? sql_getfetsel(
				'id_objet',
				'spip_mots_liens',
				'id_mot=' . intval($id_mot_consignes) . " AND objet='rubrique' AND id_objet=" . intval($id_rubrique_consigne)
			) : null;

			if (!$rubrique_est_consigne) {
				$id_consigne = 0;
			} else {
				// Vérifie que l'utilisateur est lié à une rubrique du même secteur que la consigne
				$id_secteur = sql_getfetsel('id_secteur', 'spip_rubriques', 'id_rubrique=' . intval($id_rubrique_consigne));
				$user_dans_secteur = $id_secteur ? sql_getfetsel(
					'lien.id_objet',
					'spip_auteurs_liens AS lien JOIN spip_rubriques AS rub ON lien.id_objet = rub.id_rubrique',
					'lien.id_auteur=' . $id_auteur . " AND lien.objet='rubrique' AND rub.id_secteur=" . intval($id_secteur)
				) : null;

				if (!$user_dans_secteur) {
					$id_consigne = 0;
				}
			}
		}
	}

	// Publication de l'article
	include_spip('action/editer_objet');
	if (!empty($res['id_article'])) {
		$statut = sql_getfetsel('statut', 'spip_articles', 'id_article=' . intval($res['id_article']));
		if ($id_consigne > 0) {
			sql_updateq('spip_articles', ['id_consigne' => $id_consigne], 'id_article=' . intval($res['id_article']));
		}
		if ($statut !== 'publie') {
			objet_instituer('article', $res['id_article'], ['statut' => 'publie']);
		}

		// Case cochée par l'intervenant sur la consigne → associe/dissocie le mot-clé livrable
		$id_mot_livrable = sql_getfetsel('id_mot', 'spip_mots', "titre='livrable'");
		if ($id_mot_livrable) {
			include_spip('action/editer_liens');
			$id_art = intval($res['id_article']);
			if (_request('attendre_livrable') === 'oui') {
				objet_associer(['mots' => intval($id_mot_livrable)], ['articles' => $id_art]);
			} else {
				objet_dissocier(['mots' => intval($id_mot_livrable)], ['articles' => $id_art]);
			}
		}
	}
	return $res;
}
