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
		$spanErreur.text('Taille maximale autorisée : 100 Mo.');
		$conteneur.addClass('erreur');
		$(this).val('');
	} else {
		$spanErreur.text('');
		$conteneur.removeClass('erreur');
	}
});
