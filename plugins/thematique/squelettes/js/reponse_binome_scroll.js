function bindGotoReponseBinomeScroll(suffix) {
	$('#goto_reponse_binome_' + suffix).on('click', function () {
		var anchor = $('#reponse_binome_' + suffix);
		$('#sidebar_content, #sidebar_main_inner, #sidebar_lateral_inner').animate({ scrollTop: anchor.offset().top - 4 }, 'slow');
	});
}
