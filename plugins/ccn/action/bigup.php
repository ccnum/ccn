<?php
if (!defined('_ECRIRE_INC_VERSION')) { return; }

function action_bigup_dist() {
	include_spip('inc/Bigup');

	// Quand un fichier est refusé côté client (taille, type), Flow.js déclenche
	// un fileRemoved automatique qui tente une suppression avec identifiant vide.
	// bigup retourne 404, ce qui affiche "Un problème est survenu" alors qu'il
	// n'y a rien à supprimer. On retourne 201 pour traiter ce cas silencieusement.
	if (_request('bigup_action') === 'effacer' && !trim((string) _request('identifiant'))) {
		http_response_code(201);
		exit;
	}

	$bigup = \Spip\Bigup\Repondre::depuisRequest();
	$bigup->repondre();
	exit;
}
