jQuery(function(){
	saisies_fieldset_pliable();
	saisies_fieldset_onglet();
	saisies_multi_novalidate();
	onAjaxLoad(saisies_fieldset_pliable);
	onAjaxLoad(saisies_fieldset_onglet);
	onAjaxLoad(saisies_multi_novalidate);
});

/**
 * Rend certains fieldsets pliables
 *
 * Il s'agit des fieldsets portant les classes "fieldset.pliable"
 * Non cumulable avec les fieldsets en onglets.
 */
function saisies_fieldset_pliable(){
	// On cherche les groupes de champs pliables
	jQuery('.fieldset.pliable')
		.each(function(){
			var fieldset = jQuery(this);
			var groupe = jQuery(this).find('> .editer-groupe');
			var legend = jQuery(this).find('> legend');

			// On éviter de plier un fieldset qui contient des erreurs lors de
			// l'initialisation.
			if (fieldset.find('.erreur').length > 0) {
				fieldset.removeClass('plie');
			}

			// S'il est déjà plié on cache le contenu
			if (fieldset.is('.plie'))
				groupe.hide();

			// Ensuite on ajoute une action sur le titre
			legend
				.unbind('click')
				.click(
					function(){
						fieldset.toggleClass('plie');
						if (groupe.is(':hidden'))
							groupe.show();
						else
							groupe.hide();
					}
				);
		});
};

/**
 * Tranforme certains fieldsets en onglets
 *
 * - Ceux portant les classes "fieldset.fieldset_onglet".
 * - Accessible à l'exception de la navigation au clavier.
 * - Les onglets sont persistants si les fieldsets possèdent un id ou un data-id.
 * - Non cumulable avec les fieldsets pliables.
 *
 * Markup inspiré de https://van11y.net/fr/onglets-accessibles/
 */
function saisies_fieldset_onglet() {

	// Classes utilisées
	var classes = {
		// conteneur général
		wrapper_horizontal: 'saisies-onglets',
		wrapper_vertical: 'saisies-onglets saisies-onglets-verticaux',
		// Menu
		tablist:       'saisies-menu-onglets',
		tablist_items: 'saisies-menu-onglets__items',
		tablist_item:  'saisies-menu-onglets__item',
		tablist_link:  'saisies-menu-onglets__lien',
		active:        'actif',
		error:         'erreur',
		scrollable:    'scrollable',
		// contenus (les fieldsets)
		tabscontents:  'saisies-contenus-onglets',
		tabcontent:    'saisies-contenu-onglet afficher_si_sans_visuel', // en complément de .fieldset_onglet
	}
	var selecteur_fieldset = '.fieldset.fieldset_onglet:not(.pliable)';
	var storage = window.sessionStorage;
	// Générer les onglets
	var init = function() {
		$.each(collections_fieldsets(), function(i, $fieldsets) {
			// On peut avoir des onglets horizontaux dans les onglets verticaux, donc il faut reinitialiser à chaque niveau
			classes.wrapper = classes.wrapper_horizontal;
			// A-t-on ne serait-ce qu'un onglet vertical ? Si oui, on considère que tout est en vertical
			$fieldsets.each(function() {
				if ($(this).hasClass('fieldset_onglet_vertical')) {
					classes.wrapper = classes.wrapper_vertical;
					return false;
				}
			});

			var
				$conteneur				= $('<div class="'+classes.wrapper+'"></div>'),
				$menu							= $('<nav class="'+classes.tablist+'"><ul class="'+classes.tablist_items+'" role="tablist"></ul></nav>'),
				$contenus					= $('<div class="'+classes.tabscontents+'"></div>'),
				ids_contenus			= [],
				$first_fieldset		= $fieldsets.first(),
				id_menu						= null;

			// On insère un conteneur général avant le premier fieldset de la série
			// puis celui des contenus à l'intérieur.
			$conteneur.append($contenus).insertBefore($first_fieldset);

			// On parcourt la série de fieldsets pour préparer
			// les entrées du menu, les interactions et les contenus
			$fieldsets.each(function() {

				var
					$contenu      = $(this),
					id_persistant = $contenu.attr('id') || $contenu.attr('data-id'),
					afficher_si		= $contenu.attr('data-afficher_si') || 'true',
					id_contenu    = id_persistant || randomId(),
					id_onglet     = 'onglet-' + id_contenu;

				// On ajoute les attributs nécessaire : id, classe, role et aria
				// puis on le cache d'office et on le déplace dans le conteneur.

				$contenu
					.attr('id', id_contenu)
					.addClass(classes.tabcontent)
					.attr('role', 'tabpanel')
					.attr('aria-labelledby', id_onglet)
					.attr('data-saisies-onglet', true) // pour s'assurer de ne pas passer plusieurs fois
					.hide().attr('hidden', '')
					.appendTo($contenus);

				// On récupère le titre et on le cache
				var titre = $contenu.find('legend').first().hide().text();

				// On crée l'onglet avec son interaction
				var $onglet = $('<li class="'+classes.tablist_item+'"><a class="'+classes.tablist_link+'" href="#'+id_contenu+'" id="'+id_onglet+'" aria-controls="'+id_contenu+'" role="tab" aria-selected="false" tabindex="-1">'+titre+'</a></li>');
				var autoriser_changement = false;
				$onglet
					.attr('data-afficher_si', afficher_si)
					.click(function() {
						var sibling_active = $(this).siblings().has('.' + classes.active);
						sibling_active.each(function() {
							var onglet_reference = $(this).find('a').attr('href');
							autoriser_changement = container_reportValidity(onglet_reference);
						});
						if (!sibling_active.length) {
							autoriser_changement = true;
						}
						if (autoriser_changement) {
							activer_onglet($(this).find('.'+classes.tablist_link));
						}
						return false;
					});

				// Lorsqu'on masque l'onglet avec afficher_si, désactiver l'onglet, puis se rendre si possible au premier onglet
				$onglet.on('afficher_si_masque_pre', function() {
					$this_onglet = $(this);
					$lien = $this_onglet.children('a');
					// Si c'est onglet actif, on ferme l'onglet et on cherche le premier onglet dispo
					if ($lien.attr('aria-selected') == 'true') {
						$nouvel_onglet = $this_onglet.siblings().not('.afficher_si_masque').first().children('a')
						desactiver_onglet($lien);//On désactive dans tous les cas l'onglet courant, car cela se trouve il n'y aura pas d'autres onglets à activer
						activer_onglet($nouvel_onglet);
					}
				});


				// Lorsqu'on rend visible l'onglet après un afficher_si, si c'est le seul, y aller directement
				$onglet.on('afficher_si_visible_post', function() {
					$voisins = $(this).siblings().not('.afficher_si_masque');
					if (!$voisins.length) {
						activer_onglet($onglet.children('a'));
					}
				});


				// On note l'id persistant
				if (id_persistant) {
					ids_contenus.push(id_persistant);
				}

				// S'il y a des erreurs dans cette partie du contenu, on met une classe "erreur" à l'onglet aussi
				if ($contenu.find('.editer.erreur').length) {
					$onglet.children('a').addClass(classes.error);
				}
				// On ajoute l'onglet au menu
				$menu.find('.'+classes.tablist_items).append($onglet);
			});

			// On insère le menu dans le DOM.
			// Si *tous* les fieldsets on un id persistant, on peut s'en servir pour celui du menu,
			// ce qui permet la navigation persistante.
			// l'id du menu sera utilisé comme clé dans la session, on le simplifie avec un hash.
			if (ids_contenus.length === $fieldsets.length) {
				id_menu = 'onglets-'+hashCode(ids_contenus.join(''));
				$menu.attr('data-id', id_menu);
			}
			$menu.prependTo($conteneur);

			// Indiquer si le menu doit être scrollable
			if ($menu[0].scrollWidth > $menu[0].clientWidth) {
				$menu.addClass(classes.scrollable);
			}

			// On active l'onglet par défaut, par ordre de priorité :
			//  - le premier avec une erreur au sein de son groupe d'onglets
			//	- celui en session s'il existe
			//	- le 1er trouvé
			var $onglet_defaut;
			if ($('.' + classes.tablist_link + '.' + classes.error).length > 0) {
				$onglet_defaut = $menu.find('.' + classes.tablist_link + '.' + classes.error).first();
			} else if (storage.getItem(id_menu) !== null && $('#'+escapeId(storage.getItem(id_menu))).length > 0) {
				$onglet_defaut = $('#'+escapeId(storage.getItem(id_menu)));
			} else {
				$onglet_defaut = $menu.find('.'+classes.tablist_link).first();
			}
			activer_onglet($onglet_defaut, 0, false);

		});

		// Si un problème de validation sur un champ, basculer vers le fieldset qui contient l'élèment invalide
		// Note : on initialise cela en dehors de l'initialisation des onglets
		// Car un même champ peut être dans plusieurs onglets (onglets imbriqués)
		// Et on ne veut pas de double écouteur
		$('.' + classes.tabscontents + ' [name]').on('invalid', function() {
			// On fait cela lot d'onglets par lot d'onglets
			$(this).parents('form').find('.' + classes.tabscontents).each(function() {
				var id_fieldset = '#' + $(this).find('> fieldset:invalid').first().attr('id');
				var $onglet = $('a[href="' + id_fieldset + '"]');
				activer_onglet($onglet);
			});
		});
	}

	// Retourne un tableau de collections de fieldsets
	// par log de fieldset coté à cote
	var collections_fieldsets = function() {
		var collections = [];
		$(selecteur_fieldset).each( function() {
			$fieldsets_niveau = $(this).add($(this).nextUntil(':not('+selecteur_fieldset+')')),
			parsed = $(this).data('saisies-onglet-parsed') || false;
			if (!parsed) {
				collections.push($fieldsets_niveau);
				$fieldsets_niveau.each( function() {
					$(this).data('saisies-onglet-parsed', true);
				});
			}
		});
		return collections;
	}

	// Activer un onglet
	// en commencant par désactiver son voisin
	// @param object $onglet Élément <a>
	var activer_onglet = function( $onglet, duree = 150, persistant = true ) {
		$onglet_actuel = $onglet.parent().siblings().has('.' + classes.active).find('.' + classes.tablist_link);
		desactiver_onglet($onglet_actuel);
		if ($onglet.length) {
			var $contenu = $(escapeId($onglet.attr('href')));
			$onglet.addClass(classes.active).attr('aria-selected', true).removeAttr('tabindex');
			$contenu.fadeIn(duree).removeAttr('hidden');
			// Mettre en session si on a ce qu'il faut
			var id_menu = $onglet.parents('.'+classes.tablist).attr('data-id') || null;
			if (persistant && id_menu) {
				storage.setItem(id_menu, $onglet.attr('id'));
			}
		}
	}

	// Désactiver un onglet
	// @param object $onglet Élément <a>
	var desactiver_onglet = function( $onglet, duree = 150 ) {
		if ($onglet.length) {
			var $contenu = $(escapeId($onglet.attr('href')));
			$onglet.removeClass(classes.active).attr('aria-selected', false).attr('tabindex', -1);
			$contenu.hide().attr('hidden', '');
		}
	}

	// Échapper les ids pour ne pas faire couiner jQuery
	var escapeId = function ( id ) {
		id = (id || '').replace(/[^\d\w_\-\#]/gi, '\\$&');
		return id;
	}

	// Retourne un identifiant aléatoire
	// https://stackoverflow.com/a/8084248
	var randomId = function (taille = 8) {
		var random = (Math.random() + 1).toString(36);
		return random.substring(random.length - taille);
	}

	// Hash simple et rapide
	// https://gist.github.com/hyamamoto/fd435505d29ebfa3d9716fd2be8d42f0
	var hashCode = function(s) {
		for (var i = 0, h = 0; i < s.length; i++)
			h = Math.imul(31, h) + s.charCodeAt(i) | 0;
		return Math.abs(h);
	}

	/*
	 * Vérifier la validité de l'ensemble des champs contenu dans element
	 * @param string selector : un critère de selection
	 * @return bool
	**/
	var container_reportValidity = function(selector) {
		var retour = true;
		$(selector).find('[name]').each(function() {
			retour = retour && $(this).get(0).reportValidity();//Un seul `false` et tout est `false`
		});
		return retour;
	}
	// C'est parti
	init();
}

function saisies_date_jour_mois_annee_changer_date(me, datetime) {
	var champ = jQuery(me);
	var li = champ.closest('.editer');
	var	jour = jQuery.trim(li.find('.date_jour').val());
	var	mois = jQuery.trim(li.find('.date_mois').val());
	var	annee = jQuery.trim(li.find('.date_annee').val());
	var	date = jQuery.trim(li.find('.datetime').val());

	while(jour.length < 2) {jour = '0' + jour;}
	while(mois.length < 2) {mois = '0' + mois;}
	while(annee.length < 4) {annee = '0' + annee;}

	if (datetime == 'oui') {
		heure = date.substring(10);
		if (!heure || !(heure.length == 9)) {
			heure = ' 00:00:00';
		}
		date = annee + '-' + mois + '-' + jour + heure;
	}
	else {
		date = annee + '-' + mois + '-' + jour;
	}
	li.find('.datetime').attr('value', date);
}

/** Ne pas valider lors des retours arrières sur multiétape **/
function saisies_multi_novalidate() {
	$('[name^="_retour_etape"],[name="aller_a_etape"]').click(function() {
		$(this).parents('form').attr('novalidate', 'novalidate');
	});
}
