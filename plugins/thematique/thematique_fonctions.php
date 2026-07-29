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
		'id_rubrique',
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
			'restreint' => intval($rubriques[0]['id_rubrique']),
		];
	}

	// Plusieurs rubriques → à adapter selon ta règle métier
	return [
		'role' => 'admin_restreint',
		'restreint' => intval($rubriques[0]['id_rubrique']),
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

function thematique_auteur_a_mot_dans_hierarchie($id_auteur, $titre_mot) {
	$rubriques = sql_allfetsel(
		'id_rubrique',
		'spip_auteurs_liens',
		'id_auteur=' . intval($id_auteur) . " AND objet='rubrique'"
	);
	foreach ($rubriques as $r) {
		// équivalent de ta BOUCLE_hie_rub{tout} + BOUCLE_mot_rub
		if (thematique_hierarchie_a_mot($r['id_rubrique'], $titre_mot)) {
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

	$from = ['spip_rubriques', 'spip_mots_liens'];
	$where = [
		'spip_mots_liens.id_objet=spip_rubriques.id_rubrique',
		'spip_mots_liens.objet=' . sql_quote('rubrique'),
		'spip_mots_liens.id_mot=' . intval($id_mot),
	];
	if ($id_annee) {
		$where[] = 'spip_rubriques.id_parent=' . intval($id_annee);
	}
	$conteneurs = sql_allfetsel('spip_rubriques.id_rubrique', $from, $where);
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
		$titre_racine = $annee . '-' . ($annee + 1);
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

	return (int) sql_getfetsel(
		'spip_rubriques.id_rubrique',
		['spip_rubriques', 'spip_mots_liens'],
		[
			'spip_mots_liens.id_objet=spip_rubriques.id_rubrique',
			'spip_mots_liens.objet=' . sql_quote('rubrique'),
			'spip_mots_liens.id_mot=' . intval($id_mot),
			'spip_rubriques.id_parent=' . intval($id_parent),
		],
		'',
		$orderby,
		'0,1'
	);
}

/**
 * Rubrique "classe en cours de travail" par défaut pour l'année active :
 * repli pour idRubriqueUser quand l'utilisateur n'a pas de rubrique
 * sélectionnée (cf choix_rubrique_admin2.html, ex BOUCLE_filtreTravailEnCours).
 *
 * @return int 0 si non trouvée
 */
function thematique_id_rubrique_travail_en_cours() {
	return thematique_id_rubrique_enfant_a_mot(
		thematique_id_rubrique_annee_active(),
		'travail_en_cours',
		'spip_rubriques.date'
	);
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
