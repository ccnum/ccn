let indexConsigne;
let indexReponse;
let indexArticleBlog;
let indexArticleEvenement;
/**
 * Première fonction initialisant le document
 * et les variables globales, puis appelant
 * le chargement du projet
 *
 * @see loadProjet
 */

function initCCN() {
	indexConsigne = 0;
	indexReponse = 0;
	indexArticleBlog = 0;
	indexArticleEvenement = 0;

	CCN.classes = [];
	CCN.intervenants = [];
	CCN.consignes = [];
	CCN.reponses = [];
	CCN.articlesBlog = [];
	CCN.articlesEvenement = [];

	CCN.idRubriqueRessources = null;
	CCN.idRubriqueAgora = null;

	CCN.couleurBlog = '';
	CCN.dureeTransition = 800;

	CCN.timelineLayerConsignes = $('#timeline_layer_consignes');
	CCN.timelineLayerBlogs = $('#timeline_layer_blogs');
	CCN.timelineLayerEvenements = $('#timeline_layer_evenements');
	CCN.timelineLayerLivrables = $('#livrables');

	loadProjet(CCN.urlXml + "projet");
}
/**
 *  Charge le XML du projet,
 *  initialise le projet
 *  puis appelle le chargement des classes.
 *
 * @param {string} fichier - URL du fichier
 */

function loadProjet(fichier) {
	$.ajax(
		{
			url: fichier,
			dataType: 'text',
			success: function (xml) {
				xml = $.parseXML(xml.trim());

				const dataForProjet = {};

				dataForProjet.date_debut = getXMLNodeValue('date_debut', xml);
				dataForProjet.date_fin = getXMLNodeValue('date_fin', xml);
				dataForProjet.couleur_fond = getXMLNodeValue('couleur_fond', xml);
				dataForProjet.couleur_base_texte = getXMLNodeValue('couleur_base_texte', xml);
				dataForProjet.couleur_1erplan1 = getXMLNodeValue('couleur_1erplan1', xml);
				dataForProjet.couleur_1erplan2 = getXMLNodeValue('couleur_1erplan2', xml);
				dataForProjet.couleur_1erplan3 = getXMLNodeValue('couleur_1erplan3', xml);

				dataForProjet.largeur = getLargeurZone();
				dataForProjet.hauteur = getHauteurZone();

				dataForProjet.fps = parseFloat(getXMLNodeValue('fps', xml));
				dataForProjet.zoom_consignes = getXMLNodeValue('zoom_consignes', xml);
				dataForProjet.liste_y_consignes = getXMLNodeValue('seq_posy_consignes', xml);
				dataForProjet.liste_y_blogs = getXMLNodeValue('seq_posy_blogs', xml);
				dataForProjet.liste_y_evenements = getXMLNodeValue('seq_posy_evenements', xml);

				dataForProjet.url_popup_consigne = getXMLNodeValue('url_popup_consigne', xml);
				dataForProjet.url_popup_reponse = getXMLNodeValue('url_popup_reponse', xml);
				dataForProjet.url_popup_reponseajout = getXMLNodeValue('url_popup_reponseajout', xml);
				dataForProjet.url_popup_blog = getXMLNodeValue('url_popup_blog', xml);
				dataForProjet.url_popup_livrables = getXMLNodeValue('url_popup_livrables', xml);
				dataForProjet.url_popup_evenement = getXMLNodeValue('url_popup_evenement', xml);
				dataForProjet.url_popup_ressources = getXMLNodeValue('url_popup_ressources', xml);
				dataForProjet.url_popup_agora = getXMLNodeValue('url_popup_agora', xml);
				dataForProjet.url_popup_classes = getXMLNodeValue('url_popup_classes', xml);
				dataForProjet.url_popup_chat = getXMLNodeValue('url_popup_chat', xml);
				dataForProjet.url_popup_chat2 = getXMLNodeValue('url_popup_chat2', xml);

				dataForProjet.image_fond = (hasXMLNodeValue('image_fond', xml)) ? getXMLNodeValue('image_fond', xml) : '';

				CCN.projet = new Projet();
				CCN.projet.init(dataForProjet);

				CCN.couleurBlog = getXMLNodeValue('couleur_blog', xml);
				CCN.idRubriqueRessources = getXMLNodeValue('id_rubrique_ressources', xml);
				CCN.idRubriqueAgora = getXMLNodeValue('id_rubrique_agora', xml);

				$.when(
					loadClasses(CCN.urlXml + "classes"),
					loadBlog(CCN.urlXml + "articles_blog"),
					loadEvenements(CCN.urlXml + "articles_evenement")
				).done(initTimeline);
			}
		}
	);
}
/**
 *  Charge le XML des classes (liste)
 *  puis appelle le chargement des consignes.
 *
 * @param {string} fichier - URL du fichier
 */

function loadClasses(fichier) {
	return $.ajax({url: fichier, dataType: 'text'}).then(function (xml) {
		xml = $.parseXML(xml.trim());

		const xmlClasses = xml.getElementsByTagName("classe");
		for (let i = 0; i < xmlClasses.length; ++i) {
			const dataForClasse = {};
			dataForClasse.id = parseFloat(getXMLNodeValue('id', xmlClasses[i]));
			dataForClasse.nom = getXMLNodeValue('nom', xmlClasses[i]);
			const nouvelleClasse = new Classe();
			nouvelleClasse.init(dataForClasse);
			CCN.classes.push(nouvelleClasse);
		}

		const xmlIntervenants = xml.getElementsByTagName("intervenant");
		for (let i = 0; i < xmlIntervenants.length; ++i) {
			const dataForIntervenant = {};
			dataForIntervenant.id = parseFloat(getXMLNodeValue('id', xmlIntervenants[i]));
			dataForIntervenant.nom = getXMLNodeValue('nom', xmlIntervenants[i]);
			const nouvelIntervenant = new Intervenant();
			nouvelIntervenant.init(dataForIntervenant);
			CCN.intervenants.push(nouvelIntervenant);
		}

		CCN.travailEnCoursId = parseFloat(getXMLNodeValue('travail_en_cours_id', xml));
		return loadConsignes(CCN.urlXml + "consignes");
	});
}
/**
 *  Charge le XML des consignes et des réponses
 *  puis appelle le chargement des articles du blog
 *
 * @param {string} fichier - URL du fichier
 */

function loadConsignes(fichier) {
	return $.ajax({url: fichier, dataType: 'text'}).then(function (xml) {
		xml = $.parseXML(xml.trim());

		const xmlConsignes = xml.getElementsByTagName("consigne");
				let indexY = 0;

				for (let i = 0; i < xmlConsignes.length; ++i) {

					const dataForConsigne = {};

					dataForConsigne.id = parseFloat(getXMLNodeValue('id', xmlConsignes[i]));
					dataForConsigne.intervenant_id = parseFloat(getXMLNodeValue('intervenant_id', xmlConsignes[i]));
					dataForConsigne.titre = getXMLNodeValue('titre', xmlConsignes[i]);
					dataForConsigne.image = getXMLNodeValue('image', xmlConsignes[i]);
					dataForConsigne.y = getXMLNodeValue('y', xmlConsignes[i]);
					dataForConsigne.isLivrable = (getXMLNodeValue('livrable', xmlConsignes[i]) == "oui");
					dataForConsigne.isLastConsigne = (i==xmlConsignes.length-1)

					if (indexY >= CCN.projet.liste_y_consignes.length) {
						indexY = 0;
					}

					if ((dataForConsigne.y <= 0) || (dataForConsigne.y >= 1.05)) {
						dataForConsigne.y = CCN.projet.liste_y_consignes[indexY];
					}

					indexY++;

					dataForConsigne.date_texte = getXMLNodeValue('date', xmlConsignes[i]);
					dataForConsigne.date = parseDate(dataForConsigne.date_texte);
					dataForConsigne.jour_consigne = parseFloat(Math.round((dataForConsigne.date) / (24 * 60 * 60 * 1000)));
					dataForConsigne.nombre_jours = dataForConsigne.jour_consigne - CCN.projet.premier_jour;

					while (dataForConsigne.nombre_jours < 0) {
						dataForConsigne.nombre_jours += 365
					}

					const xmlReponses = xmlConsignes[i].getElementsByTagName("reponse");

					dataForConsigne.nombre_reponses = (xmlReponses) ? xmlReponses.length : 0;
					dataForConsigne.reponses = [];

					const liste_jours_max = [];
					dataForConsigne.nombre_commentaires = 0;

					for (let j = 0; j < xmlReponses.length; j++) {
						const date_texte_reponse = getXMLNodeValue('date', xmlReponses[j]);
						const date_jours_max = parseDate(date_texte_reponse);

						const jours = parseFloat(Math.round((date_jours_max) / (24 * 60 * 60 * 1000))) - dataForConsigne.jour_consigne;
						liste_jours_max.push(jours);

						const nombre_commentaires_reponse = parseFloat(getXMLNodeValue('commentaires', xmlReponses[j]));
						dataForConsigne.nombre_commentaires += nombre_commentaires_reponse;

						dataForConsigne.reponses.push(getXMLNodeValue('classe_id', xmlReponses[j]));
					}

					dataForConsigne.nombre_jours_max = 0;

					for (let j = 0; j < liste_jours_max.length; j++) {
						if (dataForConsigne.nombre_jours_max < liste_jours_max[j]) {
							dataForConsigne.nombre_jours_max = liste_jours_max[j];
						}
					}
					dataForConsigne.nombre_jours_max += dataForConsigne.nombre_jours_max / 5;
					if (dataForConsigne.nombre_jours_max <= 30) {
						dataForConsigne.nombre_jours_max = 30;
					}

					dataForConsigne.classes = CCN.classes;
					dataForConsigne.intervenant_nom = getXMLNodeValue('intervenant_nom', xmlConsignes[i]);
					dataForConsigne.numero = parseInt(getXMLNodeValue('rang', xmlConsignes[i])) - 1;

					const nouvelleConsigne = new Consigne();
					nouvelleConsigne.init(dataForConsigne);

					// Calcul du positionnement y intelligent des réponses (TO DO)
					const liste_y = [];

					for (let j = 0; j < xmlReponses.length; j++) {
						if (j == 0) {
							const rd = Math.floor(Math.random() * xmlReponses.length);
							liste_y.push(rd);
						} else {
							for (let k = 0; k < 15; k++) {
								let meme = 0;
								const rd = Math.floor(Math.random() * xmlReponses.length);
								for (let l = 0; l < j; l++) {
									if (rd == liste_y[l]) {
										meme++;
									}
								}
								if (meme == 0) {
									liste_y.push(rd);
									break;
								}
							}
						}
					}

					if (liste_y.length < xmlReponses.length) {
						liste_y.push(liste_y[0]);
					}

					let indexReponseInConsigne = 0;
					let has_current_classe_already_answer = false;

					for (let j = 0; j < xmlReponses.length; j++) {
						const dataForReponse = {};

						dataForReponse.id = parseFloat(getXMLNodeValue('id', xmlReponses[j]));
						dataForReponse.classe_id = parseFloat(getXMLNodeValue('classe_id', xmlReponses[j]));
						dataForReponse.titre = getXMLNodeValue('texte', xmlReponses[j]);
						dataForReponse.date = getXMLNodeValue('date', xmlReponses[j]);
						dataForReponse.date_date = parseDate(dataForReponse.date);

						dataForReponse.nombre_jours = parseFloat(Math.round((dataForReponse.date_date) / (24 * 60 * 60 * 1000))) - dataForConsigne.jour_consigne;
						dataForReponse.nombre_commentaires = parseFloat(getXMLNodeValue('commentaires', xmlReponses[j]));

						dataForReponse.vignette = (hasXMLNodeValue('vignette', xmlReponses[j])) ? getXMLNodeValue('vignette', xmlReponses[j]) : '';

						dataForReponse.y = parseFloat(getXMLNodeValue('y', xmlReponses[j]));

						if ((dataForReponse.y === 0) || (dataForReponse.y > 0.8) || (dataForReponse.y < -0.2)) {
							dataForReponse.y = (liste_y[j]) / (xmlReponses.length);
						}

						dataForReponse.consigne = nouvelleConsigne;
						dataForReponse.classes = CCN.classes;
						dataForReponse.numero = indexReponse;
						dataForReponse.index = indexReponseInConsigne;

						const nouvelleReponse = new Reponse();
						nouvelleReponse.init(dataForReponse);
						nouvelleConsigne.reponses.push(nouvelleReponse);

						if (CCN.classeSelection > 0 && CCN.classeSelection == dataForReponse.classe_id) {
							has_current_classe_already_answer = true;
						}

						indexReponseInConsigne++;
						indexReponse++;
					}

					if (!has_current_classe_already_answer) {
						nouvelleConsigne.showNewReponseButtonInTimeline();
					}

					CCN.consignes.push(nouvelleConsigne);
					indexConsigne++;
				}
	});
}

/**
 *  Charge le XML des articles du blog
 *
 * @param {string} fichier - URL du fichier
 */

function loadBlog(fichier) {
	return $.ajax({url: fichier, dataType: 'text'}).then(function (xml) {
		xml = $.parseXML(xml.trim());

		const xmlArticlesBlog = xml.getElementsByTagName("article");
		let indexY = 0;

		for (let i = 0; i < xmlArticlesBlog.length; i++) {

			const dataForArticleBlog = {};

			dataForArticleBlog.id = getXMLNodeValue('id', xmlArticlesBlog[i]);
			dataForArticleBlog.type_objet = getXMLNodeValue('type_objet', xmlArticlesBlog[i]);
			dataForArticleBlog.id_objet = getXMLNodeValue('id_objet', xmlArticlesBlog[i]);
			dataForArticleBlog.titre = getXMLNodeValue('titre', xmlArticlesBlog[i]);
			dataForArticleBlog.y = getXMLNodeValue('y', xmlArticlesBlog[i]);
			dataForArticleBlog.date = getXMLNodeValue('date', xmlArticlesBlog[i]);

			if (indexY >= CCN.projet.liste_y_blogs.length) {
				indexY = 0;
			}

			if (dataForArticleBlog.y == 0) {
				dataForArticleBlog.y = CCN.projet.liste_y_blogs[indexY];
			}

			indexY++;

			const date = parseDate(dataForArticleBlog.date);
			const jour_article = parseFloat(Math.round((date) / (24 * 60 * 60 * 1000)));
			dataForArticleBlog.nombre_jours = jour_article - CCN.projet.premier_jour;
			dataForArticleBlog.nombre_commentaires = parseFloat(getXMLNodeValue('commentaires', xmlArticlesBlog[i]));

			dataForArticleBlog.numero = indexArticleBlog;
			dataForArticleBlog.index = indexArticleBlog;

			const nouvelArticleBlog = new ArticleBlog();
			nouvelArticleBlog.init(dataForArticleBlog);

			CCN.articlesBlog.push(nouvelArticleBlog);

			indexArticleBlog++;
		}
	});
}
/**
 *  Charge le XML des événements
 *  puis appelle initTimeline
 *
 * @param {string} fichier - URL du fichier
 */

function loadEvenements(fichier) {
	return $.ajax({url: fichier, dataType: 'text'}).then(function (xml) {
		xml = $.parseXML(xml.trim());

		const xmlArticlesEvenement = xml.getElementsByTagName("article");
		let indexY = 0;

		for (let i = 0; i < xmlArticlesEvenement.length; i++) {

			const dataForEvenement = {};

			dataForEvenement.id = getXMLNodeValue('id', xmlArticlesEvenement[i]);
			dataForEvenement.type_objet = getXMLNodeValue('type_objet', xmlArticlesEvenement[i]);
			dataForEvenement.id_objet = getXMLNodeValue('id_objet', xmlArticlesEvenement[i]);
			dataForEvenement.titre = getXMLNodeValue('titre', xmlArticlesEvenement[i]);
			dataForEvenement.y = getXMLNodeValue('y', xmlArticlesEvenement[i]);

			if (indexY >= CCN.projet.liste_y_evenements.length) {
				indexY = 0;
			}
			if (dataForEvenement.y == 0) {
				dataForEvenement.y = CCN.projet.liste_y_evenements[indexY];
			}

			indexY++;

			dataForEvenement.date = getXMLNodeValue('date', xmlArticlesEvenement[i]);
			const date = parseDate(dataForEvenement.date);
			const jour_article = parseFloat(Math.round((date) / (24 * 60 * 60 * 1000)));
			dataForEvenement.nombre_jours = jour_article - CCN.projet.premier_jour;
			dataForEvenement.nombre_commentaires = parseFloat(getXMLNodeValue('commentaires', xmlArticlesEvenement[i]));

			dataForEvenement.numero = indexArticleEvenement;
			dataForEvenement.index = indexArticleEvenement;

			const nouvelArticleEvenement = new ArticleEvenement();
			nouvelArticleEvenement.init(dataForEvenement);

			CCN.articlesEvenement.push(nouvelArticleEvenement);

			indexArticleEvenement++;
		}
	});
}
/**
 *  Initialise la vue, la timeline,
 *  définit les événements attribués aux éléments de la timeline.
 */

function initTimeline() {

	window.onpopstate = onHashChange;

	CCN.projet.initTimelineMonths();
	CCN.projet.showWholeTimeline();

	updateConnecteurs();

	$('.reponse_haute')
		.on(
			'mouseover', function () {
				$('body').addClass('hoveringReponse');
				$(this).addClass('hover');
				$('#connecteur_consigne_' + $(this).data('consigne-id') + '_reponse_' + $(this).data('reponse-id')).addClass('hover');
			}
		)
		.on(
			'mouseleave', function () {
				$('body').removeClass('hoveringReponse');
				$(this).removeClass('hover');
				$('.connecteur_timeline').removeClass('hover');
			}
		);

	$('.mois, .timeline_trigger').on(
		'click', function () {
			changeTimelineMode('consignes');
			CCN.projet.showWholeTimeline();
		}
	);

	// Zoom sur la date au chargement de la page

	if (CCN.dateToShowAtInit != "0") {
		const jd = parseFloat(CCN.dateToShowAtInit.substring(0, 2));
		const md = parseFloat(CCN.dateToShowAtInit.substring(3, 5));
		const yd = parseFloat(CCN.dateToShowAtInit.substring(6, 10));

		const date = new Date();
		date.setDate(jd);
		date.setMonth(md - 1);
		date.setFullYear(yd);

		// On est dans le temps du projet ?

		if (Math.round(date) >= Math.round(CCN.projet.date_debut) && Math.round(date) <= Math.round(CCN.projet.date_fin)) {
			const mois = Math.round((date - CCN.projet.date_debut) / (24 * 60 * 60 * 30.5 * 1000));
			CCN.projet.mois_select = mois;
			if (mois < CCN.projet.nombre_mois / 2) {
				CCN.projet.showRangeOfTimeline(90, (mois * CCN.projet.largeur_mois), 0);
			} else {
				CCN.projet.showRangeOfTimeline(90, ((mois + 1) * CCN.projet.largeur_mois), 0);
			}
		}
	}

	// Ouverture de la popup projet si première fois

	if (CCN.idObjetToShowAtInit == 0) {
		if (document.cookie.indexOf('visited=true') === -1) {
			const expires = new Date();
			expires.setDate(expires.getDate() + 30);
			document.cookie = "visited=true; expires=" + expires.toUTCString() + "; SameSite=Strict; Secure";
		}
	}

	setContentFromState(
		{
			data: {
				'type_objet': CCN.typePopupToShowAtInit,
				'id_objet': CCN.idObjetToShowAtInit,
				'page': CCN.pageToShowAtInit,
				'id_rubrique': CCN.idRubriqueToShowAtInit,
				'id_article': CCN.idArticleToShowAtInit,
				'id_syndic_article': CCN.idSyndicArticleToShowAtInit
			}
		}
	);

	// Silder colorbox d'aide
	$(".ccn-aide").mediabox({ width: '80%', height: 'auto', href: $(this).attr('href'), current: "{current}/{total}" });
	$('.logo_menu-aide').on("click",
		function () {
			$(".ccn-aide").mediabox({ open: true });
		}
	);

	$('.profil').mediabox({ width: '80%', height: '80%' });

	window.addEventListener("resize", () => updateConnecteurs());
}

$(function () {
	initCCN();
});
