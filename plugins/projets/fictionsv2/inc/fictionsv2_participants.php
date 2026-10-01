<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Participants et calendrier d'une année d'écriture fictionsv2 (#518).
 *
 * Un participant est une classe ou l'écrivain : c'est lui, et non le compte SPIP, qui
 * écrit les chapitres et qui apparaît sur le site. Plusieurs classes peuvent partager
 * le même compte enseignant (#519) : chacune reste un participant distinct.
 *
 * Stockage : une meta par année, fictionsv2_annee_<annee>, tableau sérialisé :
 *   lancement, cloture, finalisation : dates Y-m-d (vides = non fixées)
 *   participants : [id_participant => [type, nom, id_auteur, actif]]
 *   prochain_id  : compteur des identifiants de participants (jamais réutilisés)
 *   histoires    : [id_participant => id_rubrique de son histoire] (#520)
 *   plan, plan_valide : plan d'associations (#521, #523)
 *
 * La liste des participants est explicite (saisie par un admin, cf
 * ecrire/?exec=fictionsv2_associations), indépendante de l'ordre ou du moment de
 * connexion des enseignants.
 */

const FICTIONSV2_TYPES_PARTICIPANT = ['classe', 'ecrivain'];

function fictionsv2_annee_config_defaut(): array {
	return [
		'lancement' => '',
		'cloture' => '',
		'finalisation' => '',
		'participants' => [],
		'prochain_id' => 1,
		'histoires' => [],
		'plan' => [],
		'plan_valide' => '',
	];
}

function fictionsv2_annee_config(int $annee): array {
	include_spip('inc/meta');
	lire_metas();
	$config = @unserialize($GLOBALS['meta']['fictionsv2_annee_' . $annee] ?? '');
	return array_merge(fictionsv2_annee_config_defaut(), is_array($config) ? $config : []);
}

function fictionsv2_annee_config_ecrire(int $annee, array $config): void {
	include_spip('inc/meta');
	ecrire_meta('fictionsv2_annee_' . $annee, serialize(array_merge(fictionsv2_annee_config_defaut(), $config)), 'non');
}

/**
 * L'année a-t-elle été configurée (au moins un participant ou une date) ? Une année
 * non configurée garde le fonctionnement antérieur : pas de restriction de dates.
 */
function fictionsv2_annee_configuree(int $annee): bool {
	$config = fictionsv2_annee_config($annee);
	return $config['participants'] || $config['lancement'] || $config['cloture'];
}

/**
 * Dates de l'année. Chaque date doit être au format Y-m-d (ou vide) et l'ordre
 * lancement <= clôture <= finalisation respecté.
 *
 * @return string '' si OK, sinon le code d'erreur (item de langue fictionsv2:erreur_<code>)
 */
function fictionsv2_annee_dates_modifier(int $annee, string $lancement, string $cloture, string $finalisation): string {
	foreach ([$lancement, $cloture, $finalisation] as $date) {
		if ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
			return 'date_invalide';
		}
	}
	$renseignees = array_values(array_filter([$lancement, $cloture, $finalisation]));
	$triees = $renseignees;
	sort($triees);
	if ($renseignees !== $triees) {
		return 'dates_ordre';
	}
	$config = fictionsv2_annee_config($annee);
	$config['lancement'] = $lancement;
	$config['cloture'] = $cloture;
	$config['finalisation'] = $finalisation;
	fictionsv2_annee_config_ecrire($annee, $config);
	return '';
}

/**
 * Phase d'écriture à une date donnée (aujourd'hui par défaut) :
 *   'avant'   : avant la date de lancement, personne n'écrit (hors webmestre)
 *   'ouverte' : entre lancement et clôture inclus
 *   'close'   : après la clôture, participants désactivés
 * Date non fixée = pas de borne de ce côté.
 */
function fictionsv2_phase_ecriture(int $annee, string $jour = ''): string {
	$jour = $jour ?: date('Y-m-d');
	$config = fictionsv2_annee_config($annee);
	if ($config['lancement'] && $jour < $config['lancement']) {
		return 'avant';
	}
	if ($config['cloture'] && $jour > $config['cloture']) {
		return 'close';
	}
	return 'ouverte';
}

/**
 * Participants de l'année, triés par identifiant (ordre de saisie).
 *
 * @return array<int, array{type: string, nom: string, id_auteur: int, actif: bool}>
 */
function fictionsv2_participants(int $annee, bool $actifs_seulement = true): array {
	$participants = fictionsv2_annee_config($annee)['participants'];
	ksort($participants);
	if ($actifs_seulement) {
		$participants = array_filter($participants, fn($p) => !empty($p['actif']));
	}
	return $participants;
}

function fictionsv2_participant_nom_normalise(string $nom): string {
	include_spip('inc/charsets');
	return preg_replace('/[^a-z0-9]/', '', strtolower(translitteration($nom)));
}

/**
 * Contrôle d'un participant avant ajout/modification.
 *
 * @return string '' si OK, sinon le code d'erreur
 */
function fictionsv2_participant_verifier(int $annee, string $type, string $nom, int $id_auteur, int $id_participant = 0): string {
	if (!in_array($type, FICTIONSV2_TYPES_PARTICIPANT, true)) {
		return 'type_invalide';
	}
	if (trim($nom) === '') {
		return 'nom_obligatoire';
	}
	if (!$id_auteur || !sql_countsel('spip_auteurs', 'id_auteur=' . $id_auteur . " AND statut<>'5poubelle'")) {
		return 'auteur_invalide';
	}
	// Pas de doublon : deux participants de même nom (casse, accents et ponctuation
	// ignorés) dans la même année.
	$cle = fictionsv2_participant_nom_normalise($nom);
	foreach (fictionsv2_participants($annee, false) as $id => $participant) {
		if ($id !== $id_participant && fictionsv2_participant_nom_normalise($participant['nom']) === $cle) {
			return 'participant_doublon';
		}
	}
	return '';
}

/**
 * Ajoute un participant. Refusé une fois le plan d'associations verrouillé (#523) :
 * pas de modification silencieuse d'une année déjà démarrée.
 *
 * @return array{0: int, 1: string} [id_participant (0 si refus), code d'erreur]
 */
function fictionsv2_participant_ajouter(int $annee, string $type, string $nom, int $id_auteur): array {
	if (fictionsv2_plan_verrouille($annee)) {
		return [0, 'plan_verrouille'];
	}
	$erreur = fictionsv2_participant_verifier($annee, $type, $nom, $id_auteur);
	if ($erreur) {
		return [0, $erreur];
	}
	$config = fictionsv2_annee_config($annee);
	$id = (int) $config['prochain_id'];
	$config['participants'][$id] = ['type' => $type, 'nom' => trim($nom), 'id_auteur' => $id_auteur, 'actif' => true];
	$config['prochain_id'] = $id + 1;
	fictionsv2_annee_config_ecrire($annee, $config);
	spip_log("fictionsv2 associations $annee : participant #$id ajouté ($type, " . trim($nom) . ", auteur #$id_auteur)", 'fictionsv2');
	return [$id, ''];
}

/**
 * Modifie le nom, le type ou le compte d'un participant. Le changement de compte
 * (classe qui change d'enseignant, #519) reste possible une fois l'année démarrée :
 * les liens de ses chapitres suivent à la prochaine application du plan (#524).
 *
 * @return string '' si OK, sinon le code d'erreur
 */
function fictionsv2_participant_modifier(int $annee, int $id_participant, string $type, string $nom, int $id_auteur): string {
	$config = fictionsv2_annee_config($annee);
	if (!isset($config['participants'][$id_participant])) {
		return 'participant_inconnu';
	}
	$erreur = fictionsv2_participant_verifier($annee, $type, $nom, $id_auteur, $id_participant);
	if ($erreur) {
		return $erreur;
	}
	$config['participants'][$id_participant] = array_merge(
		$config['participants'][$id_participant],
		['type' => $type, 'nom' => trim($nom), 'id_auteur' => $id_auteur]
	);
	fictionsv2_annee_config_ecrire($annee, $config);
	spip_log("fictionsv2 associations $annee : participant #$id_participant modifié ($type, " . trim($nom) . ", auteur #$id_auteur)", 'fictionsv2');
	// Classe qui change d'enseignant (#519) : ses chapitres non écrits passent au
	// nouveau compte si le plan est validé (#524)
	include_spip('inc/fictionsv2_plan');
	fictionsv2_plan_appliquer($annee);
	return '';
}

/**
 * Retire un participant (et son histoire attitrée, la rubrique restant en place).
 * Refusé une fois le plan verrouillé.
 *
 * @return string '' si OK, sinon le code d'erreur
 */
function fictionsv2_participant_retirer(int $annee, int $id_participant): string {
	if (fictionsv2_plan_verrouille($annee)) {
		return 'plan_verrouille';
	}
	$config = fictionsv2_annee_config($annee);
	if (!isset($config['participants'][$id_participant])) {
		return 'participant_inconnu';
	}
	unset($config['participants'][$id_participant], $config['histoires'][$id_participant]);
	fictionsv2_annee_config_ecrire($annee, $config);
	spip_log("fictionsv2 associations $annee : participant #$id_participant retiré", 'fictionsv2');
	return '';
}

/**
 * Participants actifs gérés par un compte SPIP (#519) : un enseignant peut gérer
 * plusieurs classes, chacune reste un participant à part entière (sa propre histoire,
 * ses propres affectations de chapitres).
 *
 * @return array<int, array> [id_participant => participant]
 */
function fictionsv2_participants_auteur(int $annee, int $id_auteur): array {
	return array_filter(
		fictionsv2_participants($annee),
		fn($participant) => (int) $participant['id_auteur'] === $id_auteur
	);
}

/**
 * Plan verrouillé : validé et écriture démarrée (#523). Défini ici pour que les
 * contrôles des participants n'aient pas à charger tout le plan.
 */
function fictionsv2_plan_verrouille(int $annee): bool {
	$config = fictionsv2_annee_config($annee);
	return $config['plan_valide'] !== '' && fictionsv2_phase_ecriture($annee) !== 'avant';
}

/**
 * Année d'une histoire (titre de sa rubrique parente), 0 si introuvable.
 */
function fictionsv2_annee_histoire(int $id_rubrique): int {
	static $cache = [];
	if (!isset($cache[$id_rubrique])) {
		$titre = (string) sql_getfetsel(
			'p.titre',
			['spip_rubriques AS r', 'spip_rubriques AS p'],
			['p.id_rubrique=r.id_parent', 'r.id_rubrique=' . $id_rubrique]
		);
		$cache[$id_rubrique] = preg_match('/^\d{4}$/', $titre) ? (int) $titre : 0;
	}
	return $cache[$id_rubrique];
}
