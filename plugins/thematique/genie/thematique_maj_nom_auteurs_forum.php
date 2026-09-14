<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// Nombre de commentaires retraités par appel : reste dans le budget de temps
// habituel d'une tâche de fond SPIP (cf ecrire/inc/genie.php — un
// sous-processus HTTP n'a souvent qu'une trentaine de secondes) même si
// spip_forum contient beaucoup de lignes.
define('_THEMATIQUE_MAJ_NOM_AUTEURS_FORUM_TAILLE_LOT', 500);

/**
 * spip_forum.auteur est figé au moment de la publication du commentaire
 * (cf formulaires/forumv2.php : 'auteur' => _request('nom_auteur')) : les
 * commentaires postés avant l'introduction de thematique_nom_auteur_commentaire()
 * (issue #44 — nom réel + rôle/classe/collège, au lieu de rôle/classe/collège
 * seul) restent affichés avec l'ancien format tant qu'on ne les retraite pas
 * explicitement. Cette tâche recalcule une bonne fois pour toutes le champ
 * 'auteur' de tous les commentaires existants à partir de leur id_auteur.
 *
 * Tâche à usage unique (contrairement à thematique_rentree_annee/poubelle,
 * annuelles) et traitée par lots de _THEMATIQUE_MAJ_NOM_AUTEURS_FORUM_TAILLE_LOT
 * (retour négatif = "pas fini, relance-moi tout de suite", cf protocole des
 * fonctions genie_*_dist en tête de ecrire/inc/genie.php), avec le dernier
 * id_forum traité gardé en meta pour reprendre où on en était. Une fois le
 * dernier lot passé, verrouillée par la meta thematique_maj_nom_auteurs_forum :
 * elle ne fait plus rien ensuite.
 *
 * Enregistrée via le pipeline taches_generales_cron (thematique_pipelines.php)
 * plutôt que la balise <genie> de paquet.xml, cf
 * genie/thematique_rentree_annee.php.
 *
 * @param int $last
 * @return int
 */
function genie_thematique_maj_nom_auteurs_forum_dist($last) {
	if (!empty($GLOBALS['meta']['thematique_maj_nom_auteurs_forum'])) {
		return 1;
	}

	$dernier_id_traite = intval($GLOBALS['meta']['thematique_maj_nom_auteurs_forum_dernier_id'] ?? 0);

	$forums = sql_allfetsel(
		'id_forum, id_auteur, auteur',
		'spip_forum',
		'id_auteur > 0 AND id_forum > ' . $dernier_id_traite,
		'',
		'id_forum',
		'',
		_THEMATIQUE_MAJ_NOM_AUTEURS_FORUM_TAILLE_LOT
	);

	$nb_maj = 0;
	foreach ($forums as $forum) {
		$nouveau_nom = thematique_nom_auteur_commentaire($forum['id_auteur']);
		if ($nouveau_nom !== '' && $nouveau_nom !== $forum['auteur']) {
			sql_updateq('spip_forum', ['auteur' => $nouveau_nom], 'id_forum=' . intval($forum['id_forum']));
			$nb_maj++;
		}
		$dernier_id_traite = intval($forum['id_forum']);
	}

	spip_log(
		'thematique_maj_nom_auteurs_forum : lot traité jusqu\'à id_forum=' . $dernier_id_traite
			. ' (' . $nb_maj . ' mis à jour sur ' . count($forums) . ')',
		'thematique'
	);

	// Lot incomplet (ou vide) : plus rien à traiter, on verrouille pour de bon.
	if (count($forums) < _THEMATIQUE_MAJ_NOM_AUTEURS_FORUM_TAILLE_LOT) {
		ecrire_meta('thematique_maj_nom_auteurs_forum', 'fait');
		effacer_meta('thematique_maj_nom_auteurs_forum_dernier_id');
		return 1;
	}

	ecrire_meta('thematique_maj_nom_auteurs_forum_dernier_id', $dernier_id_traite);

	return -1;
}
