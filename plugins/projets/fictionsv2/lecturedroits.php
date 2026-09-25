<?php
/**
 * Filtres SPIP LECTURE_DROITS, ECRIRE_DROITS et EST_AUTEUR_DROITS
 * pour les squelettes fictionsv2.
 *
 * Usage :
 *   #ID_ARTICLE|LECTURE_DROITS{montre}   → 'oui' ou ''
 *   #ID_ARTICLE|LECTURE_DROITS{mode}     → 'visible'|'verrouille'|'ecriture'
 *   #ID_ARTICLE|ECRIRE_DROITS            → 'oui' ou ''
 *   #ID_ARTICLE|EST_AUTEUR_DROITS        → 'oui' ou ''
 *
 * Le contexte doit être écrit en session avant l'appel :
 *   #SET{id_auteur_fv2,#SESSION{id_auteur}|session_set{id_auteur_fv2}}
 *   #SET{max_cadavres_fv2,#GET{var_max_cadavres}|session_set{max_cadavres_fv2}}
 *   #SET{zone_fv2,#GET{var_id_zone}|session_set{zone_fv2}}
 *   #SET{webmaster_fv2,#SESSION{webmestre}|session_set{webmaster_fv2}}
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/fictionsv2_autorisation');

/**
 * Filtre LECTURE_DROITS.
 *
 * @param int    $id_article L'article à évaluer (données du filtre)
 * @param string $champ      'montre' ou 'mode', par défaut 'montre'
 * @return string
 */
function filtre_lecture_droits_dist($id_article, $champ = 'montre') {
	$result = fictionsv2_lecture_droits(intval($id_article));
	if ($champ === 'mode') {
		return $result['mode'];
	}
	return $result['montre'] ? 'oui' : '';
}

/**
 * Filtre ECRIRE_DROITS.
 *
 * @param int $id_article L'article à évaluer (données du filtre)
 * @return string 'oui' ou ''
 */
function filtre_ecriture_droits_dist($id_article) {
	return fictionsv2_ecriture_droits(intval($id_article));
}

/**
 * Filtre EST_AUTEUR_DROITS.
 *
 * @param int $id_article L'article à évaluer (données du filtre)
 * @return string 'oui' ou ''
 */
function filtre_est_auteur_droits_dist($id_article) {
	return fictionsv2_est_auteur_droits(intval($id_article));
}
