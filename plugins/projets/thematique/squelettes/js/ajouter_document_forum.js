/**
 * Vérifie côté client, au choix d'un fichier joint à un commentaire, que sa
 * taille ne dépasse pas 100 Mo (doit rester cohérent avec
 * _CCN_UPLOAD_TAILLE_MAX_MO, cf plugins/projets/ccn/ccn_options.php) et
 * affiche un message d'erreur dans le ".saisie_document_forum" englobant sinon.
 */
$(document).off('change', '.js-forum-document-fichier').on('change', '.js-forum-document-fichier', function () {
	var poidsMaxi = 100 * 1024 * 1024; // 100 Mo
	var fichier = this.files[0];

	var $conteneur = $(this).closest('.saisie_document_forum');
	var $spanErreur = $conteneur.find('.erreur_message');

	if ($spanErreur.length === 0) {
		$spanErreur = $('<p class="erreur_message" role="alert"></p>');
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
