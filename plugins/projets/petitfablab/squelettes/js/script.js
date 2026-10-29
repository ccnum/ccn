// Scripts communs aux pages petitfablab (chargé par cadavrexquis/inclure/head-commun.html)

// RESIZE : hauteur du livre ajustée à la fenêtre
function resize_global_content() {
	h = $(window).height();
	h -=61;
	$('.global-content').height(h);
	stitches_margin = ((h-60) - 258)/4;
	$(".stitches-inner").css({'margin-top':stitches_margin});
}

// TOOLTIP : affiche #liste-tooltip au survol des éléments `selecteur`.
// - decalage_gauche : décalage horizontal (px) par rapport à l'élément ;
// - options.contenu($el) : HTML de l'infobulle (défaut : .liste-tooltip-content de l'élément) ;
// - options.top : position verticale fixe (défaut : au-dessus de l'élément).
function tooltip_liste(selecteur, decalage_gauche, options) {
	options = options || {};
	$(selecteur).mouseover(function() {
		var offset = $(this).offset();
		var content_tooltip = options.contenu
			? options.contenu($(this))
			: $(this).find('.liste-tooltip-content').html();
		$('#liste-tooltip').show();
		$('#liste-tooltip').html(content_tooltip);
		var hauteur_tooltip = $('#liste-tooltip-content').height();
		$('#liste-tooltip').height(hauteur_tooltip);
		var toptooltip = options.top !== undefined
			? options.top
			: Math.round(offset.top) - hauteur_tooltip - 5;
		var lefttooltip = Math.round(offset.left) - decalage_gauche;
		$('#liste-tooltip').css('top', toptooltip);
		$('#liste-tooltip').css('left', lefttooltip + 2);
	});
	$(selecteur).mouseout(function() {
		$('#liste-tooltip').hide();
	});
}
