<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Droit à la déconnexion : le mail de notification d'une mission publiée
 * le soir (de 20h à 7h) ou le week-end (du vendredi 20h au lundi 7h) part
 * à la fin de cette plage, la mission elle-même étant publiée tout de suite.
 *
 * Chargé par le pipeline notifications (thematique_notifications()) juste
 * avant que le cœur ne cherche la fonction de notification : la
 * notifications_instituerarticle() définie ici prend alors le pas sur
 * notifications_instituerarticle_dist() (cf charger_fonction()).
 *
 * L'envoi différé est une tâche spip_jobs programmée à la fin de la plage :
 * stockée en base, elle survit à un redémarrage. On ne peut pas reculer
 * après coup la date des tâches envoyer_mail créées par
 * notifications_envoyer_mails() : la date fait partie de leur signature
 * (queue_sign_job()), la tâche modifiée serait supprimée sans envoi.
 */

// Heures de début et de fin de la plage de déconnexion (heure locale, PHP_TIMEZONE)
if (!defined('_THEMATIQUE_DECONNEXION_DEBUT')) {
	define('_THEMATIQUE_DECONNEXION_DEBUT', 20);
}
if (!defined('_THEMATIQUE_DECONNEXION_FIN')) {
	define('_THEMATIQUE_DECONNEXION_FIN', 7);
}

/**
 * Fin de la plage de déconnexion en cours.
 *
 * Plages : chaque soir du lundi au jeudi de _THEMATIQUE_DECONNEXION_DEBUT
 * au lendemain _THEMATIQUE_DECONNEXION_FIN, et du vendredi
 * _THEMATIQUE_DECONNEXION_DEBUT au lundi _THEMATIQUE_DECONNEXION_FIN.
 * Les jours fériés et les vacances ne sont pas pris en compte.
 *
 * @param int|null $maintenant Timestamp à tester (défaut : maintenant)
 * @return int Timestamp de la fin de la plage, 0 hors plage
 */
function thematique_deconnexion_fin($maintenant = null) {
	$maintenant = $maintenant ? (int) $maintenant : time();
	$heure = (int) date('G', $maintenant);
	$jour = (int) date('N', $maintenant); // 1 = lundi ... 7 = dimanche
	$fin_du_jour = function ($decalage_jours) use ($maintenant) {
		return mktime(
			_THEMATIQUE_DECONNEXION_FIN,
			0,
			0,
			(int) date('n', $maintenant),
			(int) date('j', $maintenant) + $decalage_jours,
			(int) date('Y', $maintenant)
		);
	};

	// Week-end : du vendredi soir au lundi matin
	if ($jour === 6 or $jour === 7 or ($jour === 5 and $heure >= _THEMATIQUE_DECONNEXION_DEBUT)) {
		return $fin_du_jour(8 - $jour);
	}
	// Tôt le matin, du lundi au vendredi
	if ($heure < _THEMATIQUE_DECONNEXION_FIN) {
		return $fin_du_jour(0);
	}
	// Soir, du lundi au jeudi
	if ($heure >= _THEMATIQUE_DECONNEXION_DEBUT) {
		return $fin_du_jour(1);
	}
	return 0;
}

/**
 * Notification de changement de statut d'un article : celle du cœur, sauf
 * pour une mission (consigne) publiée pendant la plage de déconnexion, dont
 * l'envoi est programmé à la fin de la plage.
 *
 * @param string $quoi
 * @param int $id_article
 * @param array $options statut, statut_ancien, date, date_ancienne
 */
function notifications_instituerarticle($quoi, $id_article, $options) {
	include_spip('notifications/instituerarticle');
	include_spip('thematique_fonctions');

	$fin = thematique_deconnexion_fin();
	if (
		$fin
		and ($options['statut'] ?? '') === 'publie'
		and ($options['statut_ancien'] ?? '') !== 'publie'
		and thematique_type_objet_article($id_article) === 'consignes'
	) {
		// La tâche s'exécute hors de la session de la personne qui publie :
		// on lui transmet son e-mail pour qu'elle reste exclue des
		// destinataires (cf thematique_notifications_destinataires()).
		$options['email_publieur'] = (string) ($GLOBALS['visiteur_session']['email'] ?? '');
		job_queue_add(
			'thematique_notifier_publication_differee',
			'thematique_notifier_publication_differee ' . intval($id_article),
			[$quoi, intval($id_article), $options],
			'inc/thematique_deconnexion',
			false,
			$fin
		);
		spip_log('notification de ' . intval($id_article) . ' différée au ' . date('Y-m-d H:i', $fin), 'thematique');
		return;
	}

	notifications_instituerarticle_dist($quoi, $id_article, $options);
}

/**
 * Tâche de fond : envoie la notification de publication différée par
 * notifications_instituerarticle(), si l'article est toujours publié.
 *
 * @param string $quoi
 * @param int $id_article
 * @param array $options
 * @return int 1 (tâche terminée)
 */
function thematique_notifier_publication_differee($quoi, $id_article, $options) {
	$statut = sql_getfetsel('statut', 'spip_articles', 'id_article=' . intval($id_article));
	if ($statut !== 'publie') {
		spip_log('notification différée de ' . intval($id_article) . ' annulée (statut ' . $statut . ')', 'thematique');
		return 1;
	}
	include_spip('notifications/instituerarticle');
	include_spip('inc/notifications');
	notifications_instituerarticle_dist($quoi, $id_article, $options);
	return 1;
}

/**
 * Texte du bandeau affiché à la création d'une mission pendant la plage de
 * déconnexion (cf formulaires/public_publier_article).
 *
 * @param string $type_article
 * @return string Texte, vide hors plage ou pour un autre type d'article
 */
function thematique_deconnexion_bandeau($type_article) {
	if ($type_article !== 'consignes' or !($fin = thematique_deconnexion_fin())) {
		return '';
	}
	include_spip('inc/filtres');
	$date = date('Y-m-d H:i:s', $fin);
	return _T('thematique:publier_deconnexion_bandeau', [
		'jour' => nom_jour($date) . ' ' . affdate_jourcourt($date),
		'heure' => _THEMATIQUE_DECONNEXION_FIN,
	]);
}
