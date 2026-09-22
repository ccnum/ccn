<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function thematique_autoriser() {
}

// declarations d'autorisations
// Uniquement des fonctions courtes ici théoriquement
function autoriser_thematique_creer_onglet_dist($faire, $type, $id, $qui, $opt) {
	return autoriser('configurer', 'thematique', $id, $qui, $opt);
}

function autoriser_thematique_configurer_dist($faire, $type, $id, $qui, $opt) {
	return autoriser('webmestre', $type, $id, $qui, $opt);
}

/**
 * Restreint la création d'article dans une rubrique (issue #274) : le cœur
 * SPIP (autoriser_rubrique_creerarticledans_dist) se contente de vérifier
 * que la rubrique est "visible" — sans plugin de restriction par branche
 * installé, autoriser('voir','rubrique',...) est vrai pour tout le monde
 * (cf autoriser_voir_dist) : n'importe quel compte 1comite (prof,
 * intervenant) pouvait donc créer un article dans N'IMPORTE QUELLE
 * rubrique du site, pas seulement la sienne — le formulaire public de
 * publication (formulaires/public_publier_article.php) transmet un
 * id_rubrique posté en HTTP, contraint seulement visuellement côté
 * squelette (choix des boutons affichés), jamais revérifié côté serveur.
 *
 * Un admin garde un accès complet (comportement inchangé). Pour les autres
 * rôles, la rubrique ciblée doit être une rubrique à laquelle l'auteur est
 * effectivement lié (spip_auteurs_liens) : sa/ses classe(s), son(ses)
 * projet(s) d'intervenant, le blog pédagogique — même mécanisme que
 * thematique_id_rubrique_classe/_auteur. Exception : la rubrique globale
 * "Ressources", ouverte à tout rédacteur (cf le hack côté serveur qui y
 * force id_rubrique dans public_publier_article.php, indépendamment de la
 * valeur postée).
 *
 * Corollaire : le rattachement d'un auteur à une rubrique via l'ENT
 * (thematique_cioidc_associer_rubriques) donne désormais un vrai droit de
 * création dans cette rubrique — d'où le resserrement du filtrage des
 * "groupes libres" pertinents dans inc/thematique_cioidc.php (même issue).
 *
 * Garde `function_exists` : comme pour `autoriser_article_modifier`
 * ci-dessous, le plugin contrib `autorite` (plugins/spip/autorite, pas
 * maison) déclare conditionnellement (dès que l'option "Auteur modifie
 * article" de sa page de configuration est cochée, meta
 * `$GLOBALS['autorite']['auteur_mod_article']`) sa propre
 * `autoriser_rubrique_creerarticledans()` dans inc/autoriser.php — PHP
 * fatalait (Cannot redeclare) sans ce garde (500 sur tout le site, cf le
 * crash constaté sur ccn-ontourne après le déploiement initial de ce
 * correctif). Sur un tel site, cette fonction-ci est inactive : la
 * restriction #274 est donc appliquée directement dans
 * formulaires/public_publier_article.php (thematique_auteur_peut_creer_-
 * dans_rubrique), qui ne dépend pas du hook autoriser() et s'applique
 * quelle que soit la config d'autorite. Cette fonction reste utile sur les
 * environnements où l'option autorite n'est pas activée (couverture plus
 * large que le seul formulaire de publication).
 */
if (!function_exists('autoriser_rubrique_creerarticledans')) {
	function autoriser_rubrique_creerarticledans($faire, $type, $id, $qui, $opt) {
		if (!autoriser_rubrique_creerarticledans_dist($faire, $type, $id, $qui, $opt)) {
			return false;
		}

		include_spip('thematique_fonctions');
		return thematique_auteur_peut_creer_dans_rubrique($qui['id_auteur'] ?? 0, $id);
	}
} else {
	spip_log(
		'thematique_autoriser : autoriser_rubrique_creerarticledans() déjà déclarée (probablement par le plugin autorite, option "Auteur modifie article") — restriction #274 appliquée uniquement dans public_publier_article.php',
		'thematique' . _LOG_ERREUR
	);
}

/**
 * Restriction sur le champ 'date' d'un article (issue #420, règle actée par
 * ChristoErasme le 09/09) : au-delà de l'autorisation standard de modifier
 * l'article (autoriser_article_modifier_dist()), la date n'est éditable que
 * par :
 * - un admin, sur n'importe quel type de contenu (mission, agenda, salle
 *   des profs, ressource) ;
 * - un intervenant, uniquement sur un évènement (agenda) ou un billet de
 *   salle des profs (blogs) — pas sur une mission (consignes) ni une
 *   ressource.
 * "Formateur canopé" n'a volontairement pas de traitement distinct : rôle
 * fusionné avec "intervenant" (thematique_donner_role() ne le distingue pas
 * — cf discussion #420), mêmes droits que lui pour cette autorisation.
 *
 * Le crayon #EDIT{date} passe systématiquement `champ => 'date'` en option
 * (cf classe_boucle_crayon() et autoriser_crayonner_dist() dans le plugin
 * crayons) : toute autre demande de modification d'article retombe sur le
 * comportement standard, inchangé.
 *
 * Fait suite au commit 3d1639a6 (#420) qui laissait cette restriction "à
 * traiter séparément".
 *
 * Historique du garde `function_exists` : le plugin contrib `autorite`
 * (plugins/spip/autorite, pas maison, désinstallé — issue #274) déclarait
 * lui aussi `autoriser_article_modifier()` en dur (pas de suffixe `_dist`)
 * dans inc/autoriser.php, conditionnellement à sa config stockée en meta
 * (option "auteur peut modifier son article" — `auteur_mod_article`,
 * activée en prod sur ccn-ontourne via docker-entrypoint.sh). PHP ne
 * permettant pas de redéclarer une fonction, cette config activait un
 * `autoriser_article_modifier()` concurrent qui, chargé avant celui-ci,
 * empêchait la déclaration de CETTE fonction (sans le garde
 * `function_exists`, Fatal error "Cannot redeclare" → 500 sur tout le
 * site) — et de toute façon SHADOWAIT silencieusement les restrictions
 * #420/#468/#437 ci-dessous plutôt que de les compléter, tant qu'il gagnait
 * la déclaration. D'où la désinstallation du plugin plutôt que la
 * désactivation de la seule option : la logique qu'il fournissait
 * ("l'auteur peut modifier son propre article même publié", nécessaire aux
 * crayons de ré-édition d'une mission/ressource/évènement déjà publiée) est
 * reprise explicitement ci-dessous, sous les mêmes garde-fous (#437, champ
 * date) que le reste de cette fonction — au lieu de shadower le tout comme
 * le faisait autorite.
 */
if (!function_exists('autoriser_article_modifier')) {
	function autoriser_article_modifier($faire, $type, $id, $qui, $opt) {
		include_spip('thematique_fonctions');

		if (!autoriser_article_modifier_dist($faire, $type, $id, $qui, $opt)) {
			$id_auteur_visiteur = intval($qui['id_auteur'] ?? 0);

			// Issue #468 : les jalons du projet (Cap sur l'année / La Rencontre,
			// cf genie/thematique_rentree_annee.php) sont créés en statut 'prop'
			// et liés au seul "premier intervenant" trouvé sur le projet — la
			// règle SPIP standard (auteurs_objet()) ne laisse alors QUE lui (ou
			// un admin) les éditer. N'importe quel intervenant du projet doit
			// pouvoir les compléter, pas seulement celui assigné à la création.
			$exception_jalon =
				(($opt['statut'] ?? null) === null || !in_array($opt['statut'], ['publie', 'refuse'], true))
				&& thematique_donner_role($id_auteur_visiteur) === 'intervenant'
				&& thematique_article_est_jalon($id);

			// Reprise de l'ancienne option "auteur_mod_article" du plugin
			// autorite (cf docstring ci-dessus) : l'auteur d'un article peut
			// le modifier même une fois publié — sinon un prof/intervenant ne
			// pourrait plus jamais corriger sa mission/ressource/évènement
			// après publication (autoriser_article_modifier_dist() n'autorise
			// l'auteur que sur un article encore en 'prop'/'prepa'/'poubelle',
			// pas 'publie').
			$statut_article = sql_getfetsel('statut', 'spip_articles', 'id_article=' . intval($id));
			$exception_auteur_propre_article =
				$id_auteur_visiteur
				&& $statut_article !== 'refuse'
				&& sql_countsel(
					'spip_auteurs_liens',
					"objet='article' AND id_objet=" . intval($id) . ' AND id_auteur=' . $id_auteur_visiteur
				);

			if (!$exception_jalon && !$exception_auteur_propre_article) {
				return false;
			}
			// on continue, sous les mêmes garde-fous (année passée, champ date) que le cas normal
		}

		// Issue #437 : une fois la nouvelle année scolaire créée, plus
		// personne (admin compris) ne peut modifier un contenu (mission,
		// réponse, événement, billet, ressource) d'une année passée.
		if (thematique_annee_est_passee(thematique_annee_article($id))) {
			return false;
		}

		if (($opt['champ'] ?? null) !== 'date') {
			return true;
		}

		$role = thematique_donner_role(intval($qui['id_auteur'] ?? 0));
		if ($role === 'admin') {
			return true;
		}
		if ($role !== 'intervenant') {
			return false;
		}

		return in_array(thematique_type_objet_article($id), ['evenements', 'blogs'], true);
	}
} else {
	spip_log(
		'thematique_autoriser : autoriser_article_modifier() déjà déclarée (probablement par le plugin autorite) — restriction #420 sur le champ date non appliquée',
		'thematique' . _LOG_ERREUR
	);
}

/**
 * Suppression d'un commentaire de forum (issue #356), règle actée par
 * ChristoErasme le 24/08 :
 * - un élève ne peut jamais supprimer, même son propre message ;
 * - un intervenant ne peut supprimer que ses propres messages ;
 * - un prof peut supprimer ses propres messages, ou ceux d'un élève de sa
 *   classe (comparaison des rubriques classe via thematique_id_rubrique_-
 *   classe_prof/_auteur, pas juste "un élève quelconque") ;
 * - un admin peut tout supprimer.
 *
 * Seule fonction d'autorisation pour cette action : le bouton "supprimer"
 * (article-forum-detail.html, forum_succes.html) teste désormais
 * #AUTORISER{forumsupprimer,...} directement, la même permission que
 * l'action instituer_forum vérifie réellement — plus de double check
 * incohérent entre affichage et action.
 */
function autoriser_forumsupprimer_dist($faire, $type, $id, $qui, $opt) {
	$id_auteur_visiteur = intval($qui['id_auteur'] ?? 0);
	if (!$id_auteur_visiteur) {
		return false;
	}

	include_spip('thematique_fonctions');

	$forum = sql_fetsel('id_auteur, id_objet', 'spip_forum', 'id_forum=' . intval($id) . " AND objet='article'");
	if (!$forum) {
		return false;
	}

	// Issue #437 : plus aucune suppression de commentaire (admin compris)
	// sur un article d'une année scolaire passée, une fois la nouvelle
	// année créée.
	if (thematique_annee_est_passee(thematique_annee_article(intval($forum['id_objet'])))) {
		return false;
	}

	$role_visiteur = thematique_donner_role($id_auteur_visiteur);

	if ($role_visiteur === 'admin') {
		return true;
	}
	if ($role_visiteur === 'eleve') {
		return false;
	}

	$id_auteur_commentaire = intval($forum['id_auteur']);

	// Ses propres messages : toujours autorisé (prof comme intervenant)
	if ($id_auteur_commentaire === $id_auteur_visiteur) {
		return true;
	}

	// Intervenant : uniquement ses propres messages
	if ($role_visiteur === 'intervenant') {
		return false;
	}

	// Prof : le message d'un élève de sa classe uniquement
	if (thematique_donner_role($id_auteur_commentaire) !== 'eleve') {
		return false;
	}

	$id_rubrique_classe_prof = thematique_id_rubrique_classe($id_auteur_visiteur);

	return $id_rubrique_classe_prof
		&& $id_rubrique_classe_prof === thematique_id_rubrique_classe_auteur($id_auteur_commentaire);
}
