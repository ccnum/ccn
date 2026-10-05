<?php

/**
 * Traces de la connexion SSO (pipeline cioidc_userinfo), communes aux plugins
 * thematique et fictions. Écrites dans tmp/log/cioidc.log.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Trace ce que l'ENT a envoyé à la connexion. Avec _CCN_CIOIDC_LOG_COMPLET (par défaut,
 * cf ccn_options.php ; variable Docker CCN_CIOIDC_LOG_COMPLET=false pour le couper) : le
 * JSON complet des arguments et des attributs (identité, classes, groupes : données
 * personnelles, y compris d'élèves). Sinon seulement l'identifiant et le nom des attributs.
 *
 * @param array $flux Flux du pipeline cioidc_userinfo (args, data)
 * @param string $prefixe Plugin à l'origine de la trace (thematique, fictions)
 */
function ccn_cioidc_log_userinfo(array $flux, string $prefixe): void {
	$args = (array) ($flux['args'] ?? []);
	$data = (array) ($flux['data'] ?? []);
	$uid = (string) ($args['uid'] ?? (reset($args) ?: ''));

	if (defined('_CCN_CIOIDC_LOG_COMPLET') && _CCN_CIOIDC_LOG_COMPLET) {
		$json = fn($v) => json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
		spip_log("$prefixe userinfo uid=$uid args=" . $json($args), 'cioidc');
		spip_log("$prefixe userinfo uid=$uid data=" . $json($data), 'cioidc');
		return;
	}
	spip_log("$prefixe userinfo uid=$uid attributs=" . implode(',', array_keys($data)), 'cioidc');
}

/**
 * Trace les rubriques auxquelles l'auteur est lié (id:titre), pour vérifier le résultat
 * d'une connexion (classes, projets, admin restreint).
 */
function ccn_cioidc_log_rubriques_auteur(int $id_auteur, string $prefixe): void {
	$rubriques = sql_allfetsel(
		'r.id_rubrique, r.titre, r.id_secteur',
		'spip_auteurs_liens AS l JOIN spip_rubriques AS r ON r.id_rubrique=l.id_objet',
		['l.id_auteur=' . $id_auteur, 'l.objet=' . sql_quote('rubrique')],
		'',
		'r.id_secteur, r.id_rubrique'
	);
	$liste = array_map(fn($r) => $r['id_rubrique'] . ':' . $r['titre'] . ' (secteur ' . $r['id_secteur'] . ')', $rubriques);
	spip_log("$prefixe userinfo auteur #$id_auteur lié à " . count($liste) . ' rubrique(s) : ' . ($liste ? implode(' | ', $liste) : 'aucune'), 'cioidc');
}
