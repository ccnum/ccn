/**
 * Widget favoris CCN (réactions emoji) : clic en délégation sur
 * .favoris-ccn-widget, pour fonctionner même quand ce fragment est
 * réinséré par AJAX (loadContentInMainSidebar, etc.) sans que ce
 * script soit rechargé.
 *
 * L'état initial (compteurs, aria-pressed, aria-label) est rendu côté
 * SPIP (cf inclure/favoris_ccn.html) : ce script ne gère que le clic.
 */
$(document).on('click', '.favoris-ccn-widget .favori-ccn-btn', function () {
	var $b      = $(this);
	var $widget = $b.closest('.favoris-ccn-widget');
	var url     = $b.data('action');

	$.getJSON(url, function (data) {
		if (!data.ok) {
			return;
		}
		$widget.find('.favori-ccn-btn').each(function () {
			var $btn   = $(this);
			var cat    = $btn.data('categorie');
			var label  = $btn.data('label');
			var nb     = data.compteurs[cat] || 0;
			var actif  = (data.categorie === cat);

			$btn.find('.favori-ccn-count').text(nb);
			$btn.toggleClass('actif', actif).attr('aria-pressed', actif ? 'true' : 'false');
			$btn.attr('aria-label', 'Réagir avec ' + label + ', ' + nb + ' réaction' + (nb === 1 ? '' : 's'));
		});
	});
});
