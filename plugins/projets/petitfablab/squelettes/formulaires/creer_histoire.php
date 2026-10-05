<?php

// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('action/editer_objet');
include_spip('action/editer_rubrique');
include_spip('inc/autoriser');

function formulaires_creer_histoire_saisies_dist($rub_parent, $retour = '') {
	$prologues = [];
	$articles = sql_allfetsel(
		'a.id_article,a.titre,c.nom',
		'spip_articles AS a JOIN spip_auteurs_liens AS b JOIN spip_auteurs AS c ON (a.id_article=b.id_objet AND b.objet=\'article\' AND b.id_auteur=c.id_auteur)',
		['a.id_rubrique=' . intval($rub_parent), 'a.titre = "Prologue"']
	);
	foreach ($articles as $a) {
		$prologues[$a['id_article']] = $a['titre'] . ' par : ' . $a['nom'];
	}
	$saisies = [
		'saisie' => 'checkbox',
		'options' => [
			'nom' => 'prologue',
			'label' => _T('petitfablab:creer_histoire_prologue'),
			'obligatoire' => 'oui',
			'data' => $prologues
		]
	];

	return [$saisies];
}


// Enregistrement des données
function formulaires_creer_histoire_traiter_dist($rub_parent, $retour = '') {

	// $rub_parent vient de l'URL (?creer=, cf sommaire.html) : sans ce contrôle,
	// tout compte connecté créait des rubriques publiées sous n'importe quelle
	// rubrique (audit 2026-10). Réservé aux administrateurs de la rubrique.
	$rub_parent = intval($rub_parent);
	if (!$rub_parent || !autoriser('creerrubriquedans', 'rubrique', $rub_parent)) {
		return ['message_erreur' => _T('info_acces_interdit')];
	}

	$prologues = _request('prologue');
	if (!is_array($prologues) || empty($prologues)) {
		return ['message_erreur' => _T('petitfablab:creer_histoire_prologue')];
	}
	// Seuls les prologues proposés par saisies() (articles « Prologue » de la rubrique)
	$prologues_valides = array_column(sql_allfetsel(
		'id_article',
		'spip_articles',
		['id_rubrique=' . $rub_parent, 'titre=' . sql_quote('Prologue')]
	), 'id_article');

	$rub = [];
	foreach ($prologues as $id_prologue) {
		$id_prologue = intval($id_prologue);
		if (!$id_prologue || !in_array($id_prologue, array_map('intval', $prologues_valides), true)) {
			continue;
		}

		// Rubrique
		$id_rub = rubrique_inserer($rub_parent);
		if (!$id_rub) {
			continue;
		}
		$rub[] = $id_rub;
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
	}

	// Recharger la page
	$retour = parametre_url($retour, 'rub', $rub);
	$res['redirect'] = $retour;
	return $res;
}
