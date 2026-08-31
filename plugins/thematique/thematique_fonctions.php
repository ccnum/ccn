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
 * Est-ce que le rôle donné voit les contenus réservés aux adultes
 * (blog pédagogique / "salle des profs") : prof, intervenant, admin.
 */
function thematique_role_voit_salle_profs($role) {
	return in_array($role, ['prof', 'intervenant', 'admin']);
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
 * Type de contenu (blogs, evenements, consignes, travail_en_cours,
 * ressources, agora) porté par l'une des rubriques auxquelles l'auteur est
 * rattaché (spip_auteurs_liens), au sens de thematique_type_objet_rubrique
 * (mot-clé le plus proche l'emporte).
 *
 * Remplace l'ancien squelettes/modeles/type_objet.html (branche id_auteur :
 * BOUCLE auteurs_liens + BOUCLE HIERARCHIE + BOUCLE MOTS imbriquées,
 * #CACHE{0} donc relancées à chaque #MODELE{type_objet}{id_auteur} —
 * un appel par réponse dans un fil de forum).
 *
 * @param int $id_auteur
 * @return string|null
 */
function thematique_type_objet_auteur($id_auteur) {
	static $cache = [];

	$id_auteur = intval($id_auteur);
	if (!$id_auteur) {
		return null;
	}
	if (array_key_exists($id_auteur, $cache)) {
		return $cache[$id_auteur];
	}

	include_spip('base/abstract_sql');

	$rubriques = sql_allfetsel('id_objet', 'spip_auteurs_liens', 'id_auteur=' . $id_auteur . " AND objet='rubrique'");
	foreach ($rubriques as $r) {
		$type = thematique_type_objet_rubrique($r['id_objet']);
		if ($type) {
			return $cache[$id_auteur] = $type;
		}
	}

	return $cache[$id_auteur] = null;
}

/**
 * Type d'"espace" d'un auteur (travail_en_cours, ressources ou consignes)
 * porté par l'une des rubriques auxquelles il est rattaché, au sens le plus
 * proche dans la hiérarchie (rubrique elle-même incluse, puis parent, ...).
 *
 * Remplace l'ancien squelettes/modeles/type_auteur.html (BOUCLE RUBRIQUES
 * auteurs_liens + BOUCLE HIERARCHIE + BOUCLE MOTS imbriquées, #CACHE{0},
 * relancées à chaque #MODELE{type_auteur} — donc à chaque page vue, depuis
 * layout.html).
 *
 * @param int $id_auteur
 * @return string|null
 */
function thematique_type_auteur($id_auteur) {
	static $cache = [];
	static $types = ['travail_en_cours', 'ressources', 'consignes'];

	$id_auteur = intval($id_auteur);
	if (!$id_auteur) {
		return null;
	}
	if (array_key_exists($id_auteur, $cache)) {
		return $cache[$id_auteur];
	}

	include_spip('base/abstract_sql');

	$rubriques = sql_allfetsel('id_objet', 'spip_auteurs_liens', 'id_auteur=' . $id_auteur . " AND objet='rubrique'");
	foreach ($rubriques as $r) {
		foreach (thematique_ascendants_rubrique($r['id_objet']) as $id_asc) {
			$titre = sql_getfetsel(
				'mots.titre',
				'spip_mots_liens AS liens INNER JOIN spip_mots AS mots ON liens.id_mot=mots.id_mot',
				'liens.objet=' . sql_quote('rubrique')
					. ' AND liens.id_objet=' . intval($id_asc)
					. ' AND ' . sql_in('mots.titre', $types)
			);
			if ($titre) {
				return $cache[$id_auteur] = $titre;
			}
		}
	}

	return $cache[$id_auteur] = null;
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
 * Animal (emoji) de la classe d'un prof, pour son avatar dans le menu haut.
 *
 * Un prof est lié (spip_auteurs_liens) non seulement à sa/ses classe(s),
 * mais aussi au blog pédagogique et à ses projets (voir
 * thematique_cioidc_associer_rubriques) : on ne retient donc que le premier
 * lien qui est effectivement une classe (présent dans
 * thematique_classes_rangs()), pas n'importe quelle rubrique liée. S'il a
 * plusieurs classes, la première trouvée fait foi (pas de notion de
 * "classe principale").
 *
 * @param int $id_auteur
 * @return string emoji de la classe, ou '' si aucune classe trouvée
 */
function thematique_avatar_animal($id_auteur) {
	static $cache = [];
	$id_auteur = intval($id_auteur);
	if (isset($cache[$id_auteur])) {
		return $cache[$id_auteur];
	}

	include_spip('base/abstract_sql');
	$rangs = thematique_classes_rangs();
	$rubriques = $id_auteur ? sql_allfetsel(
		'id_objet',
		'spip_auteurs_liens',
		'id_auteur=' . $id_auteur . " AND objet='rubrique'"
	) : [];

	$animal = '';
	foreach ($rubriques as $r) {
		if (isset($rangs[$r['id_objet']])) {
			$animal = classe_icone($r['id_objet']);
			break;
		}
	}

	$cache[$id_auteur] = $animal;
	return $animal;
}

/**
 * Id de rubrique d'un article, mis en cache mémoire par requête.
 *
 * Remplace la branche id_article de l'ancien squelettes/modeles/nb2col.html
 * (BOUCLE ARTICLES non cachée, relancée à chaque #MODELE{nb2col}{id_article}),
 * pour un usage avec classe_numero()/classe_icone() quand seul l'id_article
 * est disponible dans le contexte (ex: noisettes/call_sidebar.html).
 *
 * @param int $id_article
 * @return int 0 si non trouvée
 */
function thematique_id_rubrique_article($id_article) {
	static $cache = [];

	$id_article = intval($id_article);
	if (!$id_article) {
		return 0;
	}
	if (array_key_exists($id_article, $cache)) {
		return $cache[$id_article];
	}

	include_spip('base/abstract_sql');
	return $cache[$id_article] = (int) sql_getfetsel('id_rubrique', 'spip_articles', 'id_article=' . $id_article);
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
 * pas reconnecté depuis) — ne s'applique que si l'article commenté est
 * lui-même un article de réponse vivant dans la rubrique d'une classe
 * (donc id_rubrique == la classe) : sur le forum d'une consigne
 * (mission), id_rubrique est celle de l'intervenant, pas d'une classe,
 * et ne doit pas servir de repli (sinon icône/couleur arbitraire, sans
 * rapport avec l'auteur du commentaire ni avec la classe qui répond).
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
		$id_rubrique = (int) sql_getfetsel('id_rubrique', 'spip_articles', 'id_article=' . intval($forum['id_objet']));
		if ($id_rubrique && isset(thematique_classes_rangs()[$id_rubrique])) {
			return $id_rubrique;
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
 * @return int id de la rubrique "Consignes" (0 si échec) — c'est sous
 *   cette rubrique (type_objet consignes) que doivent vivre les articles
 *   jalons créés par genie/thematique_rentree_annee.php : c'est elle qui
 *   route vers noisettes/sidebar/consigne_pour_*.html (où vit le
 *   traitement spécifique est_jalon), pas "Travail des classes"
 *   (type_objet travail_en_cours).
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

	return $id_consignes ?: 0;
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
 * Intervenant "de l'année" : premier intervenant trouvé (au sens
 * thematique_premier_intervenant) sur la rubrique racine nommée par
 * l'année scolaire active. Repli sur la première rubrique taguée
 * travail_en_cours si aucune rubrique racine n'est nommée par l'année
 * (mêmes rubriques que celles utilisées par noisettes/menu_classes.html
 * pour lister les classes). Mis en cache mémoire par requête.
 *
 * @return int 0 si aucun intervenant trouvé
 */
function thematique_intervenant_annee() {
	static $cache = null;
	if ($cache !== null) {
		return $cache;
	}

	$id_rubrique_annee = sql_getfetsel(
		'id_rubrique',
		'spip_rubriques',
		'id_parent=0 AND titre LIKE ' . sql_quote('%' . constant('_ANNEE_SCOLAIRE') . '%')
	);

	if ($id_rubrique_annee) {
		return $cache = thematique_premier_intervenant(intval($id_rubrique_annee));
	}

	$id_rubrique_travail = thematique_id_rubrique_a_mot('travail_en_cours');

	return $cache = $id_rubrique_travail ? thematique_premier_intervenant($id_rubrique_travail) : 0;
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
 * Première rubrique (au sens id_rubrique croissant, toutes profondeurs)
 * portant un mot-clé donné, mise en cache mémoire par requête.
 *
 * Remplace squelettes/modeles/rub_mot_clef.html (BOUCLE RUBRIQUES non
 * cachée, relancée à chaque #MODELE{rub_mot_clef}{titre_mot}) : même
 * requête (pas de restriction id_parent=0, contrairement à
 * thematique_id_rubrique_racine_a_mot, qui vise un usage différent).
 *
 * @param string $titre_mot
 * @return int 0 si non trouvée
 */
function thematique_id_rubrique_a_mot($titre_mot) {
	static $cache = [];

	if (array_key_exists($titre_mot, $cache)) {
		return $cache[$titre_mot];
	}

	include_spip('base/abstract_sql');
	$id_mot = sql_getfetsel('id_mot', 'spip_mots', 'titre=' . sql_quote($titre_mot));
	if (!$id_mot) {
		return $cache[$titre_mot] = 0;
	}

	return $cache[$titre_mot] = (int) sql_getfetsel(
		'r.id_rubrique',
		['spip_rubriques AS r', 'spip_mots_liens AS ml'],
		['ml.id_objet=r.id_rubrique', 'ml.objet=' . sql_quote('rubrique'), 'ml.id_mot=' . intval($id_mot)],
		'',
		'r.id_rubrique',
		'0,1'
	);
}

/**
 * Premier article (au sens id_article croissant) portant un mot-clé donné,
 * sous la forme "id|statut" (ou "0|" si absent) — mis en cache mémoire par
 * requête.
 *
 * Remplace squelettes/modeles/art_mot_clef.html (BOUCLE ARTICLES non
 * cachée, relancée à chaque #MODELE{art_mot_clef}{titre_mot}). Même format
 * de sortie (cf squelettes/js/main.js, qui fait un split('|') dessus).
 *
 * @param string $titre_mot
 * @return string
 */
function thematique_article_a_mot($titre_mot) {
	static $cache = [];

	if (array_key_exists($titre_mot, $cache)) {
		return $cache[$titre_mot];
	}

	include_spip('base/abstract_sql');
	$id_mot = sql_getfetsel('id_mot', 'spip_mots', 'titre=' . sql_quote($titre_mot));
	if (!$id_mot) {
		return $cache[$titre_mot] = '0|';
	}

	$article = sql_fetsel(
		'a.id_article, a.statut',
		['spip_articles AS a', 'spip_mots_liens AS ml'],
		['ml.id_objet=a.id_article', 'ml.objet=' . sql_quote('article'), 'ml.id_mot=' . intval($id_mot)],
		'',
		'a.id_article',
		'0,1'
	);

	return $cache[$titre_mot] = $article ? ($article['id_article'] . '|' . $article['statut']) : '0|';
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
 * Ids des rubriques petites-enfants (id_parent -> enfants -> enfants) de
 * $id_grandparent portant un mot-clé donné, triées par date décroissante et
 * limitées — même principe que thematique_ids_rubriques_enfants() mais un
 * niveau plus profond (ex: chaque classe a une sous-rubrique "consignes",
 * cf noisettes/inc/actus_timeline.html).
 *
 * @param int $id_grandparent
 * @param string $titre_mot
 * @param int $limite 0 = pas de limite
 * @return int[]
 */
function thematique_ids_rubriques_petits_enfants_a_mot($id_grandparent, $titre_mot, $limite = 0) {
	if (!$id_grandparent) {
		return [];
	}
	$id_mot = sql_getfetsel('id_mot', 'spip_mots', 'titre=' . sql_quote($titre_mot));
	if (!$id_mot) {
		return [];
	}

	// Alias (r/parent/ml) obligatoires, cf thematique_id_rubrique_enfant_a_mot().
	$rows = sql_allfetsel(
		'r.id_rubrique',
		['spip_rubriques AS r', 'spip_rubriques AS parent', 'spip_mots_liens AS ml'],
		[
			'parent.id_rubrique=r.id_parent',
			'parent.id_parent=' . intval($id_grandparent),
			'ml.id_objet=r.id_rubrique',
			'ml.objet=' . sql_quote('rubrique'),
			'ml.id_mot=' . intval($id_mot),
		],
		'',
		'r.date DESC',
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
	$statut = session_get('statut');
	$admin = session_get('admin');

	// thematique_donner_role() priorise les mots-clés de hiérarchie
	// (travail_en_cours/consignes) sur le statut SPIP : un vrai webmestre
	// (statut 0minirezo) peut donc se retrouver avec $role='intervenant'
	// s'il est aussi rattaché à une hiérarchie "consignes". On vérifie le
	// statut directement pour ne pas le priver du bouton.
	if ($statut === '0minirezo' || $role === 'admin' || ($role === 'intervenant' && $admin > 0)) {
		return 'oui';
	}
	return 'non';
}

function filtre_afficher_forum_arbre($id_article) {
	include_spip('inc/session');
	$forums = sql_allfetsel(
		'*',
		'spip_forum',
		"objet='article' AND id_objet=" . intval($id_article) . ' AND statut=' . sql_quote('publie'),
		'',
		'date_heure DESC'
	);
	if (!$forums) {
		return _T('thematique:aucun_commentaire');
	}

	$id_forum_recent = null;
	if ($val = session_get('forum_commentaire_succes')) {
		$id_forum_recent = intval($val);
		session_set('forum_commentaire_succes', ''); // on "consomme" le flag
	}

	// Index des commentaires par parent
	$parents = [];
	foreach ($forums as $forum) {
		$parents[$forum['id_parent']][] = $forum;
	}
	// Construction récursive de l'arbre à partir de la racine
	$arbre = forum_construire_arbre(0, $parents, $id_forum_recent);
	return forum_rendre_branche($arbre);
}

/**
 * Compte les messages de forum publiés pour un ensemble d'articles, en une
 * seule requête groupée plutôt qu'une par article (évite le N+1 de
 * <BOUCLE_forum(FORUMS){id_article}> répétée à chaque ligne d'une liste).
 *
 * Ne compte que les messages de premier niveau (id_parent=0), comme le fait
 * implicitement le critère {id_article} sur une BOUCLE(FORUMS) : les
 * réponses imbriquées à un message ne sont pas comptées séparément.
 *
 * @return array [id_article => nombre de messages publiés]
 */
function thematique_comptes_forums(array $ids_articles) {
	$ids_articles = array_filter(array_map('intval', $ids_articles));
	if (!$ids_articles) {
		return [];
	}
	$rows = sql_allfetsel(
		'id_objet, COUNT(*) AS n',
		'spip_forum',
		["objet='article'", sql_in('id_objet', $ids_articles), 'statut=' . sql_quote('publie'), 'id_parent=0'],
		'id_objet'
	);
	$comptes = [];
	foreach ($rows as $row) {
		$comptes[(int) $row['id_objet']] = (int) $row['n'];
	}
	return $comptes;
}

/**
 * Nombre de messages de forum publiés (premier niveau) pour un seul
 * article. COUNT(*) direct plutôt qu'une BOUCLE(FORUMS) complète : utilisé
 * dans les onglets "Commentaires" des sidebars (une seule mission/réponse
 * par affichage, donc pas de N+1 à éviter ici, juste le coût d'une boucle
 * SPIP pour ce qui est une simple requête de comptage).
 *
 * @param int $id_article
 * @return int
 */
function thematique_nombre_commentaires($id_article) {
	return (int) sql_countsel('spip_forum', [
		"objet='article'",
		'id_objet=' . intval($id_article),
		'statut=' . sql_quote('publie'),
		'id_parent=0',
	]);
}

/**
 * Chemin (relatif au site) + timestamp d'un logo, à partir du tableau
 * renvoyé par quete_logo_objet() — petit utilitaire partagé par les
 * fonctions ci-dessous pour éviter de répéter la concaténation.
 *
 * @param array|false $infos Retour de quete_logo_objet()
 * @return string '' si $infos est vide/false
 */
function thematique_chemin_logo($infos) {
	if (!$infos) {
		return '';
	}
	return $infos['chemin'] . ($infos['timestamp'] ? '?' . $infos['timestamp'] : '');
}

/**
 * Avatar générique (pictogramme blanc plein, sans fond) utilisé quand un
 * auteur existe mais n'a ni logo SPIP ni avatar ENT — cf
 * thematique_logo_carre() et thematique_image_auteur_ou_classe(). Doit
 * toujours être posé sur un fond de couleur (classe CSS
 * .icon-avatar-masculin) pour rester visible, jamais affiché tel quel en
 * `<img>` nu sur un fond clair.
 */
define('_THEMATIQUE_AVATAR_GENERIQUE_MASCULIN', 'https://www.laclasse.com/avatar/avatar_masculin.svg');

/**
 * Photo d'un auteur : son logo SPIP uploadé, ou son avatar ENT
 * laclasse.com en repli (colonne extra spip_auteurs.avatar, alimentée par
 * le SSO — cf thematique_cioidc_userinfo()), mis en cache mémoire par
 * requête.
 *
 * Point d'entrée commun pour toute UI affichant "la photo d'un auteur" —
 * cf thematique_image_auteur_ou_classe() (photo de consigne) et
 * thematique_logo_carre() (avatar de forum, sidebar...).
 *
 * @param int $id_auteur 0 si pas d'auteur
 * @return array{logo:string,avatar:string,a_un_auteur:bool}
 *   'logo' : chemin du logo SPIP uploadé, '' si absent
 *   'avatar' : URL de l'avatar ENT (seulement si pas de logo), '' sinon
 *   'a_un_auteur' : true si $id_auteur correspond à un auteur existant
 */
function thematique_photo_auteur($id_auteur) {
	static $cache = [];
	$id_auteur = intval($id_auteur);
	if (isset($cache[$id_auteur])) {
		return $cache[$id_auteur];
	}

	$res = ['logo' => '', 'avatar' => '', 'a_un_auteur' => false];
	if ($id_auteur) {
		include_spip('base/abstract_sql');
		// Existence de l'auteur vérifiée sur 'nom' (toujours présent), pas sur
		// la colonne extra 'avatar' : sur un environnement où la migration
		// 3.0.9 n'a pas tourné (cf thematique_upgrade()), cette colonne peut
		// ne pas exister, et on ne veut pas perdre le vrai logo SPIP de
		// l'auteur pour autant.
		if (sql_fetsel('nom', 'spip_auteurs', 'id_auteur=' . $id_auteur)) {
			$res['a_un_auteur'] = true;
			include_spip('public/quete');
			$infos = quete_logo_objet($id_auteur, 'auteur', 'on');
			if ($infos) {
				$res['logo'] = thematique_chemin_logo($infos);
			} else {
				$avatar = sql_getfetsel('avatar', 'spip_auteurs', 'id_auteur=' . $id_auteur);
				$res['avatar'] = $avatar ?: '';
			}
		}
	}

	$cache[$id_auteur] = $res;
	return $res;
}

/**
 * Image d'une consigne : avatar de l'auteur de l'article, ou logo de sa
 * rubrique (classe/intervenant) en repli, mis en cache mémoire par requête.
 *
 * Priorité : logo SPIP uploadé par l'auteur, puis son avatar ENT, puis le
 * logo de la rubrique — jamais le logo de l'article lui-même. Repli final
 * identique à thematique_logo_carre() : _THEMATIQUE_AVATAR_GENERIQUE_MASCULIN
 * si l'auteur existe, sinon picto du site. Le résultat est injecté tel quel
 * en `src` d'un `<img>` côté JS (squelettes/js/consigne.js) : un '' y
 * produirait un `<img src="">` sans avatar, et l'avatar générique (picto
 * blanc plein sans fond) y serait invisible sans le fond de couleur que
 * fournit la classe CSS .icon-avatar-masculin — voir
 * thematique_image_est_avatar_generique(), à appeler côté squelette pour
 * savoir s'il faut ce wrapper (cf squelettes/json/consignes.html).
 *
 * @param int $id_auteur 0 si l'article n'a pas d'auteur identifié
 * @param int $id_rubrique Rubrique de repli (classe/intervenant)
 * @return string URL (relative au site ou externe) de l'image, jamais ''
 */
function thematique_image_auteur_ou_classe($id_auteur, $id_rubrique) {
	static $cache = [];
	$id_auteur = intval($id_auteur);
	$id_rubrique = intval($id_rubrique);
	$cle = $id_auteur . ':' . $id_rubrique;
	if (isset($cache[$cle])) {
		return $cache[$cle];
	}

	$photo = thematique_photo_auteur($id_auteur);
	$image = $photo['logo'] ?: $photo['avatar'];

	if (!$image && $id_rubrique) {
		include_spip('public/quete');
		$image = thematique_chemin_logo(quete_logo_objet($id_rubrique, 'rubrique', 'on'));
	}

	if (!$image) {
		$image = $photo['a_un_auteur']
			? _THEMATIQUE_AVATAR_GENERIQUE_MASCULIN
			: thematique_picto_site();
	}

	$cache[$cle] = $image;
	return $image;
}

/**
 * Indique si une image renvoyée par thematique_image_auteur_ou_classe()
 * est l'avatar générique (picto blanc plein sans fond) plutôt qu'une vraie
 * photo/logo : dans ce cas, l'`<img>` doit être enveloppé dans
 * `<span class="icon-avatar-masculin">` pour rester visible (même besoin
 * que thematique_logo_carre(), qui construit ce wrapper lui-même — ici la
 * construction du HTML final reste côté squelette/JS, cf
 * squelettes/json/consignes.html et squelettes/js/consigne.js).
 *
 * Comparaison sur le nom de fichier (avatar_masculin.svg / avatar_feminin.svg
 * — même repli que dans le menu haut, cf authentification.html), pas sur
 * l'URL exacte de _THEMATIQUE_AVATAR_GENERIQUE_MASCULIN : l'ENT lui-même
 * renvoie souvent ce même pictogramme générique comme "avatar" d'un compte
 * sans photo (colonne spip_auteurs.avatar), donc $image peut arriver ici
 * par ce chemin-là plutôt que par notre repli interne — dans les deux cas
 * c'est le même picto blanc sans fond, à envelopper pareil.
 *
 * @param string $image Retour de thematique_image_auteur_ou_classe()
 * @return bool
 */
function thematique_image_est_avatar_generique($image) {
	return (bool) preg_match('#/avatar_(masculin|feminin)\.svg(?:\?|$)#', (string) $image);
}

/**
 * Rubrique de classe liée à un auteur (prof/intervenant), mise en cache
 * mémoire par requête.
 *
 * Traduction SQL de l'ancien repli de squelettes/modeles/logo_carre.html
 * (BOUCLE(auteurs_liens) + BOUCLE(RUBRIQUES) + BOUCLE(RUBRIQUES){id_enfant})
 * : parmi les rubriques liées à l'auteur, on écarte celles taguées
 * evenements/blogs/ressources (ce sont des sous-espaces, pas des classes)
 * ainsi que celles dont un enfant direct porte un de ces mots (rubriques
 * "conteneur" d'un de ces sous-espaces) ; s'il reste plusieurs candidates,
 * la dernière par id_rubrique croissant l'emporte (même ordre que l'ancien
 * modèle, qui ne s'arrêtait pas à la première trouvée).
 *
 * @param int $id_auteur
 * @return int 0 si aucune rubrique de classe trouvée
 */
function thematique_id_rubrique_classe_auteur($id_auteur) {
	static $cache = [];
	$id_auteur = intval($id_auteur);
	if (isset($cache[$id_auteur])) {
		return $cache[$id_auteur];
	}

	include_spip('base/abstract_sql');

	$mots_exclus = sql_in('m.titre', ['evenements', 'blogs', 'ressources']);

	$candidats = $id_auteur ? sql_allfetsel(
		'al.id_objet AS id_rubrique',
		'spip_auteurs_liens AS al',
		[
			'al.id_auteur=' . $id_auteur,
			"al.objet='rubrique'",
			'NOT EXISTS (SELECT 1 FROM spip_mots_liens AS ml
				INNER JOIN spip_mots AS m ON m.id_mot=ml.id_mot
				WHERE ml.id_objet=al.id_objet AND ml.objet=' . sql_quote('rubrique') . "
				AND $mots_exclus)",
		],
		'',
		'al.id_objet'
	) : [];

	$id_rubrique = 0;
	foreach ($candidats as $candidat) {
		$id_candidat = intval($candidat['id_rubrique']);
		$a_un_enfant_exclu = sql_countsel(
			'spip_rubriques AS r
				INNER JOIN spip_mots_liens AS ml ON ml.id_objet=r.id_rubrique AND ml.objet=' . sql_quote('rubrique') . '
				INNER JOIN spip_mots AS m ON m.id_mot=ml.id_mot',
			['r.id_parent=' . $id_candidat, $mots_exclus]
		);
		if (!$a_un_enfant_exclu) {
			$id_rubrique = $id_candidat;
		}
	}

	$cache[$id_auteur] = $id_rubrique;
	return $id_rubrique;
}

/**
 * "Logo carré" prêt à afficher pour un article/auteur/rubrique/mot : avatar
 * de l'auteur (logo SPIP puis avatar ENT), sinon logo de la rubrique/du
 * mot, sinon picto du site — mis en cache mémoire par requête. Remplace
 * squelettes/modeles/logo_carre.html (jusqu'à 7 boucles SPIP non cachées
 * relancées à chaque affichage : timeline, sidebar, forum, listes
 * d'actus...).
 *
 * Appelée comme filtre, avec le type d'objet en 2ᵉ paramètre (uniforme quel
 * que soit l'objet, plutôt qu'une position dédiée par type) :
 * `#ID_RUBRIQUE|thematique_logo_carre` (objet par défaut : rubrique),
 * `#ID_ARTICLE|thematique_logo_carre{article}`,
 * `#ID_AUTEUR|thematique_logo_carre{auteur}`,
 * `#ID_RUBRIQUE|thematique_logo_carre{rubrique,40}` (taille explicite).
 *
 * Résolution de la rubrique de repli (même logique que l'ancien modèle) :
 * - $objet='article' : sa propre rubrique. L'auteur essayé en priorité
 *   (cf docstring ci-dessus) est le premier auteur lié à l'article — absent
 *   jusqu'ici, ce qui cassait la priorité "avatar de l'auteur d'abord"
 *   pourtant documentée, en particulier pour les articles jalons
 *   ("Cap sur l'année"/"La Rencontre", cf #359) : rattachés à
 *   "Consignes", une rubrique sans logo propre, ils retombaient
 *   directement sur le picto générique du site sans jamais essayer la
 *   photo de leur auteur (intervenant/admin) pourtant déjà affichée en
 *   toutes lettres juste à côté (cf noisettes/sidebar/inc/header_gauche_photo_auteur.html).
 * - $objet='auteur' : rubrique de classe liée à l'auteur (cf
 *   thematique_id_rubrique_classe_auteur()).
 * - $objet='rubrique' (par défaut) ou 'mot' : $id_objet tel quel.
 *
 * @param int $id_objet
 * @param string $objet 'rubrique' (défaut), 'auteur', 'article' ou 'mot'
 * @param int $taille Largeur maxi en pixels (image_reduire), 50 par défaut
 * @return string Code HTML (balise <img> ou <span>)
 */
function thematique_logo_carre($id_objet = 0, $objet = 'rubrique', $taille = 50) {
	static $cache = [];
	$id_objet = intval($id_objet);
	$objet = in_array($objet, ['rubrique', 'auteur', 'article', 'mot'], true) ? $objet : 'rubrique';
	$taille = intval($taille) ?: 50;
	$cle = "$objet:$id_objet:$taille";
	if (isset($cache[$cle])) {
		return $cache[$cle];
	}

	include_spip('base/abstract_sql');
	include_spip('public/quete');
	include_spip('inc/filtres_images_mini');
	include_spip('inc/filtres');

	$id_rubrique = $objet === 'rubrique' ? $id_objet : 0;
	$id_auteur = $objet === 'auteur' ? $id_objet : 0;
	$id_mot = $objet === 'mot' ? $id_objet : 0;

	if ($objet === 'article') {
		$id_rubrique = intval(sql_getfetsel('id_rubrique', 'spip_articles', 'id_article=' . $id_objet));
		$id_auteur = intval(sql_getfetsel(
			'id_auteur',
			'spip_auteurs_liens',
			"objet='article' AND id_objet=" . $id_objet,
			'',
			'id_auteur'
		));
	} elseif ($objet === 'auteur') {
		$id_rubrique = thematique_id_rubrique_classe_auteur($id_auteur);
	}

	$logo_repli = $id_rubrique ? thematique_chemin_logo(quete_logo_objet($id_rubrique, 'rubrique', 'on')) : '';
	if (!$logo_repli && $id_mot) {
		$logo_repli = thematique_chemin_logo(quete_logo_objet($id_mot, 'mot', 'on'));
	}

	$html = '';
	$a_un_auteur = false;
	if ($id_auteur) {
		$photo = thematique_photo_auteur($id_auteur);
		$a_un_auteur = $photo['a_un_auteur'];
		if ($photo['logo']) {
			$html = image_reduire($photo['logo'], $taille, 0);
		} elseif ($photo['avatar']) {
			// balise_img_svg : URL externe mais pas un .svg, donc route en
			// interne vers balise_img (construction de balise, pas de fetch
			// réseau) — safe.
			$html = filtrer('balise_img_svg', $photo['avatar'], '', 'avatar-photo');
		}
	}

	if (!$html && $logo_repli) {
		$html = image_reduire($logo_repli, $taille, 0);
	}

	if (!$html) {
		if ($a_un_auteur) {
			// avatar_masculin.svg est une URL externe (laclasse.com) : on la
			// laisse en <img> brut plutôt que |balise_img_svg, qui pour un
			// .svg distant déclencherait un copie_locale() (fetch + cache
			// serveur) à chaque premier rendu.
			$html = '<span class="icon-avatar-masculin"><img src="' . _THEMATIQUE_AVATAR_GENERIQUE_MASCULIN . '" alt=""></span>';
		} else {
			// Ici en revanche thematique_picto_site() est un fichier SVG
			// local : balise_img_svg l'inline en <svg> (pas de fetch, permet
			// le theming CSS), sans jamais toucher au réseau.
			$html = filtrer('balise_img_svg', thematique_picto_site(), '');
		}
	}

	$cache[$cle] = $html;
	return $html;
}

/**
 * Chemin du picto SVG du site courant (squelettes/img/pictos_sites/), avec
 * repli sur selfdata.svg si le site n'a pas le sien, mis en cache mémoire
 * par requête.
 *
 * Centralise une résolution jusqu'ici copiée-collée à 4 endroits :
 * favicon.ico.html, noisettes/inc-head.html (icônes de favicon),
 * noisettes/sommaire.html (badge de la timeline) et le repli "aucun logo"
 * de modeles/logo_carre.html.
 *
 * Appelée comme filtre : `#VAL{1}|thematique_picto_site` (le premier
 * paramètre n'est qu'un porteur, SPIP exige toujours une valeur pipée).
 *
 * @param mixed $valeur_ignoree Non utilisé, cf. remarque d'appel ci-dessus
 * @param string $avec_timestamp `non` pour omettre le `?timestamp`
 *     (cache-busting, comme le filtre `|timestamp`) — utile pour un appel
 *     qui recalcule déjà lui-même sur le fichier (ex: favicon.ico.html et
 *     son filesize()). Toute autre valeur (par défaut) l'ajoute.
 * @return string Chemin relatif au site (comme #CHEMIN{}), '' si introuvable
 */
function thematique_picto_site($valeur_ignoree = null, $avec_timestamp = 'oui') {
	static $cache = [];
	$avec_timestamp = ($avec_timestamp !== 'non');
	$cle = $avec_timestamp ? 1 : 0;
	if (isset($cache[$cle])) {
		return $cache[$cle];
	}

	$nom_site = strtolower(str_replace(' ', '', $GLOBALS['meta']['nom_site'] ?? ''));
	$chemin = (find_in_path('img/pictos_sites/' . $nom_site . '.svg') ?: find_in_path('img/pictos_sites/selfdata.svg'))
		?: '';

	if ($chemin && $avec_timestamp) {
		include_spip('inc/filtres');
		$chemin = timestamp($chemin);
	}

	$cache[$cle] = $chemin;
	return $chemin;
}

function forum_construire_arbre($id_parent, &$parents, $id_forum_recent = null) {
	if (!isset($parents[$id_parent])) {
		return [];
	}
	$res = [];
	foreach ($parents[$id_parent] as $forum) {

		$forum['est_recent'] = ($id_forum_recent !== null && intval($forum['id_forum']) === $id_forum_recent);
		$forum['reponses'] = forum_construire_arbre($forum['id_forum'], $parents, $id_forum_recent);
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

function filtre_titre_consigne($id_consigne) {
	if (!$id_consigne) {
		return '';
	}
	return sql_getfetsel('titre', 'spip_articles', 'id_article=' . intval($id_consigne));
}

function filtre_rang_consigne($id_consigne) {
	if (!$id_consigne) {
		return '';
	}

	$date_consigne = sql_getfetsel(
		'date',
		'spip_articles',
		'id_article=' . intval($id_consigne) . ' AND id_consigne=0'
	);
	if (!$date_consigne) {
		return '';
	}

	return sql_countsel(
		'spip_articles',
		'id_consigne = 0'
		. ' AND date >= ' . sql_quote(_DATE_DEBUT)
		. ' AND date <= ' . sql_quote($date_consigne)
		. ' AND id_article IN (SELECT DISTINCT id_consigne FROM spip_articles WHERE id_consigne > 0)'
	);
}

/**
 * Transforme un id_auteur de prof ou élève en id_rubrique de classe.
 */
function filtre_auteur_vers_classe($id_auteur) {
	if (!$id_auteur) {
		return '';
	}

	$result = sql_getfetsel(
		'sr.id_rubrique',
		'spip_auteurs_liens AS sal
         JOIN spip_rubriques AS sr ON sr.id_rubrique = sal.id_objet
         JOIN spip_rubriques AS sr2 ON sr.id_parent = sr2.id_rubrique',
		'sal.id_auteur = ' . intval($id_auteur) . '
         AND sr2.titre = ' . sql_quote('Travail des classes')
	);

	return $result;
}
