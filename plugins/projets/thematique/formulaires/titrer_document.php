<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Édition en ligne du titre d'un document, depuis sa carte dans la liste
 * des pièces jointes du formulaire "Publier un article" (#497, cf
 * noisettes/inc/publier_article_documents.html et editerTitreDocument()
 * dans js/publier_article.js).
 *
 * Formulaire CVT plutôt qu'un crayon : la liste est un fragment rechargé en
 * ajax après chaque upload, et les droits des crayons sont recalculés à
 * chaque fragment (cf thematique_affichage_final) — un formulaire par
 * document se recharge seul, sans dépendre de cette amorce.
 *
 * @package SPIP\Thematique\Formulaires
 */

function formulaires_titrer_document_charger_dist($id_document) {
	$id_document = (int) $id_document;
	include_spip('inc/autoriser');
	if (!$id_document || !autoriser('modifier', 'document', $id_document)) {
		return false;
	}

	$document = sql_fetsel('titre, fichier', 'spip_documents', 'id_document=' . $id_document);
	if (!$document) {
		return false;
	}

	return [
		'titre' => $document['titre'],
		'_nom_fichier' => basename($document['fichier']),
	];
}

function formulaires_titrer_document_verifier_dist($id_document) {
	$erreurs = [];

	// verifier/traiter sont appelés au POST sans repasser par charger
	include_spip('inc/autoriser');
	if (!autoriser('modifier', 'document', (int) $id_document)) {
		$erreurs['message_erreur'] = _T('info_acces_interdit');
	} elseif (mb_strlen(trim((string) _request('titre'))) > 255) {
		$erreurs['message_erreur'] = _T('thematique:titre_trop_long', ['max' => 255]);
	}

	return $erreurs;
}

function formulaires_titrer_document_traiter_dist($id_document) {
	include_spip('action/editer_document');

	$erreur = document_modifier((int) $id_document, ['titre' => trim((string) _request('titre'))]);
	if ($erreur) {
		return ['message_erreur' => $erreur];
	}

	return ['editable' => true];
}
