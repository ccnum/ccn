<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Formulaire d'ajout de document(s) sur un objet, commun aux sites CCN
 * (thematique : publication de mission ; fictions : fiche script).
 * L'envoi passe par bigup (#SAISIE_FICHIER : zone de dépôt, upload par
 * morceaux) ; le formulaire est soumis automatiquement à la fin de l'upload
 * (js/ccn_joindre.js), sans bouton "Envoyer" visible.
 *
 * Remplace sur le site public #FORMULAIRE_JOINDRE_DOCUMENT (plugin medias),
 * dont l'interface à onglets (ordinateur / médiathèque / internet) dépend
 * d'un JS de l'espace privé : les trois modes s'y affichent ensemble.
 *
 * Usage : #FORMULAIRE_CCN_JOINDRE_DOCUMENT{id_objet, objet[, extensions[, label]]}
 * - extensions : tableau d'extensions autorisées sans le point (ex.
 *   #CONST{_THEMATIQUE_EXTENSIONS_DOCUMENT_MISSION}) ; vide = formats
 *   autorisés par SPIP (spip_types_documents, contrôlés par
 *   action/ajouter_documents dans tous les cas)
 * - label : libellé de la zone de dépôt (défaut : ccn:joindre_document_label)
 *
 * Le squelette appelant inclut la liste des documents de l'objet avec
 * {ajax=documents} pour qu'elle soit rechargée après chaque ajout
 * (cf ccn_joindre_ajouter_documents()).
 *
 * @package SPIP\Ccn\Formulaires
 */

/**
 * Normalise le paramètre extensions du formulaire.
 *
 * @param string|array $extensions Tableau, ou chaîne "gif,jpg" / "gif jpg"
 * @return string[]
 */
function ccn_joindre_document_extensions($extensions) {
	if (!is_array($extensions)) {
		$extensions = preg_split(',[^a-zA-Z0-9]+,', (string) $extensions);
	}

	return array_values(array_filter(array_map('strtolower', $extensions)));
}

function formulaires_ccn_joindre_document_charger_dist($id_objet = 0, $objet = 'article', $extensions = [], $label = '') {
	$extensions = ccn_joindre_document_extensions($extensions);

	return [
		'id_objet' => $id_objet,
		'objet' => $objet,
		'_label' => $label ?: _T('ccn:joindre_document_label'),
		'_accept' => $extensions ? '.' . implode(',.', $extensions) : '',
		// active la recherche/réinjection des fichiers uploadés par bigup
		'_bigup_rechercher_fichiers' => true,
	];
}

function formulaires_ccn_joindre_document_verifier_dist($id_objet = 0, $objet = 'article', $extensions = [], $label = '') {
	include_spip('inc/ccn_joindre');
	if ($erreur = ccn_joindre_verifier_autorisation($objet, $id_objet)) {
		return ['message_erreur' => $erreur];
	}

	$files = ccn_joindre_filtrer_extensions(
		ccn_joindre_trouver_fichiers(),
		ccn_joindre_document_extensions($extensions)
	);
	if (is_string($files)) {
		return ['message_erreur' => $files];
	}

	return [];
}

function formulaires_ccn_joindre_document_traiter_dist($id_objet = 0, $objet = 'article', $extensions = [], $label = '') {
	include_spip('inc/ccn_joindre');
	$files = ccn_joindre_filtrer_extensions(
		ccn_joindre_trouver_fichiers(),
		ccn_joindre_document_extensions($extensions)
	);
	if (is_string($files)) {
		return ['editable' => true, 'message_erreur' => $files];
	}

	return ccn_joindre_ajouter_documents($files, $objet, $id_objet);
}
