<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function formulaires_depublier_article_charger_dist($id_article) {
	// Même autorisation que verifier() : sans elle, le bouton s'affichait
	// aussi aux visiteurs non connectés (l'action était refusée, mais seulement
	// après le clic).
	include_spip('inc/autoriser');
	if (!autoriser('modifier', 'article', $id_article)) {
		return false;
	}

	return [
		'id_article' => $id_article,
	];
}

function formulaires_depublier_article_verifier_dist($id_article) {
	$erreurs = [];

	include_spip('inc/autoriser');
	if (!autoriser('modifier', 'article', $id_article)) {
		$erreurs['message_erreur'] = _T('info_acces_interdit');
	}

	return $erreurs;
}

function formulaires_depublier_article_traiter_dist($id_article) {
	include_spip('action/editer_article');

	article_instituer($id_article, ['statut' => 'prop']);

	$res['message_ok'] = _T('thematique:article_depublie_succes');
	$res['message_ok'] .= "<script type='text/javascript'>"
		. 'setTimeout(function () { window.location.reload(); }, 800);'
		. '</script>';

	return $res;
}
