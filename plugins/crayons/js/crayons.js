(function ($) {
	/*
	 *	crayons.js (c) Fil, toggg, 2006-2023 -- licence GPL
	 */

	// le prototype configuration de Crayons
	$.prototype.cfgCrayons = function (options) {
		this.url_crayons_html = '?action=crayons_html';
		this.img = {
			'searching': { 'txt': 'En attente du serveur ...' },
			'edit': { 'txt': 'Editer' },
			'img-changed': { 'txt': 'Deja modifie' }
		};
		this.txt = {
		};
		for (opt in options) {
			this[opt] = options[opt];
		}
	};

	$.prototype.cfgCrayons.prototype.mkimg = function (what, extra) {
		const txt = this.img[what] ? this.img[what].txt : this.img['crayon'].txt;
		return '<em class="crayon-' + what + '" title="' + txt + (extra ? extra : '') + '"></em>';
	};

	$.prototype.cfgCrayons.prototype.iconclick = function (c, type) {

		// le + qui passe en prive pour editer tout si classe type--id
		let link = c.match(/\b(\w+)--(\d+)\b/);
		link = link ?
			'<a href="ecrire/?exec=' + link[1] + 's_edit&id_' + link[1] + '=' + link[2] +
			'">' + this.mkimg('edit', ' (' + link[1] + ' ' + link[2] + ')') + '</a>' : '';

		// on recherche une class du type type-champ-id
		// comme article-texte-10 pour le texte de l'article 10
		// ou meta-valeur-meta
		const cray =
			c.match(/\b\w+-(\w+)-\d(?:-\w+)+\b/)   // numeros_lien-type-2-3-article (table-champ-cles)
			|| c.match(/\b\w+-(\w+)-\d+\b/)           // article-texte-10 (inclu dans le precedent, mais bon)
			|| c.match(/\b\meta-valeur-(\w+)\b/)      // meta-valeur-xx
			;

		const boite = !cray ? '' : this.mkimg(type, ' (' + cray[1] + ')');

		return "<span class='crayon-icones'><span>" + boite +
			this.mkimg('img-changed', cray ? ' (' + cray[1] + ')' : '') +
			link + "</span></span>";
	};

	function entity2unicode(txt) {
		const reg = txt.split(/&#(\d+);/i);
		for (let i = 1; i < reg.length; i += 2) {
			reg[i] = String.fromCharCode(parseInt(reg[i]));
		}
		return reg.join('');
	};

	function uniAlert(txt) {
		alert(entity2unicode(txt));
	};

	function uniConfirm(txt) {
		return confirm(entity2unicode(txt));
	};

	// donne le crayon d'un element
	$.fn.crayon = function () {
		if (this.length) {
			return $(
				$.map(this, function (a) {
					return '#' + ($(a).find('.crayon-icones').attr('rel'));
				}).join(','));
		} else {
			return $([]);
		}
	};

	// ouvre un crayon
	$.fn.opencrayon = function (evt, percent) {
		if (evt && evt.stopPropagation) {
			evt.stopPropagation();
		}
		if (evt) {
			evt.preventDefault();
		}
		return this
			.each(function () {
				const $me = $(this);
				// verifier que je suis un crayon
				if (!$me.is('.crayon')) {
					return;
				}

				// voir si je dispose deja du crayon comme voisin
				if ($me.is('.crayon-has')) {
					$me
						.css('visibility', 'hidden')
						.crayon()
						.show();
				}
				// sinon charger le formulaire
				else {
					// sauf si je suis deja en train de le charger (lock)
					if ($me.is('.crayon-loading')) {
						return;
					}
					$me
						.addClass('crayon-loading')
						.find('>span.crayon-icones span')
						.append(configCrayons.mkimg('searching')); // icone d'attente
					const offset = $me.offset();
					const params = {
						'top': offset.top,
						'left': offset.left,
						'w': $me.width(),
						'h': $me.height(),
						'ww': (window.innerWidth ? window.innerWidth : (document.documentElement.clientWidth ? document.documentElement.clientWidth : document.body.offsetWidth)),
						'wh': (window.innerHeight ? window.innerHeight : (document.documentElement.clientHeight ? document.documentElement.clientHeight : document.body.offsetHeight)),
						'em': $me.css('fontSize'), // Bug de jquery resolu : http://bugs.jquery.com/ticket/760
						'class': this.className,
						'color': $me.css('color'),
						'font-size': $me.css('fontSize'),
						'font-family': $me.css('fontFamily'),
						'font-weight': $me.css('fontWeight'),
						'line-height': $me.css('lineHeight'),
						'min-height': $me.css('lineHeight'),
						'text-align': $me.css('textAlign'),
						'background-color': $me.css('backgroundColor'),
						'self': configCrayons.self
					};
					if (this.type) {
						params.type = this.type;
					}
					if (
						params['background-color'] == 'transparent'
						|| params['background-color'] == 'rgba(0, 0, 0, 0)'
					) {
						$me.parents()
							.each(function () {
								const bg = $(this).css('backgroundColor');
								if (bg != 'transparent' && (
									params['background-color'] == 'transparent'
									|| params['background-color'] == 'rgba(0, 0, 0, 0)'
								)) {
									params['background-color'] = bg;
								}
							});
					}
					$.post(configCrayons.url_crayons_html,
						params,
						function (c) {
							try {
								c = JSON.parse(c);
							} catch (e) {
								c = { '$erreur': 'erreur de communication :' + '  ' + e.message, '$html': '' };
							}
							$me
								.removeClass('crayon-loading')
								.find("em.crayon-searching")
								.remove();
							if (c.$erreur) {
								uniAlert(c.$erreur);
								return false;
							}
							id_crayon++;

							let position = 'absolute';
							$me.parents().each(function () {
								if ($(this).css("position") == "fixed")
									position = 'fixed';
							});

							$me
								.css('visibility', 'hidden')
								.addClass('crayon-has')
								.find('>.crayon-icones')
								.attr('rel', 'crayon_' + id_crayon);
							// Detection IE sur sa capacite a gerer zoom :
							// http://www.sitepoint.com/detect-css3-property-browser-support/
							if (document.createElement("detect").style.zoom === "") {
								$me.css({ 'zoom': 1 });
							}
							const pos = $me.offset();
							const styles = {
								'position': position,
								'top': pos['top'] - 1,
								'left': pos['left'] - 1
							};
							const zindex = $me.zindexcrayon();
							if (zindex) {
								styles['z-index'] = zindex + 1;
							}
							$('<div class="crayon-html" id="crayon_' + id_crayon + '"></div>')
								.css(styles)
								.appendTo('body')
								.html(c.$html);
							$me.activatecrayon(percent);
							// Si le crayon a une taille mini qui le fait deborder
							// a droite de l'ecran, recadrer vers la gauche
							const diff = $('#crayon_' + id_crayon).offset().left + $('#crayon_' + id_crayon).width() - $(window).width();
							if (diff > 0) {
								$('#crayon_' + id_crayon)
									.css({ 'left': parseInt(pos['left']) - diff });
							}
						}
					);
				}
			});
	};

	// trouver le zindex des crayons en remontant les parents
	// pour voir si un z-index est imposé quelque part
	$.fn.zindexcrayon = function () {
		let zindex = 0;
		this.parents().each(function () {
			const thiszindex = parseInt(jQuery(this).css('z-index'));
			if (thiszindex && thiszindex > zindex) {
				zindex = thiszindex;
			}
		});
		return zindex;
	}

	// annule le crayon ouvert (fonction destructive)
	$.fn.cancelcrayon = function () {
		this
			.filter('.crayon-has')
			.css('visibility', 'visible')
			.removeClass('crayon-has')
			.removeClass('crayon-changed')
			.crayon()
			.remove();
		return this;
	};

	// masque le crayon ouvert
	$.fn.hidecrayon = function () {
		this
			.filter('.crayon-has')
			.css('visibility', 'visible')
			.crayon()
			.hide()
			.removeClass('crayon-hover');
		return this;
	};

	$.fn.restorecrayon = function (showButtons) {
		this
			.removeClass('crayon-loading')
			.find("em.crayon-searching")
			.remove();
		if (showButtons) {
			this
				.find(".crayon-boutons,.resizehandle")
				.show()
		}
		return this;
	}

	// active un crayon qui vient d'etre charge
	$.fn.activatecrayon = function (percent) {
		let focus = false;
		this
			.crayon()
			.on("click", function (e) {
				e.stopPropagation();
			});
		this
			.each(function () {
				const me = $(this);
				const crayon = $(this).crayon();
				crayon
					.find('form')
					.append(
						$('<input type="hidden" name="self" />')
							.attr('value', configCrayons.self)
					)
					.ajaxForm({
						"dataType": "json",
						"error": function (d) {
							uniAlert('erreur de communication');
							crayon
								.restorecrayon(true)
								.prepend(
									$('<div class="error">')
										.html(d.responseText || d.statusText || 'erreur inconnue')
								)
								;
						},
						"success": function (d) {
							// parfois le JSON n'est pas renvoye sous forme d'objet
							// mais d'une chaine encadree de <pre>...</pre>
							if (typeof d == "string") {
								try {
									d = JSON.parse(d.replace(/^<pre>/, '').replace(/<[/]pre>$/, ''));
								} catch (e) {
									d = { '$erreur': 'erreur de communication :' + '  ' + e.message, '$html': '' };
								}
							}
							me.restorecrayon(false);

							//Remise a zero des warnings invalides (unwrap)
							crayon
								.find("span.crayon-invalide p")
								.remove();
							crayon
								.find("span.crayon-invalide")
								.each(function () {
									$(this).replaceWith(this.childNodes);
								}
								);

							if (d.$invalides) {
								for (let invalide in d.$invalides) {
									let retour, msg;
									//Affichage des warnings invalides
									d.$invalides[invalide]['retour'] ? retour = d.$invalides[invalide]['retour'] : retour = '';
									d.$invalides[invalide]['msg'] ? msg = d.$invalides[invalide]['msg'] : msg = '';
									crayon
										.find("*[name='content_" + invalide + "']")
										.wrap("<span class=\"crayon-invalide\"></span>")
										.parent()
										.append("<p>"
											+ retour
											+ " "
											+ msg
											+ "</p>"
										);
								}

							}

							if (d.$erreur > '') {
								if (d.$annuler) {
									if (d.$erreur > ' ') {
										uniAlert(d.$erreur);
									}
									me
										.cancelcrayon();
								} else {
									uniAlert(d.$erreur + '\n' + configCrayons.txt.error);
								}
							}

							if (d.$erreur > '' || d.$invalides) {
								crayon.restorecrayon(true);
								return false;
							}
							// Desactive celui pour qui on vient de recevoir les nouvelles donnees
							$(me).cancelcrayon();
							// Insere les donnees dans *tous* les elements ayant le meme code
							const tous = $(
								'.crayon.crayon-autorise.' +
								me[0].className.match(/crayon ([^ ]+)/)[1]
							)
								.html(
									d[$('input.crayon-id', crayon).val()]
								)
								.iconecrayon();

							// Invalider des préchargements ajax
							if (typeof jQuery.spip == 'object' && typeof jQuery.spip.preloaded_urls == 'object') {
								jQuery.spip.preloaded_urls = {};
							}

							// Declencher le onAjaxLoad normal de SPIP
							if (typeof jQuery.spip == 'object' && typeof jQuery.spip.triggerAjaxLoad == 'function') {
								jQuery.spip.triggerAjaxLoad(tous.get());
							}
						}
					})
					.on('form-submit-validate', function (form, a, e, options, veto) {
						if (!veto.veto)
							crayon
								.addClass('crayon-loading')
								.find('form')
								.after(configCrayons.mkimg('searching')) // icone d'attente
								.find(".crayon-boutons,.resizehandle")
								.hide();
					})
					// keyup pour les input et textarea ...
					.on("keyup", function (e) {
						crayon
							.find(".crayon-boutons")
							.show();
						me
							.addClass('crayon-changed');
						e.cancelBubble = true; // ne pas remonter l'evenement vers la page
					})
					// ... change pour les select : ici on submit direct, pourquoi pas
					.on("change", function (e) {
						crayon
							.find(".crayon-boutons")
							.show();
						me
							.addClass('crayon-changed');
						e.cancelBubble = true;
					})
					.on("keypress", function (e) {
						let maxh = this.className.match(/\bmaxheight(\d+)?\b/);
						if (maxh) {
							maxh = maxh[1] ? parseInt(maxh[1]) : 200;
							maxh = this.scrollHeight < maxh ? this.scrollHeight : maxh;
							if (maxh > this.clientHeight) {
								$(this).css('height', maxh + 'px');
							}
						}
						e.cancelBubble = true;
					})
					// focus par defaut (crayons sans textarea/text, mais uniquement menus ou fichiers)
					.find('select:visible:not(:disabled):not([readonly]):first').focus().end()
					.find('input:visible:not(:disabled):not([readonly]):first').focus().end()
					.find("textarea.crayon-active,input.crayon-active[type=text]")
					.each(function (n) {
						// focus pour commencer a taper son texte directement dans le champ
						// sur le premier textarea non readonly ni disabled
						// on essaie de positionner la selection (la saisie) au niveau du clic
						// ne pas le faire sur un input de [type=file]
						if (n == 0) {
							if (!$(this).is(':disabled, [readonly]')) {
								this.focus();
								focus = true;
							}
							// premiere approximation, en fonction de la hauteur du clic
							var position = parseInt(percent * this.textLength);
							this.selectionStart = position;
							this.selectionEnd = position;
						} else if (!focus && !$(this).is(':disabled, [readonly]'))
							this.focus();
					})
					.end()
					.on("keydown", function (e) {
						// Clavier pour sauver
						if (
							(!e.charCode && e.keyCode == 119 /* F8, windows */) ||
							(e.ctrlKey && (
								/* ctrl-s ou ctrl-maj-S, firefox */
								((e.charCode || e.keyCode) == 115) || ((e.charCode || e.keyCode) == 83))
								/* ctrl-s, safari */
								|| (e.charCode == 19 && e.keyCode == 19)
							) ||
							(
								e.shiftKey && (e.keyCode == 13) /* shift-return */
							)
						) {
							e.preventDefault(); // Lorsque l'on utilise ctrl+s, on n'ouvre pas la fenêtre de sauvegarde
							crayon
								.find("form.formulaire_crayon")
								.trigger("submit");
						}
						if (e.keyCode == 27) { /* esc */
							me.cancelcrayon();
						}
					})
					.find(".crayon-submit")
					.on("click", function (e) {
						e.stopPropagation();
						$(this)
							.parents("form:eq(0)")
							.trigger("submit");
					})
					.end()
					.find(".crayon-cancel")
					.on("click", function (e) {
						e.stopPropagation();
						me.cancelcrayon();
					})
					.end()
					// decaler verticalement si la fenetre d'edition n'est pas visible
					.each(function () {
						const offset = $(this).offset();
						const hauteur = parseInt($(this).css('height'));
						const scrolltop = $(window).scrollTop();
						const h = $(window).height();
						if (offset['top'] - 5 <= scrolltop)
							$(window).scrollTop(offset['top'] - 5);
						else if (offset['top'] + hauteur - h + 20 > scrolltop)
							$(window).scrollTop(offset['top'] + hauteur - h + 30);
						// Si c'est textarea, on essaie de caler verticalement son contenu
						// et on lui ajoute un resizehandle
						$("textarea", this)
							.each(function () {
								if (percent && this.scrollHeight > hauteur) {
									this.scrollTop = this.scrollHeight * percent - hauteur;
								}
							})
							.resizehandle()
							// decaler les boutons qui suivent un resizer de 16px vers le haut
							.next('.resizehandle')
							.next('.crayon-boutons')
							.addClass('resizehandle_boutons');
					})
					.end();
				// Declencher le onAjaxLoad normal de SPIP
				// (apres donc le chargement de la page de saisie (controleur))
				if (typeof jQuery.spip == 'object' && typeof jQuery.spip.triggerAjaxLoad == 'function') {
					jQuery.spip.triggerAjaxLoad(crayon.get());
				}
			});
	};

	// insere les icones et le type de crayon (optionnel) dans l'element
	$.fn.iconecrayon = function () {
		return this.each(function () {
			const ctype = this.className.match(/\b[^-]type_(\w+)\b/);
			const type = (ctype) ? ctype[1] : 'crayon';
			if (ctype) this.type = type; // Affecte son type a l'objet crayon
			$(this).prepend(configCrayons.iconclick(this.className, type))
				.find('.crayon-' + type + ', .crayon-img-changed') // le crayon a clicker lui-meme et sa memoire
				.on("click", function (e) {
					$(this).parents('.crayon:eq(0)').opencrayon(e);
				});
		});
	};

	// initialise les crayons
	$.fn.initcrayon = function () {
		const editme = function (e) {
			timeme = null;
			$(this).opencrayon(e,
				// calcul du "percent" du click par rapport a la hauteur totale du div
				((e.pageY ? e.pageY : e.clientY) - document.body.scrollTop - this.offsetTop)
				/ this.clientHeight);
		};

		const touch_load_time = 1000; // appuyer 1 seconde
		let touch_timer = null;

		const scroll_stop_time = 250; // ne pas scroller pendant 250ms
		let scroll_timer = null;
		let scroll_disable_click_flag = false;

		window.addEventListener('scroll', () => {
			scroll_disable_click_flag = true;
			if (scroll_timer) clearTimeout(scroll_timer);
			scroll_timer = setTimeout(function() { 
				scroll_disable_click_flag = false 
			}, scroll_stop_time);
		});

		this
			.addClass('crayon-autorise' + (configCrayons.cfg.yellow_fade ? ' crayon-fade' : ''))
			.on("dblclick", editme)
			.on("touchstart", function (e) { 
				const me = this; 
				touch_timer = setTimeout(function () { 
					if (!scroll_disable_click_flag) {
						editme.apply(me, [e]); 
					}
				}, touch_load_time); 
			})
			.on("touchend", function (e) { 
				if (touch_timer) { clearTimeout(touch_timer); touch_timer = null; } 
			})
			.iconecrayon()
			.hover(	// :hover pour MSIE
				function () {
					$(this)
						.addClass('crayon-hover')
						.find('>span.crayon-icones')
						.find('>span>em.crayon-' + (this.type || 'crayon') + ',>span>em.crayon-edit')
						.show();//'visibility','visible');
				}, function () {
					$(this)
						.removeClass('crayon-hover')
						.find('>span.crayon-icones')
						.find('>span>em.crayon-' + (this.type || 'crayon') + ',>span>em.crayon-edit')
						.hide();//('visibility','hidden');
				}
			);
		return this;
	};

	// demarrage
	$.fn.crayonsstart = function () {
		if (!configCrayons.droits) return;
		id_crayon = 0; // global

		// sortie, demander pour sauvegarde si oubli
		if (configCrayons.txt.sauvegarder) {
			$(window).on("unload", function (e) {
				const chg = $(".crayon-changed");
				if (chg.length && uniConfirm(configCrayons.txt.sauvegarder)) {
					chg.crayon().find('form').trigger("submit");
				}
			});
		}

		// demarrer les crayons
		if ((typeof crayons_init_dynamique == 'undefined') || (crayons_init_dynamique == false)) {

			$(function () {
				$('body')
					.on('mouseover touchstart', '.crayon:not(.crayon-init)', function (e) {
						//console.log('over');
						//console.log(this);
						$(this)
							.addClass('crayon-init')
							.filter(configCrayons.droits)
							.initcrayon()
							.trigger('mouseover');
						if (e.type == 'touchstart')
							$(this).trigger('touchstart');
					});
			});
		}

		// un clic en dehors ferme tous les crayons ouverts ?
		if (configCrayons.cfg.clickhide)
			$("html")
				.on("click", function () {
					$('.crayon-has').hidecrayon();
				});
	};

})(jQuery);
