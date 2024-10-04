<?php

/**
 * Oautils pour faciliter la construction de formulaires CVT sous formes de listes de saisies
 *
 * @package SPIP\Saisies\Saisies
 **/

// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Cherche la description des saisies d'un formulaire CVT dont on donne le nom
 *
 * @param string $form Nom du formulaire dont on cherche les saisies
 * @param array $args Tableau d'arguments du formulaire
 * @return array Retourne les saisies du formulaire sinon false
 */
function saisies_chercher_formulaire($form, $args, $je_suis_poste = false) {
	$saisies = [];

	if ($fonction_saisies = charger_fonction('saisies', 'formulaires/' . $form, true)) {
		$saisies = call_user_func_array($fonction_saisies, $args);
	}

	// Si on a toujours un tableau, on passe les saisies dans un pipeline normé comme pour CVT
	if (is_array($saisies)) {
		$saisies = pipeline(
			'formulaire_saisies',
			[
				'args' => ['form' => $form, 'args' => $args, 'je_suis_poste' => $je_suis_poste],
				'data' => $saisies
			]
		);
	}

	if (!is_array($saisies)) {
		$saisies = false;
	} else {
		$saisies = saisies_appliquer_depublie_recursivement($saisies);// Pour le cas des constructeurs
	}

	return $saisies;
}


/**
 * Génère un nom unique pour un champ d'un formulaire donné
 *
 * @param array $formulaire
 *     Le formulaire à analyser
 * @param string $type_saisie
 *     Le type de champ dont on veut un identifiant
 * @return string
 *     Un nom unique par rapport aux autres champs du formulaire
 */
function saisies_generer_nom($formulaire, $type_saisie) {
	$champs = saisies_lister_champs($formulaire);

	// Tant que type_numero existe, on incrémente le compteur
	$compteur = 1;
	while (array_search($type_saisie . '_' . $compteur, $champs) !== false) {
		$compteur++;
	}

	// On a alors un compteur unique pour ce formulaire
	return $type_saisie . '_' . $compteur;
}


/**
 * Détermine si peut faire une avance rapide en sautant des étapes qui sont "masquées" par afficher_si
 * @param array $saisies le tableau d'ensemble des saisies
 * @param int $etape l'étape à partir de laquelle on commence à tester les étapes suivantes
 * @return int l'étape où avancer
 **/
function saisies_determiner_avance_rapide(array $saisies, int $etape): int {
	return saisies_determiner_deplacement_rapide($saisies, $etape, +1);
}

/**
 * Détermine si peut faire un recul rapide en sautant des étapes qui sont "masquées" par afficher_si
 * @param array $saisies le tableau d'ensemble des saisies
 * @param int $etape l'étape à partir de laquelle on commence à tester les étapes précédentes
 * @return int l'étape où avancer
 **/
function saisies_determiner_recul_rapide(array $saisies, int $etape): int {
	return saisies_determiner_deplacement_rapide($saisies, $etape, -1);
}

/**
 * Détermine si peut faire un déplacement rapide en sautant des étapes qui sont "masquées" par afficher_si
 * Pour le confort de lecture, on pourra préférer les fonctions appellantes
 * `saisies_determiner_avance_rapide()` et `saisies_determiner_recul_rapide()`
 * @param array $saisies le tableau d'ensemble des saisies, potentiellement déjà classées par étapes
 * @param int $etape l'étape à partir de laquelle on commence à tester les étapes suivantes
 * @param int $sens 1 (avance) ou -1 (recule)
 * @return int l'étape où avancer
 **/
function saisies_determiner_deplacement_rapide(array $saisies, int $etape, int $sens): int {
	if (abs($sens) != 1) {
		spip_log("Argument $sens invalide dans saisies_determiner_deplacement_rapide ($sens)", 'saisies.' . _LOG_ERREUR);
		return $etape;
	}
	if (isset($saisies['etape_1'])) {
		$saisies_par_etapes = $saisies;
	} else {
		$saisies_par_etapes = saisies_lister_par_etapes($saisies);
	}
	$saisies_afficher_si_liste_masquees = array_keys(saisies_lister_par_nom(saisies_afficher_si_liste_masquees('get')));
	$nb_total_etapes = count($saisies_par_etapes);
	$i = $etape + $sens;
	$etape_a_conserver = false;//Basculé à true dès qu'un afficher_si réussit ou si pas d'afficher_si
	while (!$etape_a_conserver && $i <= $nb_total_etapes && $i > 0) {//Tester les étapes futures/passées (selon sens) 1 par 1
		$etape = $saisies_par_etapes["etape_$i"];
		if (!($etape['options']['afficher_si'] ?? '')) {
			$etape_a_conserver = true;
		} elseif (!in_array($etape['options']['nom'], $saisies_afficher_si_liste_masquees)) {
			$etape_a_conserver = true;
		} else {
			$i = $i + $sens;
		}
	}
	return max(1, $i);
}

/**
 * Retourne un résumé des étapes futures (qu'elles s'afficheront finalement ou pas).
 * @param array $etapes liste des étapes (sous forme de tableau de saisies)
 * @param int $etape etape courante
 * @param array $options_demandees tableau décrivant les options qu'on demande
 * @return array [
 *	'etape_n' => ['info_x' => 'valeur_x', 'info_y' => 'valeur_y' …]
 *	…
 * ]
**/
function saisies_resumer_etapes_futures(array $etapes, int $etape, array $options_demandees): array {
	$return = [];
	foreach ($etapes as $e => $description) {
		$numero_etape = intval(str_replace('etape_', '', $e));
		if ($numero_etape > $etape) {
			$output_etape = [];

			foreach ($options_demandees as $option) {
				if (isset($description['options'][$option])) {
					$output_etape[$option] = $description['options'][$option];
				}
			}

			$return[$e] = $output_etape;
		}
	}
	return $return;
}

/**
 * Détermine quels options d'étapes sont utiles au résumé des étapes futures
 * En fonction des options globales du formulaire
 * @param array $options_globales optiosn globales du formulaire
 * @return array liste des options à garder
**/
function saisies_determiner_options_demandees_resumer_etapes_futures(array $options_globales): array {
	$options = ['afficher_si'];// on veut dans tous les cas les afficher_si

	if (in_array($options_globales['etapes_precedent_suivant_titrer'] ?? '', ['on', true], true)) {
		$options[] = 'label';
	}
	return $options;
}
