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

function formulaires_public_publier_article_charger_dist(
	$id_rubrique,
	$type_article,
	$id_consigne = 0,
	$id_article = 0
) {
	$valeurs = [
		'id_rubrique' => $id_rubrique,
		'id_parent' => $id_rubrique,
		'id_consigne' => $id_consigne,
		'type_article' => $type_article,
		'id_article' => 0,
		'titre' => '',
		'texte' => '',
		'date' => date('Y-m-d'),
	];

	// Édition directe d'un article déjà publié (issue #429 : réutilise la
	// modale de publication pour l'édition, cf le bouton "Modifier" de
	// header_sidebar.html/header_blog.html) : #ID_ARTICLE prévaut sur
	// #ID_CONSIGNE, non pertinent dans ce cas (on n'édite pas une réponse à
	// une consigne mais l'article lui-même, quel que soit son type).
	//
	// autoriser('modifier',...) : mêmes règles que le crayon #EDIT{...}
	// (cf thematique_autoriser.php) - un accès direct à cette URL sans
	// autorisation retombe silencieusement sur le formulaire de création
	// vierge plutôt que d'exposer le contenu de l'article visé.
	//
	// include_spip('inc/autoriser') : ce charger_dist peut être atteint via
	// le squelette ajax-detail de la page publier (callModifierArticle,
	// controleurs.js) dans un contexte où inc/autoriser n'a pas encore été
	// inclus, contrairement à un #AUTORISER{} compilé en squelette (qui
	// génère lui-même ce garde) - sans ça, autoriser() est une fonction
	// non définie et la requête part en erreur 500/503.
	include_spip('inc/autoriser');
	if ($id_article && autoriser('modifier', 'article', $id_article)) {
		$article = sql_fetsel(
			'id_article, id_rubrique, titre, texte, date',
			'spip_articles',
			'id_article=' . intval($id_article)
		);

		if ($article) {
			$valeurs['id_article'] = $article['id_article'];
			$valeurs['titre'] = $article['titre'];
			$valeurs['texte'] = $article['texte'];
			$valeurs['id_rubrique'] = $article['id_rubrique'];
			$valeurs['id_parent'] = $article['id_rubrique'];
			$valeurs['date'] = $article['date'];
		}
	} elseif ($id_consigne) {
		// Sinon, si on répond à une consigne, chercher une éventuelle
		// réponse existante (édition de sa propre réponse).
		$reponse = thematique_trouver_reponse_a_une_consigne($id_consigne, $id_rubrique);

		if ($reponse) {
			$valeurs['id_article'] = $reponse['id_article'];
			$valeurs['titre'] = $reponse['titre'];
			$valeurs['texte'] = $reponse['texte'];
			$valeurs['id_rubrique'] = $reponse['id_rubrique'];
			$valeurs['id_parent'] = $reponse['id_rubrique'];
			$valeurs['id_consigne'] = $reponse['id_consigne'];
			$valeurs['date'] = $reponse['date'];
		}
	}

	// Champ date affiché/verrouillé selon le rôle (#420) : toujours éditable
	// à la création (pas encore d'id_article), sinon soumis aux mêmes règles
	// que le crayon #EDIT{date} (cf thematique_autoriser.php).
	$valeurs['peut_modifier_date'] = !$valeurs['id_article']
		|| autoriser('modifier', 'article', $valeurs['id_article'], null, ['champ' => 'date']);

	return $valeurs;
}

function formulaires_public_publier_article_verifier_dist($id_rubrique, $id_consigne = 0, $id_article = 0) {
	include_spip('inc/autoriser');
	include_spip('inc/editer');
	include_spip('prive/formulaires/editer_article');

	$id_article_poste = intval(_request('id_article'));

	// cf la même vérification dans le charger : un id_article posté sans
	// autorisation de modification ne doit rien pouvoir écrire.
	if ($id_article_poste && !autoriser('modifier', 'article', $id_article_poste)) {
		return ['message_erreur' => _T('info_acces_interdit')];
	}

	$erreurs = formulaires_editer_objet_verifier('article', $id_article_poste ?: 'new', ['titre', 'texte']);
	$max_caracteres = 50;
	if (empty($erreurs['titre']) && strlen(_request('titre')) > $max_caracteres) {
		$erreurs['titre'] = _T('thematique:titre_trop_long', ['max' => $max_caracteres]);
	}
	return $erreurs;
}

function formulaires_public_publier_article_traiter_dist(
	$id_rubrique,
	$type_article,
	$id_consigne = 0,
	$id_article = 0
) {
	include_spip('inc/session');
	include_spip('inc/autoriser');

	$titre = _request('titre');
	$texte = _request('texte');
	$id_auteur = session_get('id_auteur'); // auteur connecté, vient de la session SPIP

	// Ceci est un système anti-spam : si on appuie plusieurs fois très vite sur "enregistrer un article",
	// on ne l'enregistrera qu'une fois.
	$cle = 'creation_article_' . md5($titre . $texte . $id_rubrique . $type_article . $id_consigne . $id_auteur);
	$derniere = session_get($cle); // timestamp (int) ou null si absent
	if ($derniere && (time() - $derniere) < 3) {
		// soumission dupliquée détectée récemment : on bloque
		return [];
	}
	session_set($cle, time());

	
	include_spip('inc/editer');
	include_spip('prive/formulaires/editer_article');

	// Si une réponse existe déjà, ou qu'on édite un article existant,
	// id_article est transmis par le formulaire. Sinon, on crée un nouvel article.
	$id_article = intval(_request('id_article'));

	$edition = (bool) $id_article;

	if (!$id_article) {
		$id_article = 'new';
	} elseif (!autoriser('modifier', 'article', $id_article, null, ['champ' => 'date'])) {
		// Champ date non autorisé pour ce rôle sur cet article (#420) : même
		// masqué côté squelette, on ne fait pas confiance à un POST forgé -
		// on retire la valeur postée avant qu'action_editer_article ne
		// l'applique telle quelle.
		set_request('date');
	}
	// Les ressources sont créées depuis la page "Ressources" (popup sans
	// id_rubrique, cf callNouvelleRessource) : la rubrique cible est forcée
	// côté serveur. Le core la relit depuis la requête HTTP (cf
	// action_editer_article_dist : _request('id_parent')), le 3e argument de
	// formulaires_editer_objet_traiter() étant ignoré — d'où le set_request.
	if ($type_article == 'ressources') {
		$id_ressources = sql_getfetsel(
			'id_rubrique', 
			'spip_rubriques', 
			'titre=' . sql_quote('Ressources')
		);
		if ($id_ressources) {
			$id_rubrique = $id_ressources;
			set_request('id_parent', $id_rubrique);
		}
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

		include_spip('action/editer_liens');

		// Lier l'auteur connecté à l'article (source = auteur, car auteur est associable)
		if ($id_auteur) {
			objet_associer(['auteur' => $id_auteur], ['article' => $id_article]);
		}
		// Si c'est une réponse à une consigne,
		// associer l'article à la consigne.
		if ($id_consigne) {
			include_spip('action/editer_objet');
			objet_modifier('article', $id_article, [
				'id_consigne' => intval($id_consigne),
			]);
		}

		// Publier l'article (sans effet si déjà publié : cas d'une édition).
		// article_instituer() exige 'publierdans' sur la rubrique de
		// l'article (cf ecrire/action/editer_article.php) : les admins
		// restreints (statut 0minirezo, liés à leurs rubriques via
		// spip_auteurs_liens objet='rubrique') ne l'ont donc pas sur une
		// rubrique hors de leurs restrictions (ex. la rubrique globale
		// "Ressources") et l'article
		// resterait en 'prepa', invisible du front — sans aucun message
		// d'erreur. L'accès à ce formulaire est déjà borné aux rôles
		// "publie" (thematique_role_publie, cf le menu "Publier" du header),
		// la création étant elle-même contrôlée par 'creerarticledans' dans
		// action_editer_article : on accorde donc l'autorisation
		// exceptionnelle pour le hit courant.
		$id_rubrique_article = sql_getfetsel(
			'id_rubrique',
			'spip_articles',
			'id_article=' . intval($id_article)
		);
		if ($id_rubrique_article) {
			autoriser_exception('publierdans', 'rubrique', $id_rubrique_article, true);
		}
		article_instituer($id_article, [
			'statut' => 'publie',
		]);

		// article_instituer() refuse silencieusement (un simple spip_log en
		// 'editer_article X refus ...') : vérifier le statut final pour que
		// un refus ne laisse pas un article en 'prepa' sans explication.
		$statut_final = sql_getfetsel('statut', 'spip_articles', 'id_article=' . intval($id_article));
		if ($statut_final !== 'publie') {
			spip_log(
				"publication de l'article $id_article refusée (statut restant : " . var_export($statut_final, true) . ")",
				'thematique' . _LOG_ERREUR
			);
		}

		$res['message_ok'] = $edition
			? _T('thematique:article_modifie_succes')
			: _T('thematique:article_publie_succes');

		$res['redirect'] = generer_url_public('article', 'id_article=' . $id_article . '&mode=complet');
	}

	return $res;
}
