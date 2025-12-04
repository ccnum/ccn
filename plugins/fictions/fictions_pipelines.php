<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// cicas
function fictions_cicas($flux) {
	if (is_array($flux) and isset($flux['args'])) {
		spip_log($flux['args'], 'test_fictions');
	}

	return $flux;
}

function fictions_post_edition($flux) {
	spip_log($flux['args'], 'test');
	if (
		$flux['args']['action'] == 'modifier'
		and !isset($flux['args']['data'])
		and $table = $flux['args']['table']
		and $id_objet = $flux['args']['id_objet']
		and $flux['args']['champs_anciens']['statut'] == 'prop'
		and $table = 'spip_articles'
		and $id_rubrique = $flux['args']['champs_anciens']['id_rubrique']
	) {
		// Publier l'article que l'on vient de modifier
		sql_updateq($table, ['statut' => 'publie'], 'id_article=' . intval($id_objet));
		// Passer de prépa à prop le suivant
		$id_article = sql_getfetsel('id_article', 'spip_articles', ['id_rubrique=' . intval($id_rubrique), 'statut=' . sql_quote('prepa')], '', 'id_article', '0,1');
		if ($id_article) {
			sql_updateq($table, ['statut' => 'prop'], 'id_article=' . intval($id_article));
		}
	}
	return $flux;
}