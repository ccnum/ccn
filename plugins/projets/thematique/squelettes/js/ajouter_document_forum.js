/**
 * Vérifie côté client, au choix d'un fichier dans "#ajouter_document",
 * que sa taille ne dépasse pas 100 Mo (doit rester cohérent avec
 * _CCN_UPLOAD_TAILLE_MAX_MO, cf plugins/ccn/ccn_options.php) et affiche
 * un message d'erreur dans le ".saisie_document_forum" englobant sinon.
 */
$(document).off('change', '#ajouter_document').on('change', '#ajouter_document', function () {
	console.log("check file size");

	var poidsMaxi = 100 * 1024 * 1024; // 100 Mo
	var fichier = this.files[0];

	var $conteneur = $(this).closest('.saisie_document_forum');
	var $spanErreur = $conteneur.find('.erreur_message');

	if ($spanErreur.length === 0) {
		$spanErreur = $('<span class="erreur_message"></span>');
		$conteneur.prepend($spanErreur);
	}

	if (fichier && fichier.size > poidsMaxi) {
		$spanErreur.text(CCN.lang.taille_maximale_document_forum);
		$conteneur.addClass('erreur');
		$(this).val('');
	} else {
		$spanErreur.text('');
		$conteneur.removeClass('erreur');
	}
});
