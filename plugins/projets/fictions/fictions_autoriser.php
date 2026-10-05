<?php
/**
 * Autorisations de fictions, chargées via le pipeline "autoriser" (cf paquet.xml).
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function fictions_autoriser() {
}

/**
 * Association de mots-clés réservée aux admins complets (issue #274).
 *
 * Tous les mots de fictions sont techniques (rubrique-contenant-annees,
 * blog_pedagogique, presentation, prologue, chapitre1, footer-*...) et pilotent
 * la structure du site pour tout le monde : un article tagué blog_pedagogique
 * apparaît dans le footer de toutes les pages, une rubrique taguée
 * rubrique-contenant-annees fausse le sélecteur d'années. La règle native
 * (autoriser_associermots_dist) laisse un admin restreint (prof, lié à ses
 * rubriques de classe) poser n'importe lequel de ces mots depuis /ecrire sur
 * ce qu'il peut modifier.
 */
function autoriser_associermots($faire, $type, $id, $qui, $opt) {
	if (($qui['statut'] ?? '') !== '0minirezo' || !empty($qui['restreint'])) {
		return false;
	}
	return autoriser_associermots_dist($faire, $type, $id, $qui, $opt);
}

/**
 * #AUTORISER{lirechapitre,article,#ID_ARTICLE} : le chapitre est-il affiché à
 * l'auteur connecté (cf fictions_lecture_droits()).
 *
 * Auparavant dans autoriser.php, que rien ne chargeait (seul ce fichier est
 * déclaré au pipeline autoriser), et nommées autoriser_<faire>_<type> alors que
 * SPIP cherche autoriser_<type>_<faire> : ces autorisations n'existaient pas (#524).
 */
function autoriser_article_lirechapitre_dist($faire, $type, $id, $qui, $opt) {
	include_spip('inc/fictions_autorisation');
	return fictions_lecture_droits(intval($id), $qui)['montre'];
}

/**
 * #AUTORISER{ecrirechapitre,article,#ID_ARTICLE} : l'auteur connecté peut-il
 * écrire ce chapitre : webmestre, ou participant affecté au chapitre (lien
 * auteur ↔ article posé par le plan d'associations), chapitre en cours (#524).
 */
function autoriser_article_ecrirechapitre_dist($faire, $type, $id, $qui, $opt) {
	include_spip('inc/fictions_autorisation');
	return fictions_ecriture_droits(intval($id), $qui) === 'oui';
}

/**
 * Modifier un chapitre d'histoire : en plus du droit natif (rédacteur lié à
 * l'article non publié), un participant ne modifie que le chapitre en cours
 * d'écriture (statut prop) et seulement pendant la période d'écriture
 * (ecrirechapitre). Sans ça, ces règles n'étaient appliquées que par les
 * squelettes : crayons ou /ecrire permettaient de pré-écrire un chapitre prepa
 * ou de modifier le sien après la clôture (audit 2026-10). Admins complets et
 * webmestres inchangés ; autres articles inchangés.
 *
 * Déclarée seulement si aucun autre plugin ne surcharge déjà cette autorisation
 * (thematique le fait ; les deux ne sont pas censés être actifs ensemble).
 */
if (!function_exists('autoriser_article_modifier')) {
	function autoriser_article_modifier($faire, $type, $id, $qui, $opt) {
		$admin_complet = ($qui['webmestre'] ?? '') === 'oui'
			|| (($qui['statut'] ?? '') === '0minirezo' && empty($qui['restreint']));
		if (!$admin_complet) {
			include_spip('inc/fictions_autorisation');
			if (fictions_article_est_chapitre(intval($id))) {
				$statut = sql_getfetsel('statut', 'spip_articles', 'id_article=' . intval($id));
				if ($statut !== 'prop' || fictions_ecriture_droits(intval($id), $qui) !== 'oui') {
					return false;
				}
			}
		}
		return autoriser_article_modifier_dist($faire, $type, $id, $qui, $opt);
	}
}

/**
 * #AUTORISER{estauteur,article,#ID_ARTICLE} : l'auteur connecté est-il lié au
 * chapitre.
 */
function autoriser_article_estauteur_dist($faire, $type, $id, $qui, $opt) {
	include_spip('inc/fictions_autorisation');
	return fictions_est_auteur_droits(intval($id), $qui) === 'oui';
}

/**
 * Page des associations (#525, ecrire/?exec=fictions_associations) et ses opérations :
 * administrateurs complets et webmestres.
 */
function autoriser_fictionsassociations_dist($faire, $type, $id, $qui, $opt) {
	return ($qui['webmestre'] ?? '') === 'oui'
		|| (($qui['statut'] ?? '') === '0minirezo' && empty($qui['restreint']));
}

function autoriser_fictionsassociations_menu_dist($faire, $type, $id, $qui, $opt) {
	return autoriser('fictionsassociations', $type, $id, $qui, $opt);
}
