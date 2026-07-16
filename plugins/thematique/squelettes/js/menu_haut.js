(function ($) {

	$(function () {

		if ($('#menu_haut_titre').width() <= 185) {
			$('#annee_scolaire_more').hide();
		}

		function closeAllSelects() {
			$('.selectbox.open').each(function () {
				clearTimeout($(this).data('closeTimeout'));
				$(this).removeClass('open');
			});
		}

		function initSelectbox($roots) {
			$roots.each(function () {
				var $sel = $(this);
				if ($sel.hasClass('initialized')) return;
				$sel.addClass('initialized');
				var $toggle = $sel.find('.select-toggle').first();
				var $options = $sel.find('.select-options').first();
				if (!$toggle.length) return;

				// Click to toggle
				$toggle.on('click', function (e) {
					e.stopPropagation();
					var wasOpen = $sel.hasClass('open');
					closeAllSelects();
					if (!wasOpen) $sel.addClass('open');
				});

				// Hover to open
				$sel.on('mouseenter', function () {
					clearTimeout($sel.data('closeTimeout'));
					closeAllSelects();
					$sel.addClass('open');
				});

				$sel.on('mouseleave', function () {
					$sel.data('closeTimeout', setTimeout(function () {
						$sel.removeClass('open');
					}, 200));
				});

				// Click on option
				$options.on('click', '.select-option', function (e) {
					e.stopPropagation();
					var $li = $(this);
					if ($li.hasClass("actif")) {
						return;
					}
					var val = $li.data('value');
					var text = $li.html();
					// update visible label — first non-icon span
					$toggle.find('span').not('.icon').first().html(text);
					$options.find('.select-option').removeClass('actif');
					$li.addClass('actif');
					$sel.removeClass('open');
					// react to known select names
					var name = $sel.attr('data-select-name') || $sel.attr('name');
					if (name == 'annee_scolaire') {
						// ccn_options.php lit ?annee_scolaire= et pose le cookie côté serveur
						var url = new URL(window.location.href);
						url.searchParams.set('annee_scolaire', val);
						window.location.href = url.toString();
					}
					// rubrique_admin (menu "Publier") : chaque option a déjà son propre
					// onClick (createReponse, callRessource, changeTimelineMode...) qui fait
					// l'action en JS ; un reload ici l'interromprait.
				});
			});
		}

		initSelectbox($('.selectbox'));

		// close on outside click
		$(document).on('click', function () { closeAllSelects(); });
	});

})(jQuery);
