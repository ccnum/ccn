$(document).ready(function() {
	window.onresize = resize_global_content;
	resize_global_content();

	var compteur = parseFloat($('body').data('compteur'));
	var num_page_limite = $('.content-left > div').length;

	$('.lecture-page').hide();
	pagination(compteur);
	next_prev(compteur, num_page_limite);

	$('#bt-prev').click(function(event) {
		event.preventDefault();
		compteur -= 2;
		pagination(compteur);
		next_prev(compteur, num_page_limite);
	});
	$('#bt-next').click(function(event) {
		event.preventDefault();
		compteur += 2;
		pagination(compteur);
		next_prev(compteur, num_page_limite);
	});
});
