$(document).ready(function() {
	window.onresize = resize_global_content;
	resize_global_content();

	function showTooltipListeLink(el) {
		var offset = $(el).offset();
		var content_tooltip = $(el).find('.liste-tooltip-content').html();
		$('#liste-tooltip').show().html(content_tooltip);
		var hauteur_tooltip = $('#liste-tooltip .liste-tooltip-inner').height();
		$('#liste-tooltip').height(hauteur_tooltip);
		$('#liste-tooltip').css('top', Math.round(offset.top) - hauteur_tooltip - 5);
		$('#liste-tooltip').css('left', Math.round(offset.left) - 95 + 2);
	}
	$('.liste-link').on('mouseover focus', function() { showTooltipListeLink(this); });
	$('.liste-link').on('mouseout blur', function() { $('#liste-tooltip').hide(); });
});