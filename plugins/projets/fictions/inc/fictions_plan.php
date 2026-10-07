<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Plan d'associations d'une année : qui écrit quel chapitre de quelle histoire.
 *
 * Rotation circulaire simple (#521), plus facile à vérifier par un humain qu'un tirage
 * aléatoire : les participants actifs sont pris dans l'ordre de saisie, le participant
 * d'indice i possède l'histoire i (cf #520) et écrit le 1er chapitre de rotation
 * (chapitre 2) de son histoire, le 2e (chapitre 3) de l'histoire i+1, le 3e (chapitre 4)
 * de l'histoire i+2, modulo le nombre d'histoires. Chaque histoire reçoit ainsi ses
 * chapitres de trois participants différents, à condition qu'il y en ait au moins trois.
 * L'écrivain est un participant comme les autres.
 *
 * Format d'un plan : [id_rubrique_histoire => [numero_chapitre => id_participant]].
 */

include_spip('inc/fictions_participants');
include_spip('inc/fictions_histoires');

/**
 * Calcule la rotation.
 *
 * @param int[] $ordre    id_participant dans l'ordre de rotation
 * @param int[] $histoires [id_participant => id_rubrique de son histoire]
 * @return array<int, array<int, int>> plan, vide si moins de participants que de
 *   chapitres (une histoire recevrait deux chapitres du même participant)
 */
function fictions_rotation(array $ordre, array $histoires): array {
	$ordre = array_values(array_filter($ordre, fn($id) => !empty($histoires[$id])));
	$n = count($ordre);
	if ($n < count(FICTIONS_CHAPITRES)) {
		return [];
	}
	$plan = [];
	foreach ($ordre as $i => $id_proprietaire) {
		foreach (FICTIONS_CHAPITRES as $j => $chapitre) {
			// L'histoire i reçoit son j-ième chapitre de rotation du participant i - j.
			$plan[(int) $histoires[$id_proprietaire]][$chapitre] = (int) $ordre[($i - $j + $n) % $n];
		}
	}
	return $plan;
}

/**
 * Rotation de l'année à partir de ses participants actifs et de leurs histoires.
 */
function fictions_rotation_annee(int $annee): array {
	$etat = fictions_histoires_etat($annee);
	return fictions_rotation(array_keys(fictions_participants($annee)), $etat['attribuees']);
}

/**
 * Anomalies d'un plan, sous forme de codes (item de langue fictions:anomalie_<code>)
 * avec leurs paramètres.
 *
 * @return array<int, array{code: string, params: array}>
 */
function fictions_plan_anomalies(int $annee, array $plan): array {
	$anomalies = [];
	$participants = fictions_participants($annee);
	$etat = fictions_histoires_etat($annee);
	$nom = fn($id) => $participants[$id]['nom'] ?? ('#' . $id);

	if (count($participants) < count(FICTIONS_CHAPITRES)) {
		$anomalies[] = ['code' => 'trop_peu_participants', 'params' => ['nb' => count($participants), 'min' => count(FICTIONS_CHAPITRES)]];
	}
	$ecrivains = array_filter($participants, fn($p) => $p['type'] === 'ecrivain');
	if (count($ecrivains) !== 1) {
		$anomalies[] = ['code' => 'ecrivains', 'params' => ['nb' => count($ecrivains)]];
	}
	foreach ($etat['manquantes'] as $id) {
		$anomalies[] = ['code' => 'histoire_manquante', 'params' => ['participant' => $nom($id)]];
	}
	foreach ($etat['en_trop'] as $id_rubrique) {
		$anomalies[] = ['code' => 'histoire_en_trop', 'params' => ['id_rubrique' => $id_rubrique]];
	}
	foreach ($participants as $id => $participant) {
		if (!sql_countsel('spip_auteurs', 'id_auteur=' . intval($participant['id_auteur']) . " AND statut<>'5poubelle'")) {
			$anomalies[] = ['code' => 'compte_invalide', 'params' => ['participant' => $participant['nom']]];
		}
	}

	$nb_chapitres = array_fill_keys(array_keys($participants), 0);
	foreach ($etat['attribuees'] as $id_rubrique) {
		$chapitres_histoire = fictions_chapitres_histoire($id_rubrique);
		$ecrivains_histoire = [];
		foreach (FICTIONS_CHAPITRES as $chapitre) {
			$id = (int) ($plan[$id_rubrique][$chapitre] ?? 0);
			if (!isset($chapitres_histoire[$chapitre])) {
				$anomalies[] = ['code' => 'chapitre_absent', 'params' => ['id_rubrique' => $id_rubrique, 'chapitre' => $chapitre]];
			}
			if (!$id) {
				$anomalies[] = ['code' => 'chapitre_sans_participant', 'params' => ['id_rubrique' => $id_rubrique, 'chapitre' => $chapitre]];
				continue;
			}
			if (!isset($participants[$id])) {
				$anomalies[] = ['code' => 'participant_inactif', 'params' => ['id_rubrique' => $id_rubrique, 'chapitre' => $chapitre, 'participant' => $nom($id)]];
				continue;
			}
			if (in_array($id, $ecrivains_histoire, true)) {
				$anomalies[] = ['code' => 'participant_deux_fois', 'params' => ['id_rubrique' => $id_rubrique, 'participant' => $nom($id)]];
			}
			$ecrivains_histoire[] = $id;
			$nb_chapitres[$id]++;
		}
	}
	if ($plan) {
		foreach ($nb_chapitres as $id => $nb) {
			if ($nb !== count(FICTIONS_CHAPITRES)) {
				$anomalies[] = ['code' => 'charge_inegale', 'params' => ['participant' => $nom($id), 'nb' => $nb, 'attendu' => count(FICTIONS_CHAPITRES)]];
			}
		}
	}
	return $anomalies;
}

/**
 * Échéances indicatives de chaque chapitre de rotation : la période lancement → clôture
 * découpée en parts égales (#521, plan calculé pour l'année entière).
 *
 * @return array<int, array{debut: string, fin: string}> vide si les dates ne sont pas fixées
 */
function fictions_plan_echeances(int $annee): array {
	$config = fictions_annee_config($annee);
	if (!$config['lancement'] || !$config['cloture']) {
		return [];
	}
	$debut = strtotime($config['lancement']);
	$duree = (strtotime($config['cloture']) - $debut) / count(FICTIONS_CHAPITRES);
	$echeances = [];
	foreach (FICTIONS_CHAPITRES as $j => $chapitre) {
		$echeances[$chapitre] = [
			'debut' => date('Y-m-d', (int) round($debut + $j * $duree)),
			'fin' => date('Y-m-d', (int) round($debut + ($j + 1) * $duree) - ($j + 1 < count(FICTIONS_CHAPITRES) ? 86400 : 0)),
		];
	}
	return $echeances;
}

/**
 * Plan enregistré de l'année (#523) : lu tel quel, jamais recalculé à l'affichage.
 */
function fictions_plan(int $annee): array {
	return fictions_annee_config($annee)['plan'];
}

/**
 * Des contributions existent-elles déjà dans les histoires de l'année : un chapitre
 * de rotation publié ou au texte non vide.
 */
function fictions_plan_contributions_commencees(int $annee): bool {
	foreach (fictions_histoires_etat($annee)['attribuees'] as $id_rubrique) {
		if (fictions_histoire_a_contributions($id_rubrique)) {
			return true;
		}
	}
	return false;
}

/**
 * (Re)génère la proposition de plan : synchronise les histoires (#520) puis calcule la
 * rotation (#521). Le plan repasse à l'état de proposition (non validé). Refusé une
 * fois l'écriture lancée sur un plan validé, ou dès qu'une contribution existe : une
 * régénération réattribuerait des chapitres déjà écrits.
 *
 * @return string '' si OK, sinon le code d'erreur
 */
function fictions_plan_generer(int $annee): string {
	if (fictions_plan_verrouille($annee)) {
		return 'plan_verrouille';
	}
	if (fictions_plan_contributions_commencees($annee)) {
		return 'contributions_commencees';
	}
	$synchro = fictions_histoires_synchroniser($annee);
	if ($synchro['erreur']) {
		return $synchro['erreur'];
	}
	$plan = fictions_rotation_annee($annee);
	if (!$plan) {
		return 'plan_impossible';
	}
	$config = fictions_annee_config($annee);
	$config['plan'] = $plan;
	$config['plan_valide'] = '';
	fictions_annee_config_ecrire($annee, $config);
	spip_log("fictions associations $annee : proposition de plan générée (" . count($plan) . ' histoires)', 'fictions');
	fictions_plan_appliquer($annee);
	return '';
}

/**
 * Le chapitre a-t-il déjà été écrit (publié ou texte non vide) : son affectation ne
 * peut plus changer.
 */
function fictions_chapitre_ecrit(int $id_article): bool {
	return (bool) sql_countsel('spip_articles', 'id_article=' . $id_article . " AND (statut='publie' OR descriptif<>'')");
}

/**
 * Change le participant d'un chapitre (correction manuelle d'un admin, #523/#525).
 * Possible même sur un plan validé, tant que le chapitre n'est pas écrit.
 *
 * @return string '' si OK, sinon le code d'erreur
 */
function fictions_plan_modifier_affectation(int $annee, int $id_rubrique, int $chapitre, int $id_participant): string {
	$config = fictions_annee_config($annee);
	if (!isset($config['plan'][$id_rubrique][$chapitre]) || !isset(fictions_participants($annee)[$id_participant])) {
		return 'affectation_invalide';
	}
	$id_article = fictions_chapitres_histoire($id_rubrique)[$chapitre] ?? 0;
	if ($id_article && fictions_chapitre_ecrit($id_article)) {
		return 'chapitre_deja_ecrit';
	}
	$config['plan'][$id_rubrique][$chapitre] = $id_participant;
	fictions_annee_config_ecrire($annee, $config);
	spip_log("fictions associations $annee : histoire #$id_rubrique chapitre $chapitre affecté au participant #$id_participant", 'fictions');
	// Plan déjà validé : les liens suivent immédiatement (#524)
	fictions_plan_appliquer($annee);
	return '';
}

/**
 * Valide le plan : refusé s'il est vide ou présente des anomalies. Une fois la phase
 * d'écriture lancée, un plan validé est verrouillé (cf fictions_plan_verrouille()).
 *
 * @return string '' si OK, sinon le code d'erreur
 */
function fictions_plan_valider(int $annee): string {
	$plan = fictions_plan($annee);
	if (!$plan) {
		return 'plan_absent';
	}
	if (fictions_plan_anomalies($annee, $plan)) {
		return 'plan_anomalies';
	}
	$config = fictions_annee_config($annee);
	$config['plan_valide'] = date('Y-m-d H:i:s');
	fictions_annee_config_ecrire($annee, $config);
	spip_log("fictions associations $annee : plan validé", 'fictions');
	fictions_plan_appliquer($annee);
	return '';
}

/**
 * Applique le plan validé aux liens SPIP (#524) : chaque chapitre de rotation est lié
 * (spip_auteurs_liens) au compte du participant affecté, que les droits d'écriture
 * reconnaissent (fictions_auteur_affecte_chapitre(), #AUTORISER{ecrirechapitre}) ;
 * les autres comptes sont retirés des chapitres non encore écrits. Un chapitre écrit
 * garde ses auteurs (historique). Prologue et chapitre 1 commun, hors rotation, ne sont
 * pas touchés.
 *
 * Plan non validé (proposition) : aucun compte n'est lié, et tout lien sur un chapitre
 * non écrit est retiré — notamment le compte qui a créé l'histoire, qu'objet_inserer()
 * liait comme auteur de chaque chapitre (cf fictions_histoire_creer()).
 *
 * @return array{lies: int, retires: int}
 */
function fictions_plan_appliquer(int $annee): array {
	include_spip('action/editer_liens');
	$resultat = ['lies' => 0, 'retires' => 0];
	$config = fictions_annee_config($annee);
	$valide = $config['plan_valide'] !== '';
	$participants = fictions_participants($annee);
	foreach ($config['plan'] as $id_rubrique => $affectations) {
		$chapitres = fictions_chapitres_histoire((int) $id_rubrique);
		foreach ($affectations as $chapitre => $id_participant) {
			$id_article = (int) ($chapitres[$chapitre] ?? 0);
			$id_auteur = $valide ? (int) ($participants[$id_participant]['id_auteur'] ?? 0) : 0;
			if (!$id_article || ($valide && !$id_auteur)) {
				continue;
			}
			if ($id_auteur && !sql_countsel('spip_auteurs_liens', "objet='article' AND id_objet=$id_article AND id_auteur=$id_auteur")) {
				objet_associer(['auteur' => $id_auteur], ['article' => $id_article]);
				$resultat['lies']++;
			}
			if (fictions_chapitre_ecrit($id_article)) {
				continue;
			}
			$autres = sql_allfetsel('id_auteur', 'spip_auteurs_liens', "objet='article' AND id_objet=$id_article AND id_auteur<>$id_auteur");
			foreach (array_column($autres, 'id_auteur') as $id_autre) {
				objet_dissocier(['auteur' => (int) $id_autre], ['article' => $id_article]);
				$resultat['retires']++;
			}
		}
	}
	spip_log("fictions associations $annee : plan appliqué ({$resultat['lies']} lien(s) posé(s), {$resultat['retires']} retiré(s))", 'fictions');
	return $resultat;
}

/**
 * Participant affecté à un chapitre de rotation selon le plan de son année, 0 si aucun.
 */
function fictions_participant_chapitre(int $id_article): int {
	static $cache = [];
	if (!isset($cache[$id_article])) {
		$id_rubrique = (int) sql_getfetsel('id_rubrique', 'spip_articles', 'id_article=' . $id_article);
		$annee = $id_rubrique ? fictions_annee_histoire($id_rubrique) : 0;
		$chapitre = $annee ? array_search($id_article, fictions_chapitres_histoire($id_rubrique), true) : false;
		$cache[$id_article] = $chapitre === false ? 0 : (int) (fictions_plan($annee)[$id_rubrique][$chapitre] ?? 0);
	}
	return $cache[$id_article];
}

/**
 * Toutes les données de la page des associations (#525), prêtes pour des boucles DATA.
 */
function fictions_associations_donnees(int $annee): array {
	$config = fictions_annee_config($annee);
	$participants = fictions_participants($annee, false);
	$etat = fictions_histoires_etat($annee);
	$id_annee = fictions_id_rubrique_annee($annee);
	$titres = $id_annee ? array_column(sql_allfetsel('id_rubrique, titre', 'spip_rubriques', 'id_parent=' . $id_annee), 'titre', 'id_rubrique') : [];
	$comptes = [];
	foreach (sql_allfetsel('id_auteur, nom', 'spip_auteurs', sql_in('statut', ['0minirezo', '1comite']), '', 'nom') as $row) {
		$comptes[(int) $row['id_auteur']] = $row['nom'];
	}

	$liste = [];
	foreach ($participants as $id => $participant) {
		$id_rubrique = (int) ($etat['attribuees'][$id] ?? 0);
		$liste[] = $participant + [
			'id' => $id,
			'compte' => $comptes[(int) $participant['id_auteur']] ?? ('#' . $participant['id_auteur']),
			'histoire' => $id_rubrique,
			'histoire_titre' => $titres[$id_rubrique] ?? '',
		];
	}

	$proprietaires = array_flip($etat['attribuees']);
	$lignes = [];
	foreach ($config['plan'] as $id_rubrique => $affectations) {
		$chapitres = fictions_chapitres_histoire((int) $id_rubrique);
		$cellules = [];
		foreach (FICTIONS_CHAPITRES as $chapitre) {
			$cellules[] = [
				'chapitre' => $chapitre,
				'id_participant' => (int) ($affectations[$chapitre] ?? 0),
				'ecrit' => isset($chapitres[$chapitre]) && fictions_chapitre_ecrit((int) $chapitres[$chapitre]),
			];
		}
		$lignes[] = [
			'id_rubrique' => (int) $id_rubrique,
			'titre' => $titres[$id_rubrique] ?? ('#' . $id_rubrique),
			'proprietaire' => $participants[$proprietaires[$id_rubrique] ?? 0]['nom'] ?? '',
			'cellules' => $cellules,
		];
	}

	$statut = 'absent';
	if ($config['plan']) {
		$statut = $config['plan_valide'] === '' ? 'proposition' : (fictions_plan_verrouille($annee) ? 'verrouille' : 'valide');
	}
	$anomalies = [];
	foreach (fictions_plan_anomalies($annee, $config['plan']) as $anomalie) {
		// Clé construite (fictions:anomalie_<code>) : items déclarés dans lang/fictions_fr.php
		$cle = 'fictions:anomalie_' . $anomalie['code'];
		$anomalies[] = _T($cle, $anomalie['params']);
	}

	return [
		'lancement' => $config['lancement'],
		'cloture' => $config['cloture'],
		'finalisation' => $config['finalisation'],
		'phase' => fictions_phase_ecriture($annee),
		'participants' => $liste,
		'comptes' => $comptes,
		'histoires_attendu' => $etat['attendu'],
		'histoires_reel' => $etat['reel'],
		'plan_statut' => $statut,
		'plan_valide' => $config['plan_valide'],
		'plan' => $lignes,
		'chapitres' => FICTIONS_CHAPITRES,
		'echeances' => fictions_plan_echeances($annee),
		'anomalies' => $anomalies,
		'verrouille' => fictions_plan_verrouille($annee),
		'annee_sans_rubrique' => !$id_annee,
	];
}
