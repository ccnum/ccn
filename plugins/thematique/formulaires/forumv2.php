<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Vérifie que le texte du commentaire n'est pas vide.
 */
function forumv2_texte_est_valide() {
	return trim(_request('texte')) !== '';
}

/**
 * Charge le formulaire.
 *
 * Modes :
 * - publication : création d'un nouveau commentaire
 * - edition : modification d'un commentaire existant
 */
function formulaires_forumv2_charger_dist($id_article) {
	$publication_forum_action = _request('publication_forum_action');
	$id_forum = intval(_request('id_forum'));
	$id_parent = intval(_request('id_parent'));

	$affichage = 'redaction';
	$texte = _request('texte');

	/**
	 * En mode édition, si aucun texte n'a encore été envoyé,
	 * on charge le texte actuel du commentaire.
	 */
	if ($id_forum && !$texte) {
		$forum = sql_fetsel(
			'id_forum, id_article, id_parent, texte',
			'spip_forum',
			'id_forum=' . intval($id_forum)
		);

		if (!$forum) {
			return [
				'id_article' => $id_article,
				'id_forum' => $id_forum,
				'id_parent' => $id_parent,
				'texte' => '',
				'affichage' => 'redaction',
				'edition' => true,
				'commentaire_introuvable' => true,
			];
		}

		/**
		 * Vérification que le commentaire appartient bien
		 * à l'article affiché.
		 */
		if (intval($forum['id_article']) !== intval($id_article)) {
			return [
				'id_article' => $id_article,
				'id_forum' => $id_forum,
				'id_parent' => $id_parent,
				'texte' => '',
				'affichage' => 'redaction',
				'edition' => true,
				'commentaire_introuvable' => true,
			];
		}

		$texte = $forum['texte'];
		$id_parent = intval($forum['id_parent']);
	}

	/**
	 * Prévisualisation.
	 */
	if (
		$publication_forum_action === 'previsualiser'
		&& forumv2_texte_est_valide()
	) {
		$affichage = 'previsualisation';
	}

	return [
		'id_article' => $id_article,
		'id_forum' => $id_forum,
		'id_parent' => $id_parent,
		'texte' => $texte,
		'affichage' => $affichage,
		'edition' => ($id_forum > 0),
	];
}

/**
 * Vérification du formulaire.
 */
function formulaires_forumv2_verifier_dist($id_article) {
	$erreurs = [];

	$id_auteur = $GLOBALS['visiteur_session']['id_auteur'] ?? 0;

	if (!$id_auteur) {
		$erreurs['message_erreur'] = _T('info_acces_interdit');
		return $erreurs;
	}

	if (!forumv2_texte_est_valide()) {
		$erreurs['texte'] = 'Le texte est obligatoire.';
	}

	/**
	 * En mode édition, vérifier que le commentaire existe
	 * et que l'utilisateur connecté en est bien l'auteur.
	 */
	$id_forum = intval(_request('id_forum'));

	if ($id_forum) {
		$forum = sql_fetsel(
			'id_forum, id_objet, id_auteur',
			'spip_forum',
			'id_forum=' . intval($id_forum) . " AND objet='article'"
		);


		if (!$forum) {
			$erreurs['message_erreur'] = 'Ce commentaire n’existe pas.';
			return $erreurs;
		}

		if (intval($forum['id_objet']) !== intval($id_article)) {
			$erreurs['message_erreur'] = 'Ce commentaire n’appartient pas à cet article.';
			return $erreurs;
		}

		if (intval($forum['id_auteur']) !== intval($id_auteur)) {
			$erreurs['message_erreur'] = _T('info_acces_interdit');
			return $erreurs;
		}
	}

	return $erreurs;
}

/**
 * Traitement du formulaire.
 */
function formulaires_forumv2_traiter_dist($id_article) {
	include_spip('action/editer_forum');
	include_spip('inc/session');

	if (_request('publication_forum_action') === 'previsualiser') {
		return [
			'affichage' => 'previsualisation',
			'texte' => _request('texte'),
		];
	}

	if (_request('publication_forum_action') === 'publier') {
		$id_forum = intval(_request('id_forum'));
		if ($id_forum) {
			sql_updateq('spip_forum', ['texte' => _request('texte')], 'id_forum=' . $id_forum);
		} else {
			$id_parent = intval(_request('id_parent'));
			$id_auteur = $GLOBALS['visiteur_session']['id_auteur'] ?? 0;
			$id_forum = forum_inserer(
				$id_parent,
				[
					'objet' => 'article',
					'id_objet' => $id_article,
					'texte' => _request('texte'),
					'auteur' => _request('nom_auteur'),
					'statut' => 'publie',
				]
			);
			session_set('forum_commentaire_succes', $id_forum);
		}
		return [
			'redirect' => generer_url_public('article', [
				'id_article' => $id_article,
				'mode' => 'complet',
			]),
		];
	}

	return [];
}