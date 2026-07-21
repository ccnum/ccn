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

	$from = 'spip_rubriques INNER JOIN spip_mots_liens'
		. ' ON spip_mots_liens.id_objet=spip_rubriques.id_rubrique AND spip_mots_liens.objet=' . sql_quote('rubrique');
	$where = 'spip_mots_liens.id_mot=' . intval($id_mot);
	if ($id_annee) {
		$where .= ' AND spip_rubriques.id_parent=' . intval($id_annee);
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
