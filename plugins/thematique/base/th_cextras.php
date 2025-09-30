<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function th_declarer_champs_extras($champs = []) {
	$champs['spip_auteurs']['ent'] = [
		'saisie' => 'input', //Type du champ (voir plugin Saisies)
		'options' => [
			'nom' => 'ent',
			'label' => _T('th:ent'),
			'sql' => "varchar(255) NOT NULL DEFAULT ''",
			'defaut' => '', // Valeur par défaut
			//'disable' => 'disable',
			'restrictions' => [
				'voir' => ['auteur' => ''], //Tout le monde peut voir
				'modifier' => ['auteur' => 'webmestre']
			], //Seuls les webmestres peuvent modifier
		],
	];
	$champs['spip_auteurs']['ent_statut'] = [
		'saisie' => 'input', //Type du champ (voir plugin Saisies)
		'options' => [
			'nom' => 'ent_statut',
			'label' => _T('th:ent_statut'),
			'sql' => "varchar(255) NOT NULL DEFAULT ''",
			'defaut' => '', // Valeur par défaut
			'restrictions' => [
				'voir' => ['auteur' => ''], //Tout le monde peut voir
				'modifier' => ['auteur' => ['webmestre', '0minirezo']]
			], //Seuls les webmestres peuvent modifier
		],
	];

	$champs['spip_rubriques']['url_id_doc'] = [
		'saisie' => 'input', //Type du champ (voir plugin Saisies)
		'options' => [
			'nom' => 'url_id_doc',
			'label' => _T('th:url_id_doc'),
			'sql' => 'text',
			'defaut' => '', // Valeur par défaut
			'restrictions' => [
				'voir' => ['auteur' => ''], //Tout le monde peut voir
				'modifier' => ['auteur' => ['auteur' => '']]
			], //Seuls les webmestres peuvent modifier
		],
	];

	$champs['spip_rubriques']['id_rubrique_lien'] = [
		'saisie' => 'input', //Type du champ (voir plugin Saisies)
		'options' => [
			'nom' => 'id_rubrique_lien',
			'label' => _T('th:id_rubrique_lien'),
			'sql' => 'text',
			'defaut' => '', // Valeur par défaut
			'restrictions' => [
				'voir' => ['auteur' => ''], //Tout le monde peut voir
				'modifier' => ['auteur' => ['auteur' => '']]
			], //Seuls les webmestres peuvent modifier
		],
	];

	$champs['spip_articles']['x'] = [
		'saisie' => 'input', //Type du champ (voir plugin Saisies)
		'options' => [
			'nom' => 'x',
			'label' => _T('th:x'),
			'sql' => 'float',
			'defaut' => '', // Valeur par défaut
			'restrictions' => [
				'voir' => ['auteur' => ''], //Tout le monde peut voir
				'modifier' => ['auteur' => ['auteur' => '']]
			], //Seuls les webmestres peuvent modifier
		],
	];

	$champs['spip_articles']['y'] = [
		'saisie' => 'input', //Type du champ (voir plugin Saisies)
		'options' => [
			'nom' => 'y',
			'label' => _T('th:_position_y'),
			'sql' => 'float',
			'defaut' => '', // Valeur par défaut
			'restrictions' => [
				'voir' => ['auteur' => ''], //Tout le monde peut voir
				'modifier' => ['auteur' => ['auteur' => '']]
			], //Seuls les webmestres peuvent modifier
		],
	];

	return $champs;
}
