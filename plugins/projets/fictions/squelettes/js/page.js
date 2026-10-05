$(document).ready(function() {
	window.onresize = resize_global_content;
	resize_global_content();

	$('.liste-link').on('mouseover focus', function() { afficher_tooltip_liste(this, -93); });
	$('.liste-link').on('mouseout blur', function() { $('#liste-tooltip').hide(); });
});