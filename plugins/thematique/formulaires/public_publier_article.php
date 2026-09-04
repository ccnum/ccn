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

function formulaires_public_publier_article_charger_dist($id_rubrique, $type_article, $id_consigne = 0) {
	$valeurs = [
		'id_rubrique' => $id_rubrique,
		'id_parent' => $id_rubrique,
		'id_consigne' => $id_consigne,
		'type_article' => $type_article,
		'id_article' => 0,
		'titre' => '',
		'texte' => '',
	];

	// Si on répond à une consigne, chercher une éventuelle réponse existante
	if ($id_consigne) {
		$reponse = thematique_trouver_reponse_a_une_consigne($id_consigne, $id_rubrique);

		if ($reponse) {
			$valeurs['id_article'] = $reponse['id_article'];
			$valeurs['titre'] = $reponse['titre'];
			$valeurs['texte'] = $reponse['texte'];
			$valeurs['id_rubrique'] = $reponse['id_rubrique'];
			$valeurs['id_consigne'] = $reponse['id_consigne'];
		}
	}
	return $valeurs;
}

function formulaires_public_publier_article_verifier_dist($id_rubrique, $id_consigne = 0) {
	include_spip('inc/editer');
	include_spip('prive/formulaires/editer_article');

	$erreurs = formulaires_editer_objet_verifier('article', 'new', ['titre', 'texte']);
	$max_caracteres = 50;
	if (empty($erreurs['titre']) && strlen(_request('titre')) > $max_caracteres) {
		$erreurs['titre'] = _T('thematique:titre_trop_long', ['max' => $max_caracteres]);
	}
	return $erreurs;
}

function formulaires_public_publier_article_traiter_dist($id_rubrique, $type_article, $id_consigne = 0) {
	include_spip('inc/editer');
	include_spip('prive/formulaires/editer_article');

	// Si une réponse existe déjà, id_article est transmis par le formulaire.
	// Sinon, on crée un nouvel article.
	$id_article = intval(_request('id_article'));

	if (!$id_article) {
		$id_article = 'new';
	}

	$res = formulaires_editer_objet_traiter('article', $id_article, $id_rubrique);

	if (empty($res['erreurs']) && !empty($res['id_article'])) {

		$id_article = $res['id_article'];

		// Les documents joints via #FORMULAIRE_JOINDRE_DOCUMENT (sidebar-etape-2-container,
		// cf public_publier_article.html) sont déjà en base à ce stade — soit
		// liés directement à $id_article (réponse à consigne existante), soit
		// à l'id_objet temporaire -id_auteur (nouvel article) : dans ce
		// second cas ils sont automatiquement réassociés à $id_article par le
		// pipeline post_insertion du plugin medias
		// (cf medias_post_insertion() dans plugins-dist/medias/medias_pipelines.php),
		// déclenché par formulaires_editer_objet_traiter() ci-dessus. Rien à
		// faire ici.

		// Si c'est une réponse à une consigne,
		// associer l'article à la consigne.
		if ($id_consigne) {
			include_spip('action/editer_objet');
			objet_modifier('article', $id_article, [
				'id_consigne' => intval($id_consigne),
			]);
		}

		// Publier l'article
		article_instituer($id_article, [
			'statut' => 'publie',
		]);

		$res['message_ok'] = _T('thematique:article_publie_succes');

		$res['redirect'] = generer_url_public('article', 'id_article=' . $id_article . '&mode=complet');
	}

	return $res;
}
