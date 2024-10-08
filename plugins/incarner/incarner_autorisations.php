<?php
/**
 * Définit les autorisations du plugin Incarner
 *
 * @plugin     Incarner
 * @copyright  2013
 * @author     Michel Bystranowski
 * @licence    GNU/GPL
 * @package    SPIP\Incarner\Autorisations
 */

/**
 * Fonction d'appel pour le pipeline
 * @pipeline autoriser */
function incarner_autoriser() {
}

/**
 * Autorisation d'incarner
 *
 * il faut s'assurer
 * - que l'auteur qui veut incarner a le statut minimum requis configure
 * - que l'auteur qu'il veut incarner ne lui augmente pas ses propres droits
 * (on ne peut incarner qu'un autre auteur avec des droits identiques ou moins importants)
 *
 * Si rien n'est configure il faut etre webmestre
 *
 * @param $faire
 *     Action demandée
 * @param $type
 *     Type d'objet sur lequel appliquer l'action (auteur)
 * @param $id
 *     Identifiant de l'objet (id_auteur)
 * @param $qui
 *     Description de l'auteur demandant l'autorisation
 * @param $opt
 *     Options de cette autorisation
 * @return bool
 *     true s'il a le droit, false sinon
 */
function autoriser_auteur_incarner_dist($faire, $type, $id, $qui, $opt) {

	include_spip('inc/config');
	$statut_minimum = lire_config('incarner/statut_minimum', 'webmestre');

	if ($qui['statut'] === '0minirezo') {
		// si on est webmestre, on a toujours le droit
		if ($qui['webmestre'] === 'oui') {
			return true;
		}
		// sinon si seul les webmestres sont autorises, pas la peine d'aller plus loin
		elseif ($statut_minimum === 'webmestre') {
			return false;
		}

		if (!$qui['restreint']) {
			$auteur_vise = sql_fetsel('statut, webmestre', 'spip_auteurs', 'id_auteur='.intval($id));
			// si l'auteur vise est webmestre, c'est NIET
			if ($auteur_vise['statut'] === '0minirezo' and $auteur_vise['webmestre'] === 'oui') {
				return false;
			}
			// sinon ok
			return true;
		}
		else {
			if ($statut_minimum === 'admin') {
				return false;
			}
			// 1 admin restreint ne peut pas incarner un autre admin restreint avec potentiellement des zones differentes
			// s'assurer que l'auteur vise n'est pas mieux que redacteur
			if (sql_getfetsel('statut', 'spip_auteurs', 'id_auteur='.intval($id)) === '0minirezo') {
				return false;
			}
			return  true;
		}
	}
	if ($qui['statut'] === '1comite' and $statut_minimum === 'redacteur') {
		// s'assurer que l'auteur vise n'est pas mieux que redacteur
		// et que c'est un auteur que le redacteur a le droit de modifier
		// (donc a priori pas un autre redacteur avec les auth par defaut)
		if (sql_getfetsel('statut', 'spip_auteurs', 'id_auteur='.intval($id)) === '0minirezo'
		  or !autoriser('modifier', 'auteur', $id, $qui)) {
			return false;
		}
		return  true;
	}

	return false;
}


/**
 * Le cookie d'incarnation donne droit aux fonctions de debug
 * meme si la personne connectee n'est pas admin
 * (si l'auteur d'origine est bien un admin, evidemment)
 *
 */
function autoriser_debug($faire, $type, $id, $qui, $opt) {
	include_spip('incarner_fonctions');

	if ($qui['statut'] === '0minirezo') {
		return true;
	} elseif ($id_auteur_origine = incarner_racine_incarnation()
	  and sql_getfetsel('statut', 'spip_auteurs', 'id_auteur='.intval($id_auteur_origine)) === '0minirezo') {
		return true;
	}
	else {
		return false;
	}
}
