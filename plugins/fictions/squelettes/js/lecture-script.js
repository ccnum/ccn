$(document).ready(function() {
	// LARGEUR CHAMP FORUM
	setInterval(function() { $('.forum').find('textarea').attr('cols', '100'); }, 250);

	// BT CLOSE
	$('#forum-inner-close').click(function() {
		parent.$.fn.mediabox.close();
	});
});
