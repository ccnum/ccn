/**
 * Branche un clic sur "#goto_reponse_binome_{suffix}" pour scroller la
 * sidebar jusqu'à la réponse du binôme correspondante
 * ("#reponse_binome_{suffix}").
 *
 * @param {string} suffix - Identifiant partagé entre le lien et son ancre
 */
function bindGotoReponseBinomeScroll(suffix) {
	$('#goto_reponse_binome_' + suffix).on('click', function () {
		var anchor = $('#reponse_binome_' + suffix);
		$('#sidebar_content, #sidebar_main_inner, #sidebar_lateral_inner').animate({ scrollTop: anchor.offset().top - 4 }, 'slow');
	});
}
