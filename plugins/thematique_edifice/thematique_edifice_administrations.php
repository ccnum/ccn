<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function thematique_edifice_upgrade($nom_meta_base_version, $version_cible) {
	$maj = [];
	$maj['create'] = [
		['th_migration_annee']
	];
	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}

function thematique_edifice_vider_tables($nom_meta_base_version) {
	effacer_meta($nom_meta_base_version);
}

function th_migration_annee() {
	include_spip('action/editer_rubrique');
	include_spip('inc/filtres_dates');
	// Traitement des anciennes années
	$archives = sql_getfetsel('id_rubrique', 'spip_rubriques', 'titre =' . sql_quote('Archives'));
	if ($archives) {
		$annees = sql_allfetsel('*', 'spip_rubriques', 'id_parent=' . intval($archives));
		foreach ($annees as $a) {
			[$annee,] = explode('-', $a['titre']);
			sql_updateq('spip_rubriques', ['titre' => $annee, 'id_parent' => '0', 'id_secteur' => '0'], 'id_rubrique=' . intval($a['id_rubrique']));
		}
	} else {
		$archives = rubrique_inserer(0);
	}
	// Traitement de la dernière année
	$consignes = sql_allfetsel('*', 'spip_rubriques', ['titre =' . sql_quote('Consignes'), 'id_parent="0"']);
	foreach ($consignes as $consigne) {
		$article_date = sql_getfetsel('date', 'spip_articles', ['id_secteur=' . intval($consigne['id_rubrique'])], '', '', '0,1');
		sql_updateq('spip_rubriques', ['id_parent' => $archives, 'id_secteur' => $archives], 'id_rubrique=' . intval($consigne['id_rubrique']));
	}
	$travaildesclasses = sql_allfetsel('*', 'spip_rubriques', ['titre =' . sql_quote('Travail des classes'), 'id_parent="0"']);
	foreach ($travaildesclasses as $travaildesclasse) {
		sql_updateq('spip_rubriques', ['id_parent' => $archives, 'id_secteur' => $archives], 'id_rubrique=' . intval($travaildesclasse['id_rubrique']));
	}
	sql_updateq('spip_rubriques', ['titre' => annee($article_date)], 'id_rubrique=' . intval($archives));
}
