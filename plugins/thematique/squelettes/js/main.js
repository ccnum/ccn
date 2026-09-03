/**
 * Première fonction initialisant le document
 * et les variables globales, puis appelant
 * le chargement du projet
 *
 * @see loadDemarrage
 */

function initCCN() {
	CCN.classes = [];
	CCN.intervenants = [];
	CCN.consignes = [];
	CCN.reponses = [];
	CCN.articlesBlog = [];
	CCN.articlesEvenement = [];
	CCN.articlesBlogLoaded = false;
	CCN.articlesEvenementLoaded = false;

	CCN.idRubriqueRessources = null;

	CCN.couleurBlog = '';
	CCN.dureeTransition = 800;

	CCN.timelineLayerConsignes = $('#timeline_layer_consignes');
	CCN.timelineLayerBlogs = $('#timeline_layer_blogs');
	CCN.timelineLayerEvenements = $('#timeline_layer_evenements');
	CCN.timelineLayerLivrables = $('#livrables');

	loadDemarrage(CCN.urlJson + "demarrage");
}
/**
 *  Charge en un seul appel le JSON du projet, des classes et des consignes
 *  (fond "demarrage", qui combine les 3 côté squelette), puis initialise
 *  la timeline.
 *
 * @param {string} fichier - URL du fichier
 */

async function loadDemarrage(fichier) {
	const data = await fetch(fichier).then(r => r.json());

	initProjet(data.projet);
	initClasses(data.classes);
	initConsignes(data.consignes);

	// Seules les missions (classes + consignes) sont chargées au démarrage.
	// Agenda (blogs) et blog pédagogique (evenements) sont chargés à la demande,
	// au clic sur le menu-timeline (voir ensureArticlesLoaded).
	initTimeline();
}
/**
 *  Initialise CCN.projet à partir des données JSON du projet.
 *
 * @param {Object} dataForProjet
 */

function initProjet(dataForProjet) {
	dataForProjet.largeur = getLargeurZone();
	dataForProjet.hauteur = getHauteurZone();

	CCN.projet = new Projet();
	CCN.projet.init(dataForProjet);

	CCN.couleurBlog = dataForProjet.couleur_blog;
	CCN.idRubriqueRessources = dataForProjet.id_rubrique_ressources;

	const [idArticleCapSurAnnee, statutCapSurAnnee] = (dataForProjet.article_cap_sur_annee || '0|').split('|');
	CCN.idArticleCapSurAnnee = parseInt(idArticleCapSurAnnee) || 0;
	CCN.statutCapSurAnnee = statutCapSurAnnee || '';

	const [idArticleLaRencontre, statutLaRencontre] = (dataForProjet.article_la_rencontre || '0|').split('|');
	CCN.idArticleLaRencontre = parseInt(idArticleLaRencontre) || 0;
	CCN.statutLaRencontre = statutLaRencontre || '';
}
/**
 *  Charge à la demande le flux d'articles (blogs ou événements)
 *  correspondant au mode de timeline demandé, une seule fois.
 *
 * @param {string} type - "blogs" ou "evenements"
 * @returns {Promise<void>}
 */

async function ensureArticlesLoaded(type) {
	if (type === 'blogs' && !CCN.articlesBlogLoaded) {
		CCN.articlesBlogLoaded = true;
		$('body').addClass('loading');
		await loadArticles(CCN.urlJson + "articles&type=blogs", 'blogs', CCN.articlesBlog, CCN.projet.liste_y_blogs);
		$('body').removeClass('loading');
	}

	if (type === 'evenements' && !CCN.articlesEvenementLoaded) {
		CCN.articlesEvenementLoaded = true;
		$('body').addClass('loading');
		await loadArticles(CCN.urlJson + "articles&type=evenements", 'evenements', CCN.articlesEvenement, CCN.projet.liste_y_evenements);
		$('body').removeClass('loading');
	}
}
/**
 *  Retire du DOM le contenu d'un layer de la timeline (consignes+réponses,
 *  blogs ou évènements), sans perdre les données déjà chargées : un simple
 *  detach, pas de suppression.
 *
 * @param {string} type - "consignes", "blogs" ou "evenements"
 */

function detachTimelineLayer(type) {
	if (type === 'consignes') {
		CCN.consignes.forEach(consigne => {
			consigne.div_base.detach();
			consigne.reponses.forEach(reponse => {
				reponse.div_base.detach();
				reponse.connecteur.detach();
			});
		});
		return;
	}
	const ccnArray = type === 'blogs' ? CCN.articlesBlog : CCN.articlesEvenement;
	ccnArray.forEach(article => article.div_base.detach());
}
/**
 *  Réinsère dans le DOM le contenu déjà chargé et instancié
 *  d'un layer de la timeline (consignes+réponses, blogs ou évènements).
 *
 * @param {string} type - "consignes", "blogs" ou "evenements"
 */

function attachTimelineLayer(type) {
	if (type === 'consignes') {
		CCN.consignes.forEach(consigne => {
			CCN.timelineLayerConsignes.append(consigne.div_base);
			consigne.reponses.forEach(reponse => {
				CCN.timelineLayerConsignes.append(reponse.div_base);
				CCN.projet.timeline_fixed.append(reponse.connecteur);
			});
		});
		return;
	}
	const ccnArray = type === 'blogs' ? CCN.articlesBlog : CCN.articlesEvenement;
	const layer = type === 'blogs' ? CCN.timelineLayerBlogs : CCN.timelineLayerEvenements;
	ccnArray.forEach(article => layer.append(article.div_base));
}
/**
 *  Initialise CCN.classes/CCN.intervenants/CCN.travailEnCoursId à partir
 *  des données JSON des classes.
 *
 * @param {Object} data
 */

function initClasses(data) {
	data.classes.forEach(dataForClasse => {
		const nouvelleClasse = new Classe();
		nouvelleClasse.init(dataForClasse);
		CCN.classes.push(nouvelleClasse);
	});

	data.intervenants.forEach(dataForIntervenant => {
		const nouvelIntervenant = new Intervenant();
		nouvelIntervenant.init(dataForIntervenant);
		CCN.intervenants.push(nouvelIntervenant);
	});

	CCN.travailEnCoursId = data.travail_en_cours_id;
}
/**
 *  Initialise CCN.consignes (et leurs réponses) à partir des données JSON
 *  des consignes.
 *
 * @param {Object} data
 */

function initConsignes(data) {
	const jsonConsignes = data.consignes;
	let indexY = 0;

	for (let i = 0; i < jsonConsignes.length; ++i) {

		const jsonConsigne = jsonConsignes[i];
		const dataForConsigne = {};

		dataForConsigne.id = jsonConsigne.id;
		dataForConsigne.intervenant_id = jsonConsigne.intervenant_id;
		dataForConsigne.titre = jsonConsigne.titre;
		dataForConsigne.image = jsonConsigne.image;
		dataForConsigne.image_generique = jsonConsigne.image_generique;
		dataForConsigne.y = jsonConsigne.y;
		dataForConsigne.isLastConsigne = (i==jsonConsignes.length-1)

		if (indexY >= CCN.projet.liste_y_consignes.length) {
			indexY = 0;
		}

		if ((dataForConsigne.y <= 0) || (dataForConsigne.y >= 1.05)) {
			dataForConsigne.y = CCN.projet.liste_y_consignes[indexY];
		}

		indexY++;

		dataForConsigne.date_texte = jsonConsigne.date;
		dataForConsigne.date = parseDate(dataForConsigne.date_texte);
		dataForConsigne.jour_consigne = parseFloat(Math.round((dataForConsigne.date) / (24 * 60 * 60 * 1000)));
		dataForConsigne.nombre_jours = dataForConsigne.jour_consigne - CCN.projet.premier_jour;

		while (dataForConsigne.nombre_jours < 0) {
			dataForConsigne.nombre_jours += 365
		}

		const jsonReponses = jsonConsigne.reponses;

		dataForConsigne.nombre_reponses = jsonReponses.length;
		dataForConsigne.reponses = [];

		const liste_jours_max = [];
		dataForConsigne.nombre_commentaires = 0;

		for (let j = 0; j < jsonReponses.length; j++) {
			const date_jours_max = parseDate(jsonReponses[j].date);

			const jours = parseFloat(Math.round((date_jours_max) / (24 * 60 * 60 * 1000))) - dataForConsigne.jour_consigne;
			liste_jours_max.push(jours);

			dataForConsigne.nombre_commentaires += jsonReponses[j].commentaires;

			dataForConsigne.reponses.push(jsonReponses[j].classe_id);
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
		dataForConsigne.intervenant_nom = jsonConsigne.intervenant_nom;
		dataForConsigne.numero = jsonConsigne.rang - 1;

		const nouvelleConsigne = new Consigne();
		nouvelleConsigne.init(dataForConsigne);

		let has_current_classe_already_answer = false;

		for (let j = 0; j < jsonReponses.length; j++) {
			const jsonReponse = jsonReponses[j];
			const dataForReponse = {};

			dataForReponse.id = jsonReponse.id;
			dataForReponse.classe_id = jsonReponse.classe_id;
			dataForReponse.titre = jsonReponse.texte;
			dataForReponse.date = jsonReponse.date;
			dataForReponse.date_date = parseDate(dataForReponse.date);

			const jour_reponse = Math.round(dataForReponse.date_date / (24 * 60 * 60 * 1000));
			dataForReponse.x_affichage = get_abscisse_affiche_reponse(dataForConsigne.nombre_jours, dataForConsigne.jour_consigne, jour_reponse)

			dataForReponse.nombre_commentaires = jsonReponse.commentaires;

			dataForReponse.y = jsonReponse.y;
			dataForReponse.y_genere = false;

			if ((dataForReponse.y === 0) || (dataForReponse.y > 0.8) || (dataForReponse.y < -0.2)) {
				// Position de repli figée sur l'ordre des réponses (trié par
				// classe, cf {par id_rubrique} dans json/consignes.html) plutôt
				// qu'aléatoire, pour ne pas mélanger l'ordre à chaque
				// rechargement. Réparties uniformément sur [0,1] ici ; la
				// conversion en marge pixel réelle (pour ne déborder ni sous le
				// header ni sur le footer) se fait dans Reponse.initDOM, une
				// fois la vraie hauteur de la carte connue.
				dataForReponse.y = jsonReponses.length > 1 ? j / (jsonReponses.length - 1) : 0.5;
				dataForReponse.y_genere = true;
			}

			dataForReponse.consigne = nouvelleConsigne;
			dataForReponse.classes = CCN.classes;

			const nouvelleReponse = new Reponse();
			nouvelleReponse.init(dataForReponse);
			nouvelleConsigne.reponses.push(nouvelleReponse);

			if (CCN.classeSelection > 0 && CCN.classeSelection == dataForReponse.classe_id) {
				has_current_classe_already_answer = true;
			}
		}

		if (!has_current_classe_already_answer) {
			nouvelleConsigne.showNewReponseButtonInTimeline();
		}

		CCN.consignes.push(nouvelleConsigne);
	}
}

/**
 *  Charge le JSON des articles (blogs ou événements) et les instancie.
 *
 * @param {string} fichier  - URL du fichier JSON
 * @param {string} type     - "blogs" ou "evenements"
 * @param {Array}  ccnArray - Tableau de destination (CCN.articlesBlog ou CCN.articlesEvenement)
 * @param {Array}  listeY   - Séquence de positions Y (CCN.projet.liste_y_blogs ou liste_y_evenements)
 */

async function loadArticles(fichier, type, ccnArray, listeY) {
	const jsonData = await fetch(fichier).then(r => r.json());
	const jsonArticles = jsonData.articles;
	let indexY = 0;

	for (let i = 0; i < jsonArticles.length; i++) {
		const data = { ...jsonArticles[i] };

		if (indexY >= listeY.length) indexY = 0;
		if (data.y == 0) data.y = listeY[indexY];
		indexY++;

		const date = parseDate(data.date);
		data.nombre_jours = parseFloat(Math.round(date / (24 * 60 * 60 * 1000))) - CCN.projet.premier_jour;
		data.nombre_commentaires = data.commentaires;

		const article = new Article();
		article.init(data, type);
		ccnArray.push(article);
	}
}
/**
 *  Affiche/masque un badge jalon ("Cap sur l'année" / "La Rencontre") selon
 *  qu'un article existe. Une fois publié, visible pour tout le monde. Tant
 *  qu'il n'est pas publié, réservé aux admins/intervenants (cf CCN.role,
 *  posé dans sommaire.html) avec un signalement visuel (svg d'étiquette
 *  grise + pastille) ; masqué pour les autres rôles (prof/élève).
 *
 * @param {string} prefixe - "cap_sur_annee" ou "la_rencontre"
 * @param {number} idArticle
 * @param {string} statut
 */

function updateBadgeJalon(prefixe, idArticle, statut) {
	const $badge = $('#badge_' + prefixe);
	if (!idArticle) {
		$badge.hide();
		return;
	}

	const nonPublie = statut !== 'publie';
	const peutVoirNonPublie = CCN.role === 'admin' || CCN.role === 'intervenant';
	if (nonPublie && !peutVoirNonPublie) {
		$badge.hide();
		return;
	}

	// L'image de fond du badge (étiquette + pastille "Article non publié"
	// éventuelle) change selon le statut
	$badge.toggleClass('est-publie', !nonPublie);
	$badge.show();
}

/**
 *  Initialise la vue, la timeline,
 *  définit les événements attribués aux éléments de la timeline.
 */

function initTimeline() {

	window.onpopstate = onHashChange;

	CCN.projet.initTimelineMonths();
	CCN.projet.showWholeTimeline();
	changeTimelineMode('consignes');

	updateBadgeJalon('cap_sur_annee', CCN.idArticleCapSurAnnee, CCN.statutCapSurAnnee);
	updateBadgeJalon('la_rencontre', CCN.idArticleLaRencontre, CCN.statutLaRencontre);

	updateAllConnecteurs();

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
			const largeur_mois = CCN.projet.nombre_jours / CCN.projet.nombre_mois;
			if (mois < CCN.projet.nombre_mois / 2) {
				CCN.projet.showRangeOfTimeline(90, mois * largeur_mois, 0);
			} else {
				CCN.projet.showRangeOfTimeline(90, (mois + 1) * largeur_mois, 0);
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

	window.addEventListener("resize", () => updateAllConnecteurs());
}

$(function () {
	initCCN();
	initMissionTabs();
});
