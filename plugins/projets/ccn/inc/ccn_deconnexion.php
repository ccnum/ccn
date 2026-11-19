<?php
/**
 * Droit à la déconnexion : sur les sites thematique, les e-mails de
 * notification (publication d'un article, commentaire) produits la nuit
 * (_CCN_DECONNEXION_HEURE_DEBUT -> _CCN_DECONNEXION_HEURE_FIN) ou le
 * week-end (du vendredi soir au lundi matin) partent au début de la
 * période ouvrée suivante. La publication elle-même n'est pas différée.
 * Les sites fictions/petitfablab ne sont pas concernés.
 *
 * Pas de table dédiée : la file d'attente de SPIP (spip_jobs) sait déjà
 * exécuter une tâche à une date donnée. notifications_envoyer_mails() (cœur)
 * met chaque e-mail en file pour exécution immédiate, sans permettre de
 * choisir la date ; ccn_deconnexion_noter_envoi() (pipeline
 * notifications_envoyer_mails) retient la signature de chaque tâche, et
 * ccn_deconnexion_notification_fin() en repousse la date dès la fin de
 * l'événement de notification, quelques millisecondes plus tard. Modifier la
 * date ne touche pas à la signature (md5args) de la tâche.
 *
 * Seuls les e-mails envoyés pendant un événement de notification
 * (inc_notifications(), cf ccn_options.php) sont concernés : le cœur passe
 * aussi par notifications_envoyer_mails() pour le mot de passe oublié,
 * l'inscription ou le changement d'e-mail, qui doivent rester immédiats.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * L'heure $heure (0-23) tombe-t-elle dans la plage de repos quotidienne ?
 *
 * La plage peut passer minuit (début > fin, 20h -> 7h en production) ou tenir
 * dans la journée (début < fin, ex. 11h -> 15h pour tester en journée). Début
 * égal à fin : pas de plage quotidienne, seul le week-end est au repos.
 *
 * @param int $heure
 * @return bool
 */
function ccn_heure_en_repos(int $heure): bool {
	$debut = intval(_CCN_DECONNEXION_HEURE_DEBUT);
	$fin = intval(_CCN_DECONNEXION_HEURE_FIN);

	if ($debut > $fin) {
		return $heure >= $debut || $heure < $fin;
	}

	return $heure >= $debut && $heure < $fin;
}

/**
 * L'instant $time tombe-t-il en période de repos (week-end, ou plage
 * quotidienne) ?
 *
 * @param int $time
 * @return bool
 */
function ccn_est_en_repos(int $time): bool {
	return intval(date('N', $time)) >= 6 || ccn_heure_en_repos(intval(date('G', $time)));
}

/**
 * Fin de la période de repos en cours.
 *
 * La reprise est le premier instant ouvré qui suit : un jour de semaine à
 * _CCN_DECONNEXION_HEURE_FIN, ou à minuit le lundi si minuit est hors de la
 * plage (plage dans la journée). Avec la plage de nuit, le vendredi 20h donne
 * le lundi 7h.
 *
 * @param int|null $time Instant à tester (maintenant par défaut)
 * @return int|null Timestamp de reprise, null si $time tombe en période ouvrée
 */
function ccn_fin_deconnexion(?int $time = null): ?int {
	$time = $time ?? time();
	if (!ccn_est_en_repos($time)) {
		return null;
	}

	$fin = intval(_CCN_DECONNEXION_HEURE_FIN);
	$jour = intval(date('j', $time));
	$mois = intval(date('n', $time));
	$annee = intval(date('Y', $time));
	// mktime() plutôt que +86400 : juste au passage heure d'été/d'hiver.
	// Candidats dans l'ordre chronologique ; 8 jours couvrent tous les cas.
	for ($i = 0; $i <= 7; $i++) {
		foreach ([0, $fin] as $heure) {
			$candidat = mktime($heure, 0, 0, $mois, $jour + $i, $annee);
			if ($candidat > $time && !ccn_est_en_repos($candidat)) {
				return $candidat;
			}
		}
	}

	return null;
}

/**
 * État du droit à la déconnexion pour la requête en cours, partagé par
 * ccn_deconnexion_notification_debut(), ccn_deconnexion_noter_envoi() et
 * ccn_deconnexion_notification_fin() (appelées depuis des fichiers différents,
 * dont un pipeline, sans pouvoir se passer de paramètres) :
 * - profondeur : nombre d'événements de notification en cours (imbriqués) ;
 * - signatures_taches : valeurs de la colonne md5args (spip_jobs) des tâches
 *   envoyer_mail à reporter.
 *
 * Variable static plutôt que $GLOBALS : seules ces fonctions y accèdent.
 *
 * @return array Référence vers l'état, à récupérer par $etat = &ccn_deconnexion_etat()
 */
function &ccn_deconnexion_etat(): array {
	static $etat = ['profondeur' => 0, 'signatures_taches' => []];
	return $etat;
}

/**
 * Début d'un événement de notification : les e-mails mis en file jusqu'à
 * ccn_deconnexion_notification_fin() sont différables. Les événements
 * imbriqués (forumposte -> forumvalide) sont comptés.
 */
function ccn_deconnexion_notification_debut(): void {
	$etat = &ccn_deconnexion_etat();
	$etat['profondeur']++;
}

/**
 * Pipeline notifications_envoyer_mails (cf ccn_notifications_envoyer_mails) :
 * retient la signature de la tâche envoyer_mail que le cœur va créer pour cet
 * e-mail, s'il est envoyé pendant un événement de notification et en période
 * de repos.
 *
 * Doit voir le flux dans son état final, celui que le cœur sérialise en
 * arguments de la tâche : ccn est le seul plugin à utiliser ce pipeline (celui
 * du plugin notifications est commenté).
 *
 * @param array $envoi email, sujet, texte, from, headers
 */
function ccn_deconnexion_noter_envoi(array $envoi): void {
	// Sites thematique seulement (thematique_edifice compris, qui le nécessite) :
	// les sites fictions/petitfablab gardent leurs envois immédiats.
	if (!defined('_DIR_PLUGIN_THEMATIQUE')) {
		return;
	}
	$etat = &ccn_deconnexion_etat();
	if (!$etat['profondeur'] || !ccn_fin_deconnexion()) {
		return;
	}
	// Même calcul que la colonne md5args de queue_add_job() (ecrire/inc/queue.php),
	// sur les mêmes arguments, dans le même ordre, que job_queue_add('envoyer_mail', ...)
	// dans notifications_envoyer_mails() (ecrire/inc/notifications.php).
	$etat['signatures_taches'][] = md5(serialize([
		$envoi['email'],
		$envoi['sujet'],
		$envoi['texte'],
		$envoi['from'],
		$envoi['headers'],
	]));
}

/**
 * Fin d'un événement de notification : à la sortie du dernier événement
 * imbriqué, repousse à la fin de la période de repos les tâches envoyer_mail
 * retenues par ccn_deconnexion_noter_envoi().
 *
 * Si le cron exécute une tâche entre sa création et ce report, l'e-mail part
 * immédiatement : même comportement qu'avant le droit à la déconnexion.
 */
function ccn_deconnexion_notification_fin(): void {
	$etat = &ccn_deconnexion_etat();
	$etat['profondeur'] = max(0, $etat['profondeur'] - 1);
	if ($etat['profondeur'] > 0 || !$etat['signatures_taches']) {
		return;
	}
	$signatures = array_values(array_unique($etat['signatures_taches']));
	$etat['signatures_taches'] = [];

	$reprise = ccn_fin_deconnexion();
	if (!$reprise) {
		return;
	}

	include_spip('inc/queue');
	include_spip('base/abstract_sql');
	$date_reprise = date('Y-m-d H:i:s', $reprise);
	sql_updateq('spip_jobs', ['date' => $date_reprise], [
		"fonction='envoyer_mail'",
		'status=' . intval(_JQ_SCHEDULED),
		sql_in('md5args', $signatures),
		'date<' . sql_quote($date_reprise),
	]);
	spip_log(count($signatures) . " e-mail(s) de notification reporte(s) au $date_reprise (droit a la deconnexion)", 'ccn');
}
