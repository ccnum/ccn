<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


function formulaires_forumv2_charger_dist($id_article, $id_parent = 0) {

	return [
		'id_article' => intval($id_article),
		'id_parent' => intval($id_parent),
		'titre' => '',
		'texte' => '',
	];
}


function formulaires_forumv2_verifier_dist($id_article, $id_parent = 0) {

	$erreurs = [];

	if (!_request('titre')) {
		$erreurs['titre'] = 'Veuillez indiquer un titre.';
	}

	if (!_request('texte')) {
		$erreurs['texte'] = 'Veuillez écrire un message.';
	}

	return $erreurs;
}


function formulaires_forumv2_traiter_dist($id_article, $id_parent = 0) {

	$id_forum = sql_insertq(
		'spip_forum',
		[
			'id_objet' => intval($id_article),
			'objet' => 'article',
			'id_parent' => intval($id_parent),
			'titre' => _request('titre'),
			'texte' => _request('texte'),
			'date_heure' => date('Y-m-d H:i:s'),
			'date_thread' => date('Y-m-d H:i:s')
		]
	);


	if ($id_forum) {

		return [
			'message_ok' => 'Votre message a été publié.',
			'id_forum' => $id_forum
		];

	}


	return [
		'message_erreur' => 'Impossible de publier le message.'
	];
}