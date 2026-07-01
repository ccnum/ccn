/**
 * Génère un projet.
 *
 * @constructor
 */

function Projet() {

	/**
	 * Initialise le projet.
	 *
	 * @param {Object} data - Données à affecter à l'instance
	 */
	this.init = function (data) {
		this.data = data;
		this.x = 0;
		this.xx = 0;
		this.dzoom = 0;
		this.x_dest = 0;
		this.dx = 0;
		this.y = 0;
		this.yy = 0;
		this.y_dest = 0;
		this.dy = 0;
		this.fps = data.fps;
		this.frame = -1;
		this.initTimeVariables(data.date_debut, data.date_fin);
		this.couleur_fond = data.couleur_fond;
		this.couleur_base_texte = data.couleur_base_texte;
		this.couleur_1erplan1 = data.couleur_1erplan1;
		this.couleur_1erplan2 = data.couleur_1erplan2;
		this.couleur_1erplan3 = data.couleur_1erplan3;
		this.zoom_consignes = data.zoom_consignes;
		this.timeline_parent = $('#timeline');
		this.timeline_background = $('#timeline_background');
		this.timeline_fixed = $('#timeline_fixed');
		this.timeline = $('#timeline_wrapin');
		this.timeline_width = 100; // Pourcentage

		// Liste y consignes

		const liste_y_consignes = data.liste_y_consignes.split(",");
		this.liste_y_consignes = [];
		for (let i = 0; i < liste_y_consignes.length; i++) {
			this.liste_y_consignes.push(parseFloat(liste_y_consignes[i]));
		}

		// Liste y articles de blog
		const liste_y_blogs = data.liste_y_blogs.split(",");
		this.liste_y_blogs = [];
		for (let i = 0; i < liste_y_blogs.length; i++) {
			this.liste_y_blogs.push(parseFloat(liste_y_blogs[i]));
		}

		// Liste y articles d'événement
		const liste_y_evenements = data.liste_y_evenements.split(",");
		this.liste_y_evenements = [];
		for (let i = 0; i < liste_y_evenements.length; i++) {
			this.liste_y_evenements.push(parseFloat(liste_y_evenements[i]));
		}

		if (data.image_fond && !data.image_fond.trim().includes('..') && !/^https?:\/\//i.test(data.image_fond.trim())) {
			this.timeline_parent.css({ 'background-image': 'url("' + encodeURI(data.image_fond.trim()) + '")' });
		}

		// Urls
		this.url_popup_consigne = data.url_popup_consigne;
		this.url_popup_reponse = data.url_popup_reponse;
		this.url_popup_reponseajout = data.url_popup_reponseajout;
		this.url_popup_blog = data.url_popup_blog;
		this.url_popup_livrables = data.url_popup_livrables;
		this.url_popup_evenement = data.url_popup_evenement;
		this.url_popup_ressources = data.url_popup_ressources;
		this.url_popup_agora = data.url_popup_agora;
		this.url_popup_classes = data.url_popup_classes;
		this.url_popup_chat = data.url_popup_chat;
		this.url_popup_chat2 = data.url_popup_chat2;
	}

	/**
	 * Définit les variables de temps du projet à partir des dates de début et de fin.
	 *
	 * @param {string} date_debut - Date de début du projet (format `parseDate`)
	 * @param {string} date_fin - Date de fin du projet (format `parseDate`)
	 */
	this.initTimeVariables = function (date_debut, date_fin) {
		this.date_debut = parseDate(date_debut);
		this.date_fin = parseDate(date_fin);
		this.nombre_mois = Math.round((this.date_fin - this.date_debut) / (24 * 60 * 60 * 30.5 * 1000));
		this.nombre_jours = Math.round((this.date_fin - this.date_debut) / (24 * 60 * 60 * 1000));
		this.nombre_jours_total = this.nombre_jours + 40;
		this.nombre_jours_vus = this.nombre_jours_total;
		this.nombre_jours_vus_dest = this.nombre_jours_vus;
		this.premier_mois = this.date_debut.getMonth();
		this.premier_jour = Math.round(this.date_debut / (24 * 60 * 60 * 1000));
		this.mois_rollover = -1;
		this.mois_select = -1;
		this.aujourdhui = Math.round(new Date() - this.date_debut) / (24 * 60 * 60 * 1000);
		this.premiere_annee = this.date_debut.getFullYear();
	}

	/**
	 * Met à jour les variables d'affichage de la timeline
	 * et appelle l'application des nouveaux paramètres.
	 *
	 * @param {number} nombre_jours_vus_dest - Nombre de jours à afficher dans la fenêtre
	 * @param {number} x_dest - Position horizontale de départ (en jours depuis le début)
	 * @param {number} y_dest - Position verticale de départ
	 * @see Projet#setTimelineZoom
	 */
	this.showRangeOfTimeline = function (nombre_jours_vus_dest, x_dest, y_dest) {

		this.nombre_jours_vus_dest = nombre_jours_vus_dest;
		this.x_dest = x_dest;
		this.y_dest = y_dest;

		$('body').removeClass('highlightReponse');

		this.setTimelineZoom();
	}
	/**
	 * Affiche la totalité de l'année.
	 *
	 * @see Projet#showRangeOfTimeline
	 * @see Projet#setTimelineZoom
	 *
	 * @todo Gérer l'affichage/le masquage des événements et des blogs
	 */
	this.showWholeTimeline = function () {
		canShowConsigneSidebar = false;

		setContentFromState(
			{
				'data': {
					'type_objet': '0',
					'id_objet': '0'
				}
			}, 'CCN', './'
		);
		$('#menu-consignes .filter a, #menu-classes .filter a').removeClass('selected');
		$('#menu-consignes .logo_menu-tout, #menu-classes .logo_menu-tout').addClass('selected');

		this.showRangeOfTimeline(CCN.projet.nombre_jours_total, 0, 0);

		closeSidebar();
		$('body').removeClass('highlightReponse');

		this.mois_select = -1;
		this.mois_rollover = -1;

		for (let i = 0; i < CCN.consignes.length; i++) {
			CCN.consignes[i].showConsignePastille();
			CCN.consignes[i].select = false;
		}

		$('.consigne_haute').removeClass('hide');
		$('.reponse_haute').addClass('hide');

		// affiche tous les articles de blog
		for (let i = 0; i < CCN.articlesBlog.length; i++) {
			$(CCN.articlesBlog[i].div_base).fadeIn(3000);
		}
		// affiche tous les articles d'événement
		for (let i = 0; i < CCN.articlesEvenement.length; i++) {
			$(CCN.articlesEvenement[i].div_base).fadeIn(3000);
		}

		this.setTimelineZoom();

		$('.connecteur_timeline').addClass('hide');
	}
	/**
	 * Applique les paramètres de zoom de la timeline.
	 *
	 * @see Projet#showRangeOfTimeline
	 */

	this.setTimelineZoom = function () {
		this.timeline_width = (100 / (this.nombre_jours_vus_dest * 100 / this.nombre_jours_total) * 100);
		this.timeline.css(
			{
				'width': this.timeline_width + '%',
				'left': (-(this.x_dest * 100 / this.nombre_jours_total) * this.timeline_width / 100) + '%'
			}
		);

	}

	/**
	 * Initialise les mois de la timeline.
	 */

	this.initTimelineMonths = function () {

		let mois = this.premier_mois;
		let annee = this.premiere_annee;

		for (let i = 0; i < this.nombre_mois; i++) {

			const texte = CCN.nomCompletMois[mois] + " ";
			const jours_mois = new Date(annee, mois + 1, 0).getDate();
			const largeur = jours_mois / this.nombre_jours_total * 100;

			const mois_DOM = $(
				'<div/>', {
				'class': 'mois'
			}
			).append('<div class="mois_label" aria-hidden="true">' + texte + '</div>').css({ 'width': largeur + '%' });

			mois_DOM.appendTo(this.timeline_background);

			mois++;
			if (mois >= 12) {
				mois = 0;
				annee++;
			}
		}
	}
}
