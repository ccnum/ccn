<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function fictions_post_edition($flux) {
	if ($flux['args']['action'] !== 'modifier' || isset($flux['args']['data'])) {
		return $flux;
	}

	$id_objet   = intval($flux['args']['id_objet'] ?? 0);
	$statut     = $flux['args']['champs_anciens']['statut'] ?? '';
	$descriptif = $flux['args']['champs_anciens']['descriptif'] ?? '';
	$id_rubrique = intval($flux['args']['champs_anciens']['id_rubrique'] ?? 0);

	if (!$id_objet || !$statut || $descriptif === '' || !$id_rubrique) {
		return $flux;
	}

	include_spip('action/editer_objet');

	$blog = sql_getfetsel('id_rubrique', 'spip_rubriques', 'titre LIKE ' . sql_quote('%Blog Pédagogique%'));

	if ($statut === 'prop') {
		// Publier l'article que l'on vient de modifier
		objet_modifier('article', $id_objet, ['statut' => 'publie']);
		// Passer de prépa à prop le suivant
		$id_article = sql_getfetsel('id_article', 'spip_articles', ['id_rubrique=' . $id_rubrique, 'statut=' . sql_quote('prepa')], '', 'id_article', '0,1');
		if ($id_article) {
			objet_modifier('article', intval($id_article), ['statut' => 'prop']);
		}
	}

	if ($blog && (int)$blog === $id_rubrique && $statut === 'prepa') {
		objet_modifier('article', $id_objet, ['statut' => 'publie']);
	}

	return $flux;
}