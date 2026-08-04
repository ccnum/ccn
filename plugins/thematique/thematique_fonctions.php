<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}
/**
 * FONCTIONS
 **/
function filtre_nb2col($nb) {
	return substr($nb, spip_strlen((int) $nb) - 1, 1);
}

/**
 * Classes CSS du conteneur externe d'un bloc "rubrique/classe" du menu de
 * navigation (cf noisettes/rubrique.html), selon que la rubrique est de
 * type travail_en_cours ou non. Le calcul du booléen ($est_travail_en_cours)
 * reste dans le squelette (=={xxx} ou match{xxx} selon l'appel d'origine),
 * seule la construction de la chaîne de classes est mutualisée ici.
 *
 * @param bool $est_travail_en_cours
 * @return string
 */
function thematique_classe_bloc_rubrique_menu_externe($est_travail_en_cours) {
	return 'sidebar_bubble ressources_classes_around '
		. ($est_travail_en_cours ? 'ressources_travail_en_cours' : 'ressources_no_color');
}

/**
 * Classes CSS du conteneur interne d'un bloc "rubrique/classe" du menu de
 * navigation — voir thematique_classe_bloc_rubrique_menu_externe().
 *
 * @param bool $est_travail_en_cours
 * @param int $id_rubrique
 * @return string
 */
function thematique_classe_bloc_rubrique_menu_interne($est_travail_en_cours, $id_rubrique) {
	return ($est_travail_en_cours ? 'bgc_classe_' . filtre_nb2col($id_rubrique) . ' ' : '') . 'ressources_classes';
}

/**
 * Faut-il mettre en avant la rubrique de l'utilisateur en premier dans le
 * menu de navigation de la sidebar (cf noisettes/rubrique.html) : seulement
 * pour un prof, et pas sur la rubrique "evenements" (blog pédagogique).
 *
 * @param string $motcle Mot-clé de la rubrique de l'utilisateur (#SESSION{idRubriqueUser})
 * @param string $role
 * @return bool
 */
function thematique_afficher_rubrique_utilisateur_prof($motcle, $role) {
	return $role === 'prof' && $motcle !== 'evenements';
}

/**
 * Année scolaire courante (cookie/GET, cf plugins/ccn/ccn_options.php).
 *
 * Doit rester dans ce fichier _fonctions.php (auto-inclus à chaque appel),
 * pas dans _pipelines.php : le pipeline pre_boucle interpole l'appel à cette
 * fonction en dur dans le squelette compilé (pour qu'elle soit réévaluée à
 * chaque requête), et ce squelette compilé s'exécute sans que _pipelines.php
 * soit forcément rechargé.
 */
function thematique_annee_scolaire() {
	static $annee_scolaire = null;
	if ($annee_scolaire === null) {
		$annee_scolaire = intval(constant('_ANNEE_SCOLAIRE'));
	}
	return $annee_scolaire;
}

/**
 * Année scolaire réelle (calendaire), indépendante du cookie/GET de
 * sélection d'année (cf plugins/ccn/ccn_options.php). Sert à distinguer
 * l'année scolaire réellement en cours d'une année archivée consultée
 * via le sélecteur du menu haut.
 */
function thematique_annee_scolaire_reelle() {
	if (intval(date('m')) >= 9) {
		return intval(date('Y'));
	}
	return intval(date('Y')) - 1;
}

function balise_ANNEE_SCOLAIRE_REELLE_dist($p) {
	$p->code = 'thematique_annee_scolaire_reelle()';
	return $p;
}

/**
 * Cherche une rubrique par titre sous un parent, la crée (publiée) si absente.
 *
 * @param string $nom
 * @param int $id_parent
 * @return int|null
 */
function thematique_trouver_ou_creer_rubrique($nom, $id_parent) {
	if (!$id_parent || empty($nom)) {
		return null;
	}
	$id_rubrique = sql_getfetsel(
		'id_rubrique',
		'spip_rubriques',
		'titre LIKE ' . sql_quote('%' . $nom . '%') . ' AND id_parent=' . intval($id_parent)
	);
	spip_log(
		'userinfo recherche rubrique name=' . $nom . ' id_parent=' . $id_parent . ' => id_rubrique=' . $id_rubrique,
		'cioidc'
	);
	if (!$id_rubrique) {
		include_spip('inc/rubriques');
		$id_rubrique = creer_rubrique_nommee($nom, $id_parent);
		if ($id_rubrique) {
			sql_updateq('spip_rubriques', ['statut' => 'publie'], 'id_rubrique=' . intval($id_rubrique));
			spip_log(
				'userinfo rubrique créée name=' . $nom . ' id_parent=' . $id_parent . ' => id_rubrique=' . $id_rubrique,
				'cioidc'
			);
		}
	}
	return $id_rubrique ?: null;
}

/**
 * Indique si la requête HTTP courante est un appel Ajax (XMLHttpRequest),
 * par opposition à une vraie navigation du navigateur.
 */
function thematique_est_requete_ajax() {
	return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
}

/**
 * Balise #EST_MODE_NOISETTE
 *
 * Retourne 'oui' quand la page ne doit afficher que le fragment
 * (noisette) sans le layout complet (donc sans les scripts du <head>) :
 * - mode=ajax (toujours un fragment)
 * - mode=ajax-detail chargé en Ajax (XHR) : un fragment dans une page déjà initialisée
 *
 * Retourne 'non' pour une vraie navigation (lien direct, rafraîchissement)
 * même en mode=ajax-detail, afin que le layout complet (et donc les scripts,
 * ex. controleurs.js) soit chargé.
 */
function balise_EST_MODE_NOISETTE_dist($p) {
	$p->code = "(_request('mode') === 'ajax' || (_request('mode') === 'ajax-detail' && thematique_est_requete_ajax()) ? 'oui' : 'non')";
	return $p;
}

/**
 * Retourne le profil de navigation de la sidebar
 *
 * @return array
 */
function sidebar_profil() {

	// Pas connecté
	if (!session_get('id_auteur')) {
		return [
			'role' => 'intervenant',
			'restreint' => null,
		];
	}

	$id_auteur = intval(session_get('id_auteur'));
	$statut = session_get('statut');

	// Administrateur complet
	if ($statut === '0minirezo') {
		return [
			'role' => 'admin',
			'restreint' => null,
		];
	}

	// Recherche des rubriques administrées
	$rubriques = sql_allfetsel(
		'id_objet',
		'spip_auteurs_liens',
		['id_auteur=' . $id_auteur, 'objet=' . sql_quote('rubrique')]
	);

	// Aucune rubrique administrée
	if (!$rubriques) {
		return [
			'role' => 'intervenant',
			'restreint' => null,
		];
	}

	// Une seule rubrique → admin restreint
	if (count($rubriques) === 1) {

		return [
			'role' => 'admin_restreint',
			'restreint' => intval($rubriques[0]['id_objet']),
		];
	}

	// Plusieurs rubriques → à adapter selon ta règle métier
	return [
		'role' => 'admin_restreint',
		'restreint' => intval($rubriques[0]['id_objet']),
	];
}

function filtre_sidebar_profil_dist() {
	return sidebar_profil();
}

function thematique_donner_role($id_auteur) {
	if (!$id_auteur) {
		return 'visiteur';
	}

	// cache mémoire (une requête par hit, pas par appel)
	static $cache = [];
	if (isset($cache[$id_auteur])) {
		return $cache[$id_auteur];
	}

	include_spip('base/abstract_sql');
	include_spip('inc/session'); // pour session_get/session_set si besoin

	// PROF : rattaché (via rubriques) à une hiérarchie contenant le mot "travail_en_cours"
	if (thematique_auteur_a_mot_dans_hierarchie($id_auteur, 'travail_en_cours')) {
		$cache[$id_auteur] = 'prof';
		return 'prof';
	}

	// INTERVENANT : idem avec le mot "consignes"
	if (thematique_auteur_a_mot_dans_hierarchie($id_auteur, 'consignes')) {
		$cache[$id_auteur] = 'intervenant';
		return 'intervenant';
	}

	// ADMIN / ELEVE selon statut
	$statut = sql_getfetsel('statut', 'spip_auteurs', 'id_auteur=' . intval($id_auteur));
	if ($statut === '0minirezo') {
		$cache[$id_auteur] = 'admin';
		return 'admin';
	}
	if ($statut === '6forum') {
		$cache[$id_auteur] = 'eleve';
		return 'eleve';
	}

	$cache[$id_auteur] = null;
	return null;
}

/**
 * Fond sidebar à inclure pour une consigne (mission), selon le rôle de session.
 * Par défaut (visiteur non connecté, admin, eleve) : vue "autre",
 * pas de différence apparente entre "autre" et "eleve".
 */
function fond_consigne_pour_role($role) {
	if ($role === 'prof') {
		return 'consigne_pour_classe';
	}
	if ($role === 'intervenant' || $role === 'admin') {
		return 'consigne_pour_intervenant';
	}
	return 'consigne_pour_autre';
}

/**
 * Fond sidebar à inclure pour une réponse à une consigne, selon le rôle de session.
 * Par défaut (visiteur non connecté, prof, eleve) : vue classe.
 */
function fond_reponse_pour_role($role) {
	if ($role === 'intervenant' || $role === 'admin') {
		return 'reponse_pour_intervenant';
	}
	return 'reponse_pour_classe';
}

function thematique_auteur_a_mot_dans_hierarchie($id_auteur, $titre_mot) {
	$rubriques = sql_allfetsel(
		'id_objet',
		'spip_auteurs_liens',
		'id_auteur=' . intval($id_auteur) . " AND objet='rubrique'"
	);
	foreach ($rubriques as $r) {
		// équivalent de ta BOUCLE_hie_rub{tout} + BOUCLE_mot_rub
		if (thematique_hierarchie_a_mot($r['id_objet'], $titre_mot)) {
			return true;
		}
	}
	return false;
}

function thematique_ascendants_rubrique($id_rubrique) {
	static $cache = [];
	$id_rubrique = intval($id_rubrique);

	if (isset($cache[$id_rubrique])) {
		return $cache[$id_rubrique];
	}

	$ids = [];
	$courant = $id_rubrique;
	$securite = 0; // garde-fou anti boucle infinie si arbre corrompu

	while ($courant && $securite < 30) {
		$ids[] = $courant;
		$parent = sql_getfetsel('id_parent', 'spip_rubriques', 'id_rubrique=' . $courant);
		$courant = intval($parent);
		$securite++;
	}

	$cache[$id_rubrique] = $ids;
	return $ids;
}

function thematique_hierarchie_a_mot($id_rubrique, $titre_mot) {
	$ascendants = thematique_ascendants_rubrique($id_rubrique);
	if (!$ascendants) {
		return false;
	}

	static $cache_mot = [];
	if (!isset($cache_mot[$titre_mot])) {
		$cache_mot[$titre_mot] = sql_getfetsel('id_mot', 'spip_mots', 'titre=' . sql_quote($titre_mot));
	}
	$id_mot = $cache_mot[$titre_mot];
	if (!$id_mot) {
		return false; // le mot-clé n'existe même pas
	}

	$id_objet = sql_getfetsel(
		'id_objet',
		'spip_mots_liens',
		'id_mot=' . intval($id_mot)
			. " AND objet='rubrique'"
			. ' AND id_objet IN (' . implode(',', $ascendants) . ')'
	);

	return !empty($id_objet);
}

/**
 * Type de contenu (blogs, evenements, consignes, travail_en_cours,
 * ressources, agora) porté par une rubrique ou l'une de ses ascendantes :
 * le mot-clé le plus proche l'emporte (rubrique elle-même incluse, puis
 * parent, grand-parent, ... jusqu'à la racine).
 *
 * Remplace l'ancien noisettes/fonction/type_objet.html (BOUCLE HIERARCHIE
 * + BOUCLE MOTS imbriquées, non cachées, à chaque inclusion) : même
 * logique de "plus proche l'emporte", mais en une requête par niveau de
 * hiérarchie au lieu de charger toute la table MOTS pour chaque rubrique.
 *
 * @param int $id_rubrique
 * @return string|null
 */
function thematique_type_objet_rubrique($id_rubrique) {
	static $types = ['blogs', 'evenements', 'consignes', 'travail_en_cours', 'ressources', 'agora'];
	static $cache = [];

	$id_rubrique = intval($id_rubrique);
	if (!$id_rubrique) {
		return null;
	}
	if (array_key_exists($id_rubrique, $cache)) {
		return $cache[$id_rubrique];
	}

	foreach (thematique_ascendants_rubrique($id_rubrique) as $id_asc) {
		$titre = sql_getfetsel(
			'mots.titre',
			'spip_mots_liens AS liens INNER JOIN spip_mots AS mots ON liens.id_mot=mots.id_mot',
			'liens.objet=' . sql_quote('rubrique')
				. ' AND liens.id_objet=' . intval($id_asc)
				. ' AND ' . sql_in('mots.titre', $types)
		);
		if ($titre) {
			return $cache[$id_rubrique] = $titre;
		}
	}

	return $cache[$id_rubrique] = null;
}

/**
 * Type de contenu d'un article : "travail_en_cours" s'il répond à une
 * consigne (id_consigne renseigné), sinon celui porté par sa rubrique
 * (cf thematique_type_objet_rubrique).
 *
 * @param int $id_article
 * @return string|null
 */
function thematique_type_objet_article($id_article) {
	$id_article = intval($id_article);
	if (!$id_article) {
		return null;
	}

	$article = sql_fetsel('id_rubrique, id_consigne', 'spip_articles', 'id_article=' . $id_article);
	if (!$article) {
		return null;
	}
	if (!empty($article['id_consigne'])) {
		return 'travail_en_cours';
	}

	return thematique_type_objet_rubrique($article['id_rubrique']);
}

/**
 * Type de contenu porté par la rubrique d'un article syndiqué
 * (cf thematique_type_objet_rubrique).
 *
 * @param int $id_syndic_article
 * @return string|null
 */
function thematique_type_objet_syndic_article($id_syndic_article) {
	$id_syndic_article = intval($id_syndic_article);
	if (!$id_syndic_article) {
		return null;
	}

	$id_rubrique = sql_getfetsel('id_rubrique', 'spip_syndic_articles', 'id_syndic_article=' . $id_syndic_article);
	if (!$id_rubrique) {
		return null;
	}

	return thematique_type_objet_rubrique($id_rubrique);
}

/**
 * Périmètre admin de l'auteur en session : niveau d'administration et
 * rubrique restreinte sélectionnée. Persiste le résultat en session
 * (#SESSION{admin}, #SESSION{restreint}) pour les fonds qui les lisent.
 *
 * admin : 0 = admin total, N>0 = nb de rubriques restreintes administrées,
 * -1 = pas admin, -2 = pas connecté.
 *
 * Remplace l'ancien noisettes/fonction/admin.html (BOUCLE RUBRIQUES
 * auteurs + INCLURE type_objet imbriqué, non cachée, à chaque inclusion).
 *
 * @param int|string|null $id_rubrique_choisie rubrique explicitement
 *   sélectionnée par l'utilisateur (#ENV{rub}), mémorisée en session
 * @return array{admin:int, restreint:int|null}
 */
function thematique_admin_scope($id_rubrique_choisie = null) {
	include_spip('inc/session');

	if ($id_rubrique_choisie) {
		session_set('cookie_rubrique', intval($id_rubrique_choisie));
	}

	$id_auteur = intval(session_get('id_auteur'));
	$statut = session_get('statut');

	$admin = 0;
	$restreint1 = null;

	$rubriques = sql_allfetsel(
		'objets.id_rubrique',
		'spip_auteurs_liens AS liens INNER JOIN spip_rubriques AS objets ON liens.id_objet=objets.id_rubrique',
		'liens.id_auteur=' . $id_auteur
			. ' AND liens.objet=' . sql_quote('rubrique')
			. ' AND objets.id_parent>0',
		'',
		'',
		'50'
	);
	foreach ($rubriques as $rubrique) {
		$id_rub = intval($rubrique['id_rubrique']);
		if (thematique_type_objet_rubrique($id_rub) !== 'evenements') {
			$restreint1 = $id_rub;
		}
		$admin++;
	}

	if (!in_array($statut, ['0minirezo', '1comite'], true)) {
		$admin = -1;
	}
	if (!$statut) {
		$admin = -2;
	}

	$restreint = ($admin === 1 || $admin === 2) ? $restreint1 : null;

	$cookie_rubrique = session_get('cookie_rubrique');
	if (is_numeric($cookie_rubrique) && ($admin > 1 || $admin === 0)) {
		$restreint = intval($cookie_rubrique);
	}

	session_set('restreint', $restreint);
	session_set('admin', $admin);

	return ['admin' => $admin, 'restreint' => $restreint];
}

/**
 * Rang (0, 1, 2, ...) de chaque classe dans l'ordre d'affichage du sommaire,
 * calculé directement en base (même logique que les boucles RUBRIQUES de
 * sommaire.html : rubriques de l'année en cours taguées "travail_en_cours",
 * sinon repli sur toutes les rubriques taguées "travail_en_cours").
 *
 * Volontairement stateless (pas de session) : mis en cache pour la durée de
 * la requête seulement, recalculé identiquement depuis n'importe quelle
 * page, dans n'importe quel ordre de navigation.
 *
 * @return array<int,int> id_rubrique => rang
 */
function thematique_classes_rangs() {
	static $rangs = null;
	if ($rangs !== null) {
		return $rangs;
	}
	$rangs = [];

	$id_mot = sql_getfetsel('id_mot', 'spip_mots', 'titre=' . sql_quote('travail_en_cours'));
	if (!$id_mot) {
		return $rangs;
	}

	$annee_scolaire = thematique_annee_scolaire();
	$id_annee = sql_getfetsel(
		'id_rubrique',
		'spip_rubriques',
		'titre LIKE ' . sql_quote('%' . $annee_scolaire . '%') . ' AND id_parent=0'
	);

	// Alias (r/ml) obligatoires : le préfixage des tables SPIP (spip_ → préfixe
	// réel du site) ne s'applique qu'à la clause SELECT...FROM, jamais à la
	// clause WHERE (cf ecrire/req/mysql.php:_mysql_traite_query) — un
	// spip_mots_liens.xxx dans le WHERE resterait donc littéralement
	// "spip_mots_liens", introuvable une fois le FROM préfixé (erreur SQL
	// "Unknown column").
	$from = ['spip_rubriques AS r', 'spip_mots_liens AS ml'];
	$where = ['ml.id_objet=r.id_rubrique', 'ml.objet=' . sql_quote('rubrique'), 'ml.id_mot=' . intval($id_mot)];
	if ($id_annee) {
		$where[] = 'r.id_parent=' . intval($id_annee);
	}
	$conteneurs = sql_allfetsel('r.id_rubrique', $from, $where);
	$ids_conteneurs = array_column($conteneurs, 'id_rubrique');
	if (!$ids_conteneurs) {
		return $rangs;
	}

	$classes = sql_allfetsel('id_rubrique', 'spip_rubriques', sql_in('id_parent', $ids_conteneurs), '', 'id_rubrique');
	foreach ($classes as $rang => $ligne) {
		$rangs[$ligne['id_rubrique']] = $rang;
	}

	return $rangs;
}

/**
 * Numéro de couleur (0-9) d'une classe : son rang d'affichage (cf
 * thematique_classes_rangs()) modulo le nombre de couleurs/icônes
 * disponibles (cf classe_icone()).
 *
 * @param int $id_rubrique
 * @return string
 */
function classe_numero($id_rubrique) {
	$rang = thematique_classes_rangs()[$id_rubrique] ?? null;
	if ($rang === null) {
		// rubrique inconnue du sommaire (pas une "classe") : repli sur l'id
		return filtre_nb2col($id_rubrique);
	}

	return (string) ($rang % 10);
}

/**
 * Icône (emoji) d'une classe, dérivée de son id_rubrique via classe_numero().
 *
 * @param int $id_rubrique
 * @return string
 */
function classe_icone($id_rubrique) {
	$icones = ['🐝', '🦩', '🦉', '🦔', '🐟', '🐙', '🐜', '🦁', '🦋', '🦊'];
	return $icones[classe_numero($id_rubrique)] ?? '';
}

/**
 * Id de la rubrique de classe d'un auteur (élève ou prof), déduite de la
 * rubrique de classe à laquelle il est lié (cf thematique_cioidc_userinfo,
 * qui rattache l'auteur à sa rubrique de classe via objet_associer).
 * À utiliser avec les filtres classe_icone()/classe_numero() habituels.
 *
 * Restreint explicitement aux rubriques reconnues comme "classe" (cf
 * thematique_classes_rangs()) pour ignorer d'éventuels autres liens
 * d'un prof (blog pédagogique, rubrique de projet).
 *
 * @param int $id_auteur
 * @return int|null
 */
function classe_id_rubrique_auteur($id_auteur) {
	$rangs = thematique_classes_rangs();
	if (!$rangs) {
		return null;
	}

	$id_rubrique = sql_getfetsel(
		'id_objet',
		'spip_auteurs_liens',
		'id_auteur=' . intval($id_auteur) . ' AND objet=' . sql_quote('rubrique') . ' AND ' . sql_in(
			'id_objet',
			array_keys($rangs)
		)
	);

	return $id_rubrique ? (int) $id_rubrique : null;
}

/**
 * Id de la rubrique de classe à utiliser pour une carte de commentaire forum
 * (avec classe_icone()/classe_numero()).
 *
 * Priorité à la classe actuelle de l'auteur (cf classe_id_rubrique_auteur) ;
 * repli sur la rubrique de l'article commenté pour les commentaires
 * d'élèves dont le compte n'a jamais été rattaché à une classe (créé
 * avant l'ajout de ce rattachement dans thematique_cioidc_userinfo, et
 * pas reconnecté depuis).
 *
 * @param array $forum Ligne spip_forum (cf filtre_afficher_forum_arbre())
 * @return int|null
 */
function classe_id_rubrique_forum($forum) {
	$id_rubrique = classe_id_rubrique_auteur($forum['id_auteur'] ?? 0);
	if ($id_rubrique) {
		return $id_rubrique;
	}

	if (($forum['objet'] ?? '') === 'article' && !empty($forum['id_objet'])) {
		$id_rubrique = sql_getfetsel('id_rubrique', 'spip_articles', 'id_article=' . intval($forum['id_objet']));
		if ($id_rubrique) {
			return (int) $id_rubrique;
		}
	}

	return null;
}

/**
 * Id de la rubrique racine de l'année scolaire active (cf choix_rubrique_admin2.html).
 *
 * @return int 0 si non trouvée
 */
function thematique_id_rubrique_annee_active() {
	return (int) sql_getfetsel(
		'id_rubrique',
		'spip_rubriques',
		'titre LIKE ' . sql_quote('%' . thematique_annee_scolaire() . '%') . ' AND id_parent=0'
	);
}

/**
 * Crée (si absente) la structure minimale de l'année scolaire active :
 * rubrique racine + deux sous-rubriques "Travail des classes" (mot-clé
 * travail_en_cours) et "Consignes" (mot-clé consignes) — titres exacts
 * requis par la synchro ENT (cf thematique_pipelines.php, recherche par
 * LIKE sur ces titres).
 *
 * Ne crée jamais "Ressources"/"Agora" : rubriques globales, réutilisées
 * d'année en année (cf xml/projet.html, résolues sans filtre d'année).
 *
 * @return int id de la rubrique "Travail des classes" (0 si échec)
 */
function thematique_assurer_structure_annee() {
	$annee = thematique_annee_scolaire();
	$id_racine = thematique_id_rubrique_annee_active();

	if (!$id_racine) {
		include_spip('inc/rubriques');
		// Format simple ("2026"), pas "2026-2027" : cohérent avec toutes les
		// rubriques années existantes sur les sites CCN (2018, 2019, ..., 2025).
		$titre_racine = (string) $annee;
		$id_racine = creer_rubrique_nommee($titre_racine, 0);
		if (!$id_racine) {
			spip_log(
				"thematique_assurer_structure_annee : échec de création de la rubrique racine '$titre_racine'",
				'thematique' . _LOG_ERREUR
			);
			return 0;
		}
		sql_updateq('spip_rubriques', ['statut' => 'publie'], 'id_rubrique=' . intval($id_racine));
		spip_log("thematique_assurer_structure_annee : rubrique racine '$titre_racine' créée (#$id_racine)", 'thematique');
	}

	$id_travail_classes = thematique_trouver_ou_creer_rubrique('Travail des classes', $id_racine);
	$id_consignes = thematique_trouver_ou_creer_rubrique('Consignes', $id_racine);

	include_spip('action/editer_liens');
	foreach (['travail_en_cours' => $id_travail_classes, 'consignes' => $id_consignes] as $titre_mot => $id_rub) {
		if (!$id_rub) {
			continue;
		}
		$id_mot = sql_getfetsel('id_mot', 'spip_mots', 'titre=' . sql_quote($titre_mot));
		if ($id_mot) {
			objet_associer(['mots' => intval($id_mot)], ['rubriques' => intval($id_rub)]);
		}
	}

	return $id_travail_classes ?: 0;
}

/**
 * Premier intervenant (au sens thematique_donner_role) trouvé sur la
 * branche "consignes" du projet dont fait partie $id_rubrique.
 *
 * @param int $id_rubrique
 * @return int 0 si aucun intervenant trouvé
 */
function thematique_premier_intervenant($id_rubrique) {
	$id_mot_consignes = sql_getfetsel('id_mot', 'spip_mots', "titre='consignes'");
	if (!$id_mot_consignes) {
		return 0;
	}

	$id_secteur = sql_getfetsel('id_secteur', 'spip_rubriques', 'id_rubrique=' . intval($id_rubrique));
	if (!$id_secteur) {
		return 0;
	}

	return intval(sql_getfetsel(
		'lien.id_auteur',
		['spip_auteurs_liens AS lien', 'spip_rubriques AS rub', 'spip_mots_liens AS ml'],
		[
			'lien.id_objet=rub.id_rubrique',
			"lien.objet='rubrique'",
			'rub.id_secteur=' . intval($id_secteur),
			'ml.id_objet=rub.id_rubrique',
			"ml.objet='rubrique'",
			'ml.id_mot=' . intval($id_mot_consignes),
		],
		'',
		'',
		'1'
	));
}

/**
 * Première rubrique enfant de $id_parent taguée du mot-clé $titre_mot.
 *
 * @param int $id_parent
 * @param string $titre_mot
 * @param string $orderby
 * @return int 0 si non trouvée
 */
function thematique_id_rubrique_enfant_a_mot($id_parent, $titre_mot, $orderby = '') {
	if (!$id_parent) {
		return 0;
	}
	$id_mot = sql_getfetsel('id_mot', 'spip_mots', 'titre=' . sql_quote($titre_mot));
	if (!$id_mot) {
		return 0;
	}

	// Alias (r/ml) obligatoires, cf thematique_classes_rangs().
	return (int) sql_getfetsel(
		'r.id_rubrique',
		['spip_rubriques AS r', 'spip_mots_liens AS ml'],
		[
			'ml.id_objet=r.id_rubrique',
			'ml.objet=' . sql_quote('rubrique'),
			'ml.id_mot=' . intval($id_mot),
			'r.id_parent=' . intval($id_parent),
		],
		'',
		$orderby,
		'0,1'
	);
}

/**
 * Ids des rubriques racine (id_parent=0) portant un mot-clé donné.
 *
 * Remplace un chaînage BOUCLE(RUBRIQUES){racine}{titre_mot=xxx} par un
 * tableau résolu en PHP, pour aplatir les boucles imbriquées des flux
 * d'activité (cf noisettes/inc/actus_timeline.html).
 *
 * @param string $titre_mot
 * @return int[]
 */
function thematique_ids_rubriques_racine_a_mot($titre_mot) {
	$id_mot = sql_getfetsel('id_mot', 'spip_mots', 'titre=' . sql_quote($titre_mot));
	if (!$id_mot) {
		return [];
	}

	$rows = sql_allfetsel(
		'r.id_rubrique',
		['spip_rubriques AS r', 'spip_mots_liens AS ml'],
		[
			'ml.id_objet=r.id_rubrique',
			'ml.objet=' . sql_quote('rubrique'),
			'ml.id_mot=' . intval($id_mot),
			'r.id_parent=0',
		]
	);
	return array_map('intval', array_column($rows, 'id_rubrique'));
}

/**
 * Rubrique racine (id_parent=0) portant un mot-clé donné (la première si
 * plusieurs) — variante singulière de thematique_ids_rubriques_racine_a_mot(),
 * pour les mots-clés supposés uniques (ex: "ressources").
 *
 * @param string $titre_mot
 * @return int 0 si non trouvée
 */
function thematique_id_rubrique_racine_a_mot($titre_mot) {
	$ids = thematique_ids_rubriques_racine_a_mot($titre_mot);
	return $ids ? $ids[0] : 0;
}

/**
 * Ids des rubriques enfants directes d'une rubrique, triées par date
 * décroissante et limitées — remplace une BOUCLE(RUBRIQUES){id_parent}
 * {!par date}{0,N} imbriquée par un tableau résolu en PHP (cf
 * noisettes/inc/actus_timeline.html).
 *
 * @param int $id_parent
 * @param int $limite 0 = pas de limite
 * @return int[]
 */
function thematique_ids_rubriques_enfants($id_parent, $limite = 0) {
	if (!$id_parent) {
		return [];
	}
	$rows = sql_allfetsel(
		'id_rubrique',
		'spip_rubriques',
		'id_parent=' . intval($id_parent),
		'',
		'date DESC',
		$limite ? '0,' . intval($limite) : ''
	);
	return array_map('intval', array_column($rows, 'id_rubrique'));
}

/**
 * Rubrique "classe en cours de travail" par défaut pour l'année active :
 * repli pour idRubriqueUser quand l'utilisateur n'a pas de rubrique
 * sélectionnée (cf choix_rubrique_admin2.html, ex BOUCLE_filtreTravailEnCours).
 *
 * @return int 0 si non trouvée
 */
function thematique_id_rubrique_travail_en_cours() {
	return thematique_id_rubrique_enfant_a_mot(thematique_id_rubrique_annee_active(), 'travail_en_cours', 'r.date');
}

/**
 * Mémorise en session la rubrique "courante" de l'utilisateur (utilisée pour
 * surligner la classe active dans le menu, cf rubrique.html) : la rubrique
 * explicitement sélectionnée ($restreint), sinon la classe en cours de
 * travail par défaut de l'année active.
 *
 * @param int|string $restreint
 * @return int la valeur mémorisée
 */
function thematique_set_id_rubrique_user($restreint) {
	include_spip('inc/session');
	$id_rubrique_user = $restreint ? intval($restreint) : thematique_id_rubrique_travail_en_cours();
	session_set('idRubriqueUser', $id_rubrique_user);
	return $id_rubrique_user;
}

/**
 * Rubrique de l'intervenant sous "Consignes", pour l'année active. Sert de
 * repli pour créer une mission quand l'utilisateur (ex: webmaster) n'a pas
 * de rubrique restreinte (cf choix_rubrique_admin2.html).
 *
 * @return int 0 si non trouvée
 */
function thematique_id_rubrique_mission() {
	static $id_rubrique_mission = null;
	if ($id_rubrique_mission !== null) {
		return $id_rubrique_mission;
	}

	$id_consignes = thematique_id_rubrique_enfant_a_mot(thematique_id_rubrique_annee_active(), 'consignes');
	$id_rubrique_mission = $id_consignes
		? (int) sql_getfetsel('id_rubrique', 'spip_rubriques', 'id_parent=' . intval($id_consignes))
		: 0;

	return $id_rubrique_mission;
}

/**
 * Est-ce que le menu "Publier > Une nouvelle mission" doit être proposé à
 * l'utilisateur connecté : admin, ou intervenant avec au moins une rubrique
 * restreinte (cf choix_rubrique_admin2.html).
 *
 * @return string 'oui'|'non'
 */
function thematique_voir_mission() {
	include_spip('inc/session');
	$role = session_get('role');
	$admin = session_get('admin');

	if ($role === 'admin' || ($role === 'intervenant' && $admin > 0)) {
		return 'oui';
	}
	return 'non';
}

function filtre_afficher_forum_arbre($id_article) {
	$forums = sql_allfetsel(
		'*',
		'spip_forum',
		"objet='article' AND id_objet=" . intval($id_article) . ' AND statut=' . sql_quote('publie'),
		'',
		'date_heure'
	);
	if (!$forums) {
		return '';
	}
	// Index des commentaires par parent
	$parents = [];
	foreach ($forums as $forum) {
		$parents[$forum['id_parent']][] = $forum;
	}
	// Construction récursive de l'arbre à partir de la racine
	$arbre = forum_construire_arbre(0, $parents);
	return forum_rendre_branche($arbre);
}

function forum_construire_arbre($id_parent, &$parents) {
	if (!isset($parents[$id_parent])) {
		return [];
	}
	$res = [];
	foreach ($parents[$id_parent] as $forum) {
		$forum['reponses'] = forum_construire_arbre($forum['id_forum'], $parents);
		$res[] = $forum;
	}
	return $res;
}

function forum_rendre_branche($forums) {
	$html = '';
	foreach ($forums as $forum) {
		$html .= recuperer_fond('noisettes/inc/forumv2/forum_commentaire_et_ses_reponses', [
			'forum' => $forum,
		]);
	}
	return $html;
}

// cioidc_session() résout le compte qui recevra réellement la session par login=uid
// (uid_champ_spip='login', cf. cioidc_verifier_identifiant()) : on doit chercher avec
// le même critère en priorité, sinon on met à jour un autre compte (ex: un doublon
// historique retrouvé par email) que celui qui sera effectivement connecté.
function thematique_cioidc_resoudre_auteur($uid, $email) {
	$auteur = $uid ? sql_fetsel('id_auteur,nom,statut,email,webmestre', 'spip_auteurs', 'login=' . sql_quote($uid)) : null;
	if (!$auteur) {
		$auteur = sql_fetsel('id_auteur,nom,statut,email,webmestre', 'spip_auteurs', 'email=' . sql_quote($email));
	}
	return $auteur;
}

// Si $valeur diffère du champ actuel de l'auteur, met à jour spip_auteurs et logue le
// changement. Factorise le motif commun aux mises à jour d'email/nom/statut ci-dessous.
function thematique_cioidc_maj_champ(array $auteur, string $champ, $valeur, string $libelle_log) {
	if ($valeur && $valeur !== $auteur[$champ]) {
		spip_log('userinfo mise à jour ' . $libelle_log . ' : ' . $auteur[$champ] . ' => ' . $valeur, 'cioidc');
		sql_updateq('spip_auteurs', [$champ => $valeur], 'id_auteur=' . intval($auteur['id_auteur']));
		$auteur[$champ] = $valeur;
	}
	return $auteur;
}

// Normalise un attribut CAS multivalué : arrive en objet unique (pas en tableau)
// quand il n'y a qu'une seule valeur.
function thematique_cioidc_normaliser_liste($valeur) {
	if (is_object($valeur)) {
		return [$valeur];
	}
	return $valeur ?: [];
}

// ENTClassesGroupes (member_type=ENS, group_type=CLS) : la/les classe(s) réellement
// enseignée(s) par ce prof, utilisée pour affiner son nom affiché et pour le lien
// "Travail des classes". Absent chez un intervenant qui n'a pas de classe en charge,
// seulement un groupe projet (ENTGroupesLibres).
function thematique_cioidc_classes_reelles(array $classes_groupes) {
	return array_values(array_filter(
		$classes_groupes,
		fn ($groupe) => ($groupe->group_type ?? '') === 'CLS' && !empty($groupe->group_name)
	));
}

// Les comptes ENS rattachés à un établissement listé dans _THEMATIQUE_RNE_WEBMESTRES
// (ex: établissement pilote de l'équipe projet) restent administrateurs complets
// plutôt que rédacteurs, sans restriction de rubrique. ENTAllUai liste tous les UAI
// de rattachement (pas seulement le principal).
function thematique_cioidc_est_webmestre($uai_liste, bool $is_enseignant) {
	$uai_liste = array_map('strval', (array) thematique_cioidc_normaliser_liste($uai_liste));
	$rne_webmestres = array_filter(array_map('trim', explode(',', _THEMATIQUE_RNE_WEBMESTRES)));
	$is_webmestre = $is_enseignant && (bool) array_intersect($uai_liste, $rne_webmestres);
	spip_log(
		'userinfo ENTAllUai=' . implode(',', $uai_liste) . ' => webmestre:' . ($is_webmestre ? 'oui' : 'non'),
		'cioidc'
	);
	return $is_webmestre;
}

// Rôle ENT affiché (Enseignant/Tuteur/Élève), remplacé par "Admin" pour un webmestre :
// affiché ci-dessous à la suite du nom plutôt que le rôle ENT d'origine.
function thematique_cioidc_role_affiche(string $profils, bool $is_webmestre) {
	if ($is_webmestre) {
		return 'Admin';
	}
	$roles_ent = ['ENS' => 'Enseignant', 'TUT' => 'Tuteur', 'ELV' => 'Élève'];
	foreach ($roles_ent as $code => $libelle) {
		if (strpos($profils, $code) !== false) {
			return $libelle;
		}
	}
	return null;
}

// Nom affiché : prénom/nom ENT, suivi du rôle ENT et de la classe (première classe
// réelle du prof) pour distinguer dans la liste des auteurs un même prof intervenant
// sur plusieurs CCN (cf issue #44). Le group_name reçu de l'ENT est préfixé par
// "CCN - " : préfixe redondant qu'on retire.
function thematique_cioidc_nom_affiche(array $flux_data, array $classes_reelles, ?string $role_ent) {
	$prenom = $flux_data['LaclassePrenom'] ?? '';
	$nom_famille = $flux_data['LaclasseNom'] ?? '';
	$nom = trim($prenom . ' ' . $nom_famille);
	if ($nom && $role_ent) {
		$nom .= ' - ' . $role_ent;
	}
	if ($nom && ($groupe_classe = $classes_reelles[0]->group_name ?? null)) {
		if (stripos($groupe_classe, 'CCN - ') === 0) {
			$groupe_classe = substr($groupe_classe, strlen('CCN - '));
		}
		$nom .= ' - ' . $groupe_classe;
	}
	return $nom;
}

// Résout le secteur de l'année scolaire en cours (ex: "2025"), et sous ce secteur les
// rubriques "Travail des classes" (rôle prof) et "Consignes" (rôle intervenant).
function thematique_cioidc_rubriques_annee(string $annee_scolaire) {
	$id_secteur = sql_getfetsel(
		'id_rubrique',
		'spip_rubriques',
		'titre LIKE ' . sql_quote('%' . $annee_scolaire . '%') . ' AND id_parent=0'
	);
	spip_log('userinfo annee_scolaire=' . $annee_scolaire . ' id_secteur=' . $id_secteur, 'cioidc');

	$id_travail_classes = null;
	$id_consignes = null;
	if ($id_secteur) {
		$id_travail_classes = sql_getfetsel(
			'id_rubrique',
			'spip_rubriques',
			'titre LIKE ' . sql_quote('%Travail des classes%') . ' AND id_secteur=' . intval($id_secteur)
		);
		$id_consignes = sql_getfetsel(
			'id_rubrique',
			'spip_rubriques',
			'titre LIKE ' . sql_quote('%Consignes%') . ' AND id_secteur=' . intval($id_secteur)
		);
	}
	spip_log('userinfo id_travail_classes=' . $id_travail_classes . ' id_consignes=' . $id_consignes, 'cioidc');

	return [$id_secteur, $id_travail_classes, $id_consignes];
}

// ENTGroupesLibres : le groupe projet (ex: nom de l'intervenant/du binôme) → lien
// "Consignes". Un même compte ENT peut porter des groupes de plusieurs
// thématiques/années ("Textile 2023", "On tourne 2025", ...) : seuls ceux de la
// thématique de CE site (meta nom_site) et de l'année scolaire en cours sont
// pertinents ici.
function thematique_cioidc_groupes_libres_pertinents(array $groupes_libres, string $nom_site, string $annee_scolaire) {
	if (!$nom_site) {
		return [];
	}
	$pertinents = [];
	foreach ($groupes_libres as $groupe) {
		$nom_groupe = $groupe->name ?? '';
		if (
			$nom_groupe
			&& stripos($nom_groupe, $nom_site) !== false
			&& strpos($nom_groupe, $annee_scolaire) !== false
		) {
			$pertinents[] = $groupe;
		}
	}
	return $pertinents;
}

// Statut SPIP selon le rôle ENT. Un enseignant sans classe réelle ni groupe projet
// pertinent pour ce site/cette année reste simple visiteur ('6forum') : pas de droit
// de rédaction tant qu'il n'est pas effectivement affecté à quelque chose ici.
function thematique_cioidc_calculer_statut(bool $is_webmestre, string $profils, bool $a_un_groupe_pertinent) {
	if ($is_webmestre) {
		return '0minirezo';
	}
	if (strpos($profils, 'ELV') !== false) {
		return '6forum';
	}
	if (strpos($profils, 'ENS') !== false) {
		return $a_un_groupe_pertinent ? '1comite' : '6forum';
	}
	return null;
}

// Sur un CCN archivé (_CCN_PROJET_ACTIVE=false), seuls les comptes webmestre peuvent
// se connecter via l'ENT/OIDC. Vérifié seulement après la promotion webmestre (dans
// l'appelant) : un webmestre qui se connecte pour la première fois sur ce site précis
// n'a pas encore 'oui' en base avant ce point (chaque CCN a sa propre table
// spip_auteurs), il serait bloqué à tort si on testait plus tôt. cioidc_session()
// (plugins/cioidc/inc/cioidc_session.php) ouvre la session juste après ce pipeline :
// rediriger ici empêche la session de s'ouvrir, sans avoir à toucher au plugin tiers
// cioidc.
function thematique_cioidc_bloquer_si_archive(array $auteur) {
	if (
		defined('_CCN_PROJET_ACTIVE') && !_CCN_PROJET_ACTIVE
		&& ($auteur['webmestre'] ?? 'non') !== 'oui'
	) {
		spip_log('userinfo connexion refusée (CCN archivé, non-webmestre) id=' . $auteur['id_auteur'], 'cioidc');
		include_spip('inc/headers');
		redirige_par_entete(generer_url_public('cioidc_erreur_archive'));
	}
}

// Rubriques de classe/projet à lier à l'auteur : rôle prof (classes réelles + groupes
// projet pertinents) ou rôle élève (même classe que son prof, même recherche/création,
// pour rattacher l'élève à sa classe à son tour, ex: affichage de l'emoji de classe
// hors du contexte d'une rubrique).
function thematique_cioidc_resoudre_liens_rubriques(
	bool $is_enseignant,
	bool $is_webmestre,
	bool $is_eleve,
	array $classes_reelles,
	array $groupes_libres_pertinents,
	$id_travail_classes,
	$id_consignes
) {
	$classes_a_lier = [];
	$projets_a_lier = [];

	if ($is_enseignant && !$is_webmestre) {
		foreach ($classes_reelles as $groupe) {
			if ($id_classe = thematique_trouver_ou_creer_rubrique($groupe->group_name, $id_travail_classes)) {
				$classes_a_lier[] = $id_classe;
			}
		}
		foreach ($groupes_libres_pertinents as $groupe) {
			if ($id_projet = thematique_trouver_ou_creer_rubrique($groupe->name ?? '', $id_consignes)) {
				$projets_a_lier[] = $id_projet;
			}
		}
	} elseif ($is_eleve) {
		foreach ($classes_reelles as $groupe) {
			if ($id_classe = thematique_trouver_ou_creer_rubrique($groupe->group_name, $id_travail_classes)) {
				$classes_a_lier[] = $id_classe;
			}
		}
	}

	return [$classes_a_lier, $projets_a_lier];
}

// Associe effectivement l'auteur aux rubriques résolues ci-dessus (+ le blog
// pédagogique commun à tous les profs).
function thematique_cioidc_associer_rubriques(
	array $auteur,
	bool $is_enseignant,
	bool $is_webmestre,
	bool $is_eleve,
	array $classes_a_lier,
	array $projets_a_lier
) {
	if ($is_enseignant && !$is_webmestre) {
		$blog = sql_getfetsel('id_rubrique', 'spip_rubriques', 'titre = ' . sql_quote('Blog pédagogique'));
		if ($blog) {
			objet_associer(['id_auteur' => $auteur['id_auteur']], ['rubrique' => $blog]);
		}
		foreach ($projets_a_lier as $id_projet) {
			objet_associer(['id_auteur' => $auteur['id_auteur']], ['rubrique' => $id_projet]);
		}
	}
	if (($is_enseignant && !$is_webmestre) || $is_eleve) {
		foreach ($classes_a_lier as $id_classe) {
			objet_associer(['id_auteur' => $auteur['id_auteur']], ['rubrique' => $id_classe]);
		}
	}
}
