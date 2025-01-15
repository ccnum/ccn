<?php

// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('action/editer_objet');
include_spip('action/editer_rubrique');
include_spip('inc/autoriser');

function formulaires_creer_histoire_saisies_dist($rub_parent, $retour = '') {
	$saisies = [
		'saisie' => 'fieldset',
		'options' => [
			'nom' => 'conci_info'
		],
		'saisies' => [
			[
				'saisie' => 'input',
				'options' => [
					'nom' => 'nbr_histoire',
					'label' => _T('petitfablab:creer_histoire_nbr'),
					'obligatoire' => 'oui',
					'type' => 'number',
					'step' => '1',
					'min' => '1',
					'defaut' => '1'
				],
				'verifier' => [
					'type' => 'entier',
					'options' => [
						'min' => 1
					]
				]
			]
		]
	];

	return [$saisies];
}


// Enregistrement des données
function formulaires_creer_histoire_traiter_dist($rub_parent, $retour = '') {

	$nbr_histoire = _request('nbr_histoire');
	$i = 0;
	while ($i <= $nbr_histoire) {
		$id_prologue = sql_getfetsel("id_article", "spip_articles", ['titre LIKE "%prologue%"', 'statut="publie"', 'id_rubrique =' . intval($rub_parent)], "", "RAND()");

		// Rubrique
		$id_rub = rubrique_inserer($rub_parent);
		autoriser_exception('modifier', 'rubrique', $id_rub);
		objet_modifier('rubrique', $id_rub, ["titre" => "Histoire " . $id_rub, "descriptif" => $id_prologue]);
		objet_instituer('rubrique', $id_rub, ['id_parent' => $rub_parent, 'statut' => 'publie']);

		// Prologue
		/*
		$art = objet_inserer('article',$id_rub);
		autoriser_exception('modifier', 'article', $art);
		objet_modifier('article', $art,  array('titre'=>'Prologue','texte'=> $texte));
		sql_update('spip_articles', array("statut"=>"publie"), "id_article=".intval($art));
		autoriser_exception('modifier', 'article', $art, false);
		*/

		// Cloture exceptions
		autoriser_exception('modifier', 'rubrique', $id_rub, false);

		$i++;
	}

	// Recharger la page
	$res['redirect'] = $retour;
	return $res;
}
