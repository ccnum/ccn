<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Histoires d'une année : autant que de participants (#520).
 *
 * Une histoire est une rubrique "NN. Histoire NN" sous la rubrique de l'année, avec
 * ses chapitres 2 à 4 (3 chapitres en plus du prologue et du chapitre 1 commun, #521).
 * Chaque participant actif a son histoire attitrée (meta de l'année, clé histoires,
 * cf inc/fictions_participants.php), point de départ de la rotation.
 *
 * Synchronisation idempotente : un participant sans histoire reçoit d'abord une
 * histoire existante non attribuée (ex: créée par l'ancienne création à la connexion
 * SSO), sinon une histoire neuve. Aucune histoire n'est jamais supprimée
 * automatiquement : les histoires en trop sont seulement signalées.
 */

include_spip('fictions_fonctions');
include_spip('inc/fictions_participants');

// Numéros des chapitres écrits par les participants (prologue et chapitre 1 commun à part)
const FICTIONSV2_CHAPITRES = [2, 3, 4];

/**
 * Histoires de l'année : rubriques filles de la rubrique d'année dont le titre commence
 * par un numéro ("07. Histoire 07"), triées par ce numéro. Les autres rubriques (blog
 * pédagogique...) sont ignorées.
 *
 * @return array<int, int> [id_rubrique => numero]
 */
function fictions_histoires_annee(int $id_annee): array {
	$histoires = [];
	foreach (sql_allfetsel('id_rubrique, titre', 'spip_rubriques', 'id_parent=' . $id_annee) as $row) {
		if (preg_match('/^(\d+)\./', $row['titre'], $m)) {
			$histoires[(int) $row['id_rubrique']] = (int) $m[1];
		}
	}
	asort($histoires);
	return $histoires;
}

/**
 * Chapitres d'une histoire, dans l'ordre de création (= numéro de chapitre, le titre
 * restant modifiable) : [numero_chapitre => id_article].
 */
function fictions_chapitres_histoire(int $id_rubrique): array {
	$ids = array_map('intval', array_column(
		sql_allfetsel('id_article', 'spip_articles', 'id_rubrique=' . $id_rubrique . " AND statut<>'poubelle'", '', 'id_article'),
		'id_article'
	));
	return array_combine(array_slice(FICTIONSV2_CHAPITRES, 0, min(count($ids), count(FICTIONSV2_CHAPITRES))), array_slice($ids, 0, count(FICTIONSV2_CHAPITRES)));
}

/**
 * L'histoire a-t-elle déjà des contributions : un chapitre publié ou au texte non vide.
 */
function fictions_histoire_a_contributions(int $id_rubrique): bool {
	return (bool) sql_countsel(
		'spip_articles',
		'id_rubrique=' . $id_rubrique . " AND (statut='publie' OR descriptif<>'')"
	);
}

/**
 * Crée "NN. Histoire NN" et ses chapitres 2 à 4 vides : le chapitre 2 en prop (à écrire,
 * cf cascade prop->publie de fictions_post_edition()), les suivants en prepa. Le
 * chapitre 1 n'est pas créé : c'est l'article "chapitre1" commun de l'année.
 *
 * @return int id_rubrique, 0 en cas d'échec
 */
function fictions_histoire_creer(int $id_annee, int $numero): int {
	include_spip('action/editer_objet');
	$num = sprintf('%02d', $numero);
	$id_rubrique = (int) objet_inserer('rubrique', $id_annee, ['titre' => _T('fictions:titre_histoire_numero', ['num' => $num])]);
	if (!$id_rubrique) {
		return 0;
	}
	foreach (FICTIONSV2_CHAPITRES as $i => $chapitre) {
		objet_inserer('article', $id_rubrique, [
			'titre' => $chapitre . '/ ' . _T('fictions:titre_chapitre_defaut'),
			'statut' => $i === 0 ? 'prop' : 'prepa',
		]);
	}
	// Publiée d'emblée (aucun article publié avant l'écriture du chapitre 2), statut
	// maintenu ensuite par fictions_calculer_rubriques().
	sql_updateq('spip_rubriques', ['statut' => 'publie', 'date' => date('Y-m-d H:i:s')], 'id_rubrique=' . $id_rubrique);
	return $id_rubrique;
}

/**
 * État des histoires de l'année par rapport aux participants actifs.
 *
 * @return array{attendu: int, reel: int, attribuees: array<int, int>, manquantes: int[],
 *   en_trop: int[], en_trop_avec_contributions: int[]}
 *   attribuees : [id_participant => id_rubrique] (attributions valides)
 *   manquantes : participants actifs sans histoire
 *   en_trop    : histoires attribuées à aucun participant actif
 */
function fictions_histoires_etat(int $annee): array {
	$id_annee = fictions_id_rubrique_annee($annee);
	$histoires = $id_annee ? fictions_histoires_annee($id_annee) : [];
	$participants = fictions_participants($annee);
	$config = fictions_annee_config($annee);

	$attribuees = [];
	foreach ($participants as $id => $participant) {
		$id_rubrique = (int) ($config['histoires'][$id] ?? 0);
		if ($id_rubrique && isset($histoires[$id_rubrique]) && !in_array($id_rubrique, $attribuees, true)) {
			$attribuees[$id] = $id_rubrique;
		}
	}
	$en_trop = array_values(array_diff(array_keys($histoires), $attribuees));
	return [
		'attendu' => count($participants),
		'reel' => count($histoires),
		'attribuees' => $attribuees,
		'manquantes' => array_values(array_diff(array_keys($participants), array_keys($attribuees))),
		'en_trop' => $en_trop,
		'en_trop_avec_contributions' => array_values(array_filter($en_trop, 'fictions_histoire_a_contributions')),
	];
}

/**
 * Donne une histoire à chaque participant actif qui n'en a pas : histoire existante non
 * attribuée (par numéro croissant), sinon histoire neuve. Relancer ne crée aucun
 * doublon ; aucune histoire n'est supprimée ni réattribuée.
 *
 * @return array{attribuees: int, creees: int, erreur: string}
 */
function fictions_histoires_synchroniser(int $annee): array {
	$resultat = ['attribuees' => 0, 'creees' => 0, 'erreur' => ''];
	$id_annee = fictions_id_rubrique_annee($annee);
	if (!$id_annee) {
		$resultat['erreur'] = 'annee_sans_rubrique';
		return $resultat;
	}

	// Verrou MySQL : deux synchronisations simultanées prendraient le même numéro.
	$verrou = sql_quote('fictions_histoires_' . $id_annee);
	sql_query("SELECT GET_LOCK($verrou, 10)");

	$etat = fictions_histoires_etat($annee);
	$libres = $etat['en_trop'];
	$config = fictions_annee_config($annee);
	$config['histoires'] = $etat['attribuees'];
	foreach ($etat['manquantes'] as $id_participant) {
		$id_rubrique = (int) array_shift($libres);
		if ($id_rubrique) {
			$resultat['attribuees']++;
		} else {
			$numeros = fictions_histoires_annee($id_annee);
			$id_rubrique = fictions_histoire_creer($id_annee, ($numeros ? max($numeros) : 0) + 1);
			if (!$id_rubrique) {
				$resultat['erreur'] = 'creation_histoire';
				break;
			}
			$resultat['creees']++;
		}
		$config['histoires'][$id_participant] = $id_rubrique;
		spip_log("fictions associations $annee : histoire #$id_rubrique attribuée au participant #$id_participant", 'fictions');
	}
	fictions_annee_config_ecrire($annee, $config);

	sql_query("SELECT RELEASE_LOCK($verrou)");
	return $resultat;
}
