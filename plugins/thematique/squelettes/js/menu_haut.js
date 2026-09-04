/**
 * Menu haut : masque le sélecteur d'année scolaire si le titre n'a pas la
 * place de s'afficher, et gère l'ouverture/fermeture des ".selectbox"
 * (sélecteur d'année scolaire, menu "Publier"...).
 */
(function ($) {

	$(function () {

		if ($('#menu_haut_titre').width() <= 185) {
			$('#annee_scolaire_more').hide();
		}

		// La modal de login SPIP natif se ferme au rechargement de page (pas
		// de classe .open par défaut). En cas d'erreur (identifiants
		// invalides), le formulaire se réaffiche avec le message dans le
		// HTML, mais caché derrière la modal fermée si on ne la rouvre pas.
		if ($('#login_modal .reponse_formulaire_erreur').text().trim() !== '') {
			$('#login_modal').addClass('open');
		}

		/**
		 * Referme toutes les ".selectbox" actuellement ouvertes.
		 */
		function closeAllSelects() {
			$('.selectbox.open').each(function () {
				clearTimeout($(this).data('closeTimeout'));
				$(this).removeClass('open');
			});
		}

		/**
		 * Initialise le comportement d'un ensemble de ".selectbox" :
		 * ouverture au clic/survol, fermeture au clic extérieur, sélection
		 * d'une option (avec mémorisation visuelle si ".save-choice").
		 * Idempotent (marque chaque élément ".initialized").
		 *
		 * @param {jQuery} $roots - Éléments ".selectbox" à initialiser
		 */
		function initSelectbox($roots) {
			$roots.each(function () {
				var $sel = $(this);
				if ($sel.hasClass('initialized')) return;
				$sel.addClass('initialized');
				var rememberChoice = $sel.hasClass('save-choice')
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

					// Option "Se connecter" (formulaire de login SPIP natif,
					// CIOIDC désactivé) : ouvre la modal dédiée plutôt que de
					// traiter le clic comme une sélection d'option classique.
					// stopPropagation() ci-dessus empêche tout listener délégué
					// sur document de voir ce clic, d'où l'ouverture ici même.
					if ($li.find('.js-ouvrir-login-modal').length) {
						e.preventDefault();
						$('#login_modal').addClass('open');
						$sel.removeClass('open');
						return;
					}

					if (rememberChoice && $li.hasClass("actif")) {
						return;
					}
					var val = $li.data('value');
					var text = $li.html();
					// update visible label — first non-icon span
					$toggle.find('span').not('.icon').first().html(text);
					$options.find('.select-option').removeClass('actif');
					if(rememberChoice) {
						$li.addClass('actif');
					}
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

		// Clic sur le fond de la modal de login (en dehors de
		// .login-modal-content) : on ferme.
		$(document).on('click', '.login-modal.open', function (e) {
			if (e.target === this) {
				$(this).removeClass('open');
			}
		});
	});

})(jQuery);
