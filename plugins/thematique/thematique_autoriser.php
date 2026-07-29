<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function thematique_autoriser() {
}

// declarations d'autorisations
// Uniquement des fonctions courtes ici théoriquement
function autoriser_thematique_creer_onglet_dist($faire, $type, $id, $qui, $opt) {
	return autoriser('configurer', 'thematique', $id, $qui, $opt);
}

function autoriser_thematique_configurer_dist($faire, $type, $id, $qui, $opt) {
	return autoriser('webmestre', $type, $id, $qui, $opt);
}

/**
 * Modération des forums (modifier/supprimer un message) : réservée aux
 * profs/intervenants/admins, jamais aux élèves — y compris sur leurs
 * propres messages. Les élèves ne doivent pouvoir que publier. (issue #356)
 *
 * Surcharge autoriser_modererforum_dist() du plugin forum (qui délègue par
 * défaut à autoriser('modifier', $type_objet, ...), non pertinent ici).
 */
function autoriser_modererforum($faire, $type, $id, $qui, $opt) {
	include_spip('thematique_fonctions');
	$role = thematique_donner_role($qui['id_auteur'] ?? 0);

	return in_array($role, ['prof', 'intervenant', 'admin'], true);
}
