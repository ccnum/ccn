$(document).ready(function() {
	window.onresize = resize_global_content;
	resize_global_content();

	$('.liste-link').on('mouseover focusin', function() { afficher_tooltip_liste(this, -93); });
	$('.liste-link').on('mouseout focusout', function() { $('#liste-tooltip').hide(); });
});