let canShowConsigneSidebar = false;

let _sidebarTrigger = null;

const SIDEBAR_FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

function _sidebarFocusableElements() {
	return $('#sidebar').find(SIDEBAR_FOCUSABLE).filter(':visible');
}

$(document).on('keydown.sidebarFocusTrap', function (e) {
	if (!$('body').hasClass('hasSidebarOpen')) {
		return;
	}
	if (e.key === 'Escape') {
		closeSidebar();
		return;
	}
	if (e.key !== 'Tab') {
		return;
	}
	const $focusable = _sidebarFocusableElements();
	if ($focusable.length === 0) {
		return;
	}
	const first = $focusable.first()[0];
	const last = $focusable.last()[0];
	if (e.shiftKey) {
		if (document.activeElement === first) {
			e.preventDefault();
			last.focus();
		}
	} else {
		if (document.activeElement === last) {
			e.preventDefault();
			first.focus();
		}
	}
});

// Vérifie les paramètres dans l'url
function urlParam(name) {
	return new URLSearchParams(window.location.search).get(name) || 0;
}

$(function () {

	$(document).on('click', '.js-call-consigne', function () {
		callConsigne($(this).data('id-objet'));
	});

	$(document).on('click', '.js-call-reponse', function () {
		callReponse($(this).data('id-article'));
	});

	$(document).on('click', '.js-call-livrable', function () {
		callLivrable(null, 'open');
		callLivrable($(this).data('id-article'), 'openDetails');
	});

	$('#timeline_fixed').on(
		'click', function (event) {
			event.stopPropagation();
			CCN.projet.showWholeTimeline();
			changeTimelineMode('consignes');
		}
	);

	$(window).on(
		'resize', function () {
			onResize();
		}
	);

	onResize();

	$(document).on('click', '#sidebarExpand', function () {
		toggleSidebarExpand();
	});

	$(document).on('click', '#sidebar-close', function () {
		if (CCN.projet) {
			CCN.projet.showWholeTimeline();
		} else {
			closeSidebar();
		}
	});

	$(document).on('click', '#sidebarCache', function () {
		$('body').removeClass('hasSidebarExpanded');
	});

	$(".logo").not('#menu-classes-select ul a').tooltip(
		{
			appendTo: "body", // On garde ça pour éviter le bug des images déplacées !
			position: {
				my: "center bottom-4",
				at: "center top",
				using: function (position, feedback) {
					$(this).css(position);
					$("<div>")
						.addClass("arrow")
						.addClass(feedback.vertical)
						.addClass(feedback.horizontal)
						.appendTo(this);
				},
				collision: "fit",
			},
			show: {
				duration: 100,
				effect: 'fadeIn'
			},
			hide: {
				duration: 100,
				effect: 'fadeOut'
			}
		}
	);
});

let antifloodHashChange = false;

function onHashChange() {
	if (antifloodHashChange === false) {
		setContentFromState(History.getState());
	}
}

/**
 * Initialise la vue depuis l'URL donnée
 * ou depuis l'état de l'historique donné
 */
let currentState = {};
function setContentFromState(state) {

	if (typeof state.data !== 'object' || state.data == null) {
		return;
	}
	state = state.data;

	if (state.type_objet == undefined) { state.type_objet = ''; }
	if (state.page == undefined) { state.page = ''; }
	if (state.id_rubrique == undefined) { state.id_rubrique = ''; }
	if (state.id_article == undefined) { state.id_article = ''; }
	if (state.id_syndic_article == undefined) { state.id_syndic_article = ''; }
	if (state.id_objet == undefined) { state.id_objet = ''; }

	if (currentState.type_objet == undefined) { currentState.type_objet = ''; }
	if (currentState.page == undefined) { currentState.page = ''; }
	if (currentState.id_rubrique == undefined) { currentState.id_rubrique = ''; }
	if (currentState.id_article == undefined) { currentState.id_article = ''; }
	if (currentState.id_syndic_article == undefined) { currentState.id_syndic_article = ''; }
	if (currentState.id_objet == undefined) { currentState.id_objet = ''; }

	let isSamePage = true;

	for (const index of Object.keys(state)) {
		if (state[index] != currentState[index]) {
			isSamePage = false;
			break;
		}
	}
	currentState = state;
	if (isSamePage) { return; }

	antifloodHashChange = true;
	// Ressource
	if ((state.type_objet == '0'
		&& state.id_objet == '0')
		|| (state.type_objet == ''
			&& state.id_objet == '')
	) {
		CCN.projet.showWholeTimeline();
	}

	if (state.type_objet == "ressources") {
		callRessource();

		if (state.page == 'rubrique') {
			if (state.id_rubrique != CCN.idRubriqueRessources) {
				callRessourceRubrique(state.id_rubrique, 'ressources');
			}
		}

		if (state.page == 'article') {
			callRessourceArticle(state.id_article, 'ressources');
		}

		if (state.page == 'syndic_article') {
			callRessourceSyndicArticle(state.id_syndic_article, 'ressources');
		}
	}

	// Agora
	if (state.type_objet == "agora") {
		callAgora();

		if (state.page == 'rubrique') {
			if (state.id_rubrique != CCN.idRubriqueAgora) {
				callRessourceRubrique(state.id_rubrique, 'agora');
			}
		}

		if (state.page == 'article') {
			callRessourceArticle(state.id_article, 'agora');
		}

		if (state.page == 'syndic_article') {
			callRessourceSyndicArticle(state.id_syndic_article, 'agora');
		}
	}

	if (state.id_objet != "0") {
		// Consigne
		if (state.type_objet == "consignes") {
			for (let k = 0; k < CCN.consignes.length; k++) {
				if (CCN.consignes[k].id == state.id_objet) {
					callConsigne(state.id_objet);
					break;
				}
			}
		}

		// Réponse
		if (state.type_objet == "travail_en_cours") {
			outer: for (let k = 0; k < CCN.consignes.length; k++) {
				for (let l = 0; l < CCN.consignes[k].reponses.length; l++) {
					if (CCN.consignes[k].reponses[l].id == state.id_objet) {
						callReponse(state.id_objet);
						break outer;
					}
				}
			}
		}

		// Classe
		if (state.type_objet == "classes") {
			for (let k = 0; k < CCN.classes.length; k++) {
				if (CCN.classes[k].id == state.id_objet) {
					callClasse(state.id_objet);
					break;
				}
			}
		}

		// Article de blog
		if (state.type_objet == "blogs") {
			callArticleBlog(state.id_objet, "article");
		}

		// Article d'événement
		if (state.type_objet == "evenements") {
			callArticleEvenement(state.id_objet, "article");
		}
	}
	else { // state.id_objet == 0)

		// Ressource
		if (state.type_objet == "ressources") {

		} else if (state.type_objet == 'travail_en_cours') {
			if (state.page == 'rubrique') {
				callClasses();
			}
		} else {
			changeTimelineMode('consignes');
		}
	}

}

/**
 * Gère la mise à jour des styles lorsque l'écran est resizé
 */
function onResize() {
	$('#crayons-surcharge-styles').text('.crayon-active.markItUpEditor { height: ' + (parseInt($(window).height()) - 228) + 'px !important; } .resizehandle { display:none !important; }');
}

function expandSidebar() {
    if ($('body').hasClass('hasSidebarExpanded')) return; // déjà ouvert
    
    $('body').addClass('hasSidebarExpanded');
}

function collapseSidebar() {
    if (!$('body').hasClass('hasSidebarExpanded')) return; // déjà fermé
    
    $('body').removeClass('hasSidebarExpanded');
}

function toggleSidebarExpand() {
    if ($('body').hasClass('hasSidebarExpanded')) {
        collapseSidebar();
    } else {
        expandSidebar();
    }
}

/**
 * Définit la largeur de la zone.
 */

function getLargeurZone() {
	return $(window).width() * 0.98;
}

/**
 * Définit la hauteur de la zone.
 */

function getHauteurZone() {
	return $(window).height() * 0.873;
}

/**
 * Change le mode d'affichage de la timeline.
 *
 * @param {string} type - Peut être <tt>consignes</tt>, <tt>blogs</tt> ou <tt>evenements</tt>
 */
function changeTimelineMode(type) {
	const classCss = {};
	classCss.consignes = 'show_consignes';
	classCss.blogs = 'show_blogs';
	classCss.evenements = 'show_evenements';

	if (!$('body').hasClass(classCss[type])) {
		CCN.projet.showWholeTimeline();

		for (const index in classCss) {
			$('body').removeClass(classCss[index]);
		}

		$('body').addClass(classCss[type]);

		updateMenuIcon([type], 'timelineMode');
	}

	$('#menu_bas .logo a.menu_logo_type_sidebarView').removeClass('selected');

}
/**
 * Gère les événements lors du click sur une consigne et appelle {@link consigne#showInTimeline}.
 *
 * @param {number} numero - ID SPIP de l'objet
 *
 * @example
 * // Avec l'ID SPIP #146 de la consigne
 * showConsigneInTimeline(146, true);
 *
 * @see callConsigne
 * @see consigne#showInTimeline
 */

function showConsigneInTimeline(numero) {
	for (const consigne of CCN.consignes) {
		if (consigne.id == numero) {
			consigne.showInTimeline();
		}
	}
}
/**
 * Gère les événements lors du click sur une réponse et appelle {@link reponse#showInTimeline}.
 *
 * @param {number} numero - ID SPIP de l'objet
 *
 * @example
 * // Avec l'ID SPIP #146 de la consigne
 * showReponseInTimeline(146);
 *
 * @see callConsigne
 * @see consigne#ouvre
 */

function showReponseInTimeline(numero) {
	for (const consigne of CCN.consignes) {
		for (const reponse of consigne.reponses) {
			if (reponse.id == numero) {
				reponse.showInTimeline();
			}
		}
	}
}
/**
 * Redirige vers la fonction la plus appropriée
 * pour charger l'élément
 *
 * @param {Object} opts - Données identifiant l'élément
 * @param {string} opts.type - Le type de la page à charger (<tt>rubrique</tt>, <tt>article</tt>…)
 * @param {string} opts.mode - La modalité d'affichage de la page (<tt>ajax</tt>, <tt>ajax-detail</tt>, <tt>detail</tt>)
 * @param {string} [opts.id_rubrique] - L'id de la rubrique si c'est une <tt>rubrique</tt>
 * @param {string} [opts.id_article] - L'id de l'article si c'est un <tt>article</tt>
 * @param {string} [opts.id_consigne] - L'id de la consigne si c'est une réponse de classe
 *
 * @see callConsigne
 * @see callReponse
 * @see callClasse
 *
 * @todo Compléter au maximum la fonction
 */

function call(opts) {

	if (opts.type == 'rubrique' && opts.type_objet == 'travail_en_cours') {
		toggleSidebarExpand();
		// Classe
		callClasse(opts.id_rubrique);
	}

	if (opts.type == 'article' && opts.type_objet == 'travail_en_cours' && opts.type_entite == 'reponse') {
		// Réponse d'une classe
		callReponse(opts.id_article);
	}
}

/**
 * Appelle le chargement de la consigne
 * dans la sidebar principale et appelle
 * l'affichage de la consigne dans la timeline.
 *
 * @param {number} id_consigne - ID de la consigne
 *
 * @see loadContentInMainSidebar
 * @see showConsigneInTimeline
 *
 * @todo Définir le contenu de la sidebar secondaire
 */
// CCN.currentUserType
// current_user_type
function callConsigne(id_consigne) {

	if (!Number.isInteger(Number(id_consigne))) return;
	changeTimelineMode('consignes');

	// récupérer le rang déjà connu côté JS
	const consigneData = CCN.consignes.find(c => c.id == id_consigne);
	const numero       = consigneData ? consigneData.numero : '';
	const nextConsigne = consigneData ? CCN.consignes.find(c => c.numero === consigneData.numero + 1) : null;
	const dateLimite   = nextConsigne ? nextConsigne.data.date_texte : '';

	const url = CCN.projet.url_popup_consigne + "&id_article=" + id_consigne + "&rang=" + numero + "&date_limite=" + dateLimite;
	showConsigneInTimeline(id_consigne);
	setFullscreenModeToCols(false);
	updateMenuIcon(['consignes-' + id_consigne], 'mainView');

	loadContentInMainSidebar(
		url, function () {
			updateUrl(
				{
					'type_objet': 'consignes',
					'id_objet': id_consigne,
					'id_rubrique': id_consigne,
					'page': 'article',
				}, 'Consigne', "./spip.php?page=article&id_article=" + id_consigne + "&mode=complet"
			);
		}
	);

}
/**
 * Appelle le chargement de la réponse
 * dans la sidebar principale et appelle
 * le chargement de la réponse dans la sidebar secondaire.
 *
 * @param {number} id_reponse - ID de la réponse
 * @param {number} id_consigne - ID de la consigne parente
 *
 * @see loadContentInMainSidebar
 * @see loadContentInLateralSidebar
 * @see showConsigneInTimeline
 */

function callReponse(id_reponse) {

	if (!Number.isInteger(Number(id_reponse))) return;
	changeTimelineMode('consignes');
	setFullscreenModeToCols(true);

	const id_consigne = getIdConsigneFromIdReponse(id_reponse);

	const id_classe = getIdClasseFromIdReponse(id_reponse);

	updateMenuIcon(['consignes-' + id_consigne, 'classes-' + id_classe], 'mainView');

	const url = CCN.projet.url_popup_reponse + "&id_article=" + id_reponse;

	showConsigneInTimeline(id_consigne);
	loadContentInMainSidebar(
		url, function () {
			updateUrl(
				{
					'type_objet': 'travail_en_cours',
					'id_objet': id_reponse,
					'id_article': id_reponse,
					'page': 'article'
				}, "Réponse", "./spip.php?page=article&id_article=" + id_reponse + "&mode=complet"
			);
		}
	);
	const url_travail_en_cours = 'spip.php?page=rubrique&mode=detail&id_rubrique=' + CCN.travailEnCoursId;

	showReponseInTimeline(id_reponse);

}
/**
 * Appelle le chargement de la classe
 * dans la sidebar principale et appelle
 * le chargement de la classe dans la sidebar secondaire.
 *
 * @param {number} id_classe - ID de la classe
 *
 * @see loadContentInMainSidebar
 * @see loadContentInLateralSidebar
 *
 * @todo *1 : Modifier le contenu de la sidebar secondaire
 */

function callClasse(id_classe) {

	if (id_classe !== '' && !Number.isInteger(Number(id_classe))) return;
	changeTimelineMode('consignes');
	toggleSidebarExpand();
	setFullscreenModeToCols(true);
	updateMenuIcon(['classes', 'classes-' + id_classe], 'sidebarView');

	let url = CCN.projet.url_popup_classes;
	if (id_classe != '') {
		url = CCN.projet.url_popup_classes + '&id_objet=' + id_classe + '&type_objet=travail_en_cours';
	}
	loadContentInMainSidebar(
		url, function () {
			updateUrl(
				{
					'type_objet': 'classes',
					'id_objet': id_classe,
					'id_rubrique': id_classe,
					'page': 'rubrique'
				}, "Classe", "./spip.php?page=rubrique&id_objet=" + id_classe + "&mode=complet&type_objet=classes"
			);
		}
	);

	const url_travail_en_cours = 'spip.php?page=rubrique&mode=detail&id_rubrique=' + CCN.travailEnCoursId;
}
/**
 * Appelle le chargement des classes
 * dans la sidebar principale
 *
 * @see loadContentInLateralSidebar
 */

function callClasses() {
	changeTimelineMode('consignes');
	showSidebar();
	toggleSidebarExpand();
	setFullscreenModeToCols(true);
	updateMenuIcon(['classes'], 'sidebarView');

	blankMainSidebar('travail_en_cours');

	const url_travail_en_cours = 'spip.php?page=rubrique&mode=detail&id_rubrique=' + CCN.travailEnCoursId;
}

/**
 * Appelle le chargement des livrables
 * dans la sidebar principale
 *
 * @see loadContentInLateralSidebar
 */

function callLivrables() {
	changeTimelineMode('consignes');
	showSidebar();
	toggleSidebarExpand();
	updateMenuIcon(['livrables'], 'sidebarView');

	blankMainSidebar('livrables');
	setFullscreenModeToCols(true);

	const url_lateral = CCN.projet.url_popup_livrables;
}
/**
 * Appelle le chargement de l'article de blog
 * dans la sidebar principale et appelle
 * (…)
 *
 * @param {number} id_objet
 * @param {string} type_objet
 *
 * @see loadContentInMainSidebar
 * @see loadContentInLateralSidebar
 *
 * @todo Modifier le contenu de la sidebar secondaire
 * @todo Documenter
 */

function callArticleBlog(id_article) {
	if (!Number.isInteger(Number(id_article))) return;
	changeTimelineMode('blogs');
	setFullscreenModeToCols(false);
	updateMenuIcon(['blogs'], 'mainView');

	const url = CCN.projet.url_popup_blog + "&page=article&id_article=" + id_article;
	loadContentInMainSidebar(
		url, function () {
			updateUrl(
				{
					'type_objet': 'blogs',
					'id_objet': id_article,
					'id_article': id_article,
					'page': 'article'
				}, "Blog", "./spip.php?page=article&id_article=" + id_article + "&mode=complet"
			);
		}
	);
}
/**
 * Appelle le chargement de la ressource
 * dans la sidebar principale et appelle
 * (…)
 *
 * @param {number} id_objet
 * @param {string} type_objet
 *
 * @see loadContentInLateralSidebar
 *
 * @todo Modifier le contenu de la sidebar secondaire
 * @todo Documenter
 */

function callRessource() {
	changeTimelineMode('consignes');
	showSidebar();
	toggleSidebarExpand();
	updateMenuIcon(['ressources'], 'sidebarView');

	blankMainSidebar('ressources');
	setFullscreenModeToCols(true);

	const url_lateral = CCN.projet.url_popup_ressources;
}

/**
 * Appelle le chargement d'un article ressource
 * dans la sidebar secondaire
 *
 * @param {number} id_article
 *
 * @see loadContentInMainSidebar
 *
 * @todo Documenter
 */

function callRessourceArticle(id_article, type_objet) {
	if (!Number.isInteger(Number(id_article))) return;
	changeTimelineMode('consignes');
	setFullscreenModeToCols(true);
	updateMenuIcon([type_objet], 'sidebarView');

	const url = "./spip.php?page=article&id_article=" + id_article + "&mode=ajax-detail";
	loadContentInMainSidebar(
		url, function () {
			updateUrl(
				{
					'type_objet': type_objet,
					'id_article': id_article,
					'page': 'article'
				}, "Ressources", "./spip.php?page=article&id_article=" + id_article + "&type_objet=" + type_objet + "&mode=complet"
			);
		}
	);
}

/**
 * Appelle le chargement d'un article syndic
 * dans la sidebar secondaire
 *
 * @param {number} id_syndic_article
 *
 * @see loadContentInMainSidebar
 *
 * @todo Documenter
 */

function callRessourceSyndicArticle(id_syndic_article, type_objet) {
	if (!Number.isInteger(Number(id_syndic_article))) return;
	changeTimelineMode('consignes');
	setFullscreenModeToCols(true);
	updateMenuIcon([type_objet], 'sidebarView');

	const url = "./spip.php?page=syndic_article&id_syndic_article=" + id_syndic_article + "&mode=ajax-detail";
	loadContentInMainSidebar(
		url, function () {
			updateUrl(
				{
					'type_objet': type_objet,
					'id_syndic_article': id_syndic_article,
					'page': 'article'
				}, "Ressources", "./spip.php?page=syndic_article&id_syndic_article=" + id_syndic_article + "&type_objet=" + type_objet + "&mode=complet"
			);
		}
	);
}

/**
 * Appelle le chargement d'une rubrique ressource
 * dans la sidebar secondaire
 *
 * @param {number} id_rubrique
 *
 * @see loadContentInMainSidebar
 *
 * @todo Documenter
 */

function callRessourceRubrique(id_rubrique, type_objet) {
	if (!Number.isInteger(Number(id_rubrique))) return;
	changeTimelineMode('consignes');
	setFullscreenModeToCols(true);
	updateMenuIcon([type_objet], 'sidebarView');

	const url = "./spip.php?page=rubrique&id_rubrique=" + id_rubrique + "&mode=ajax-detail";
	loadContentInMainSidebar(
		url, function () {
			updateUrl(
				{
					'type_objet': type_objet,
					'id_rubrique': id_rubrique,
					'page': 'rubrique'
				}, "Ressources", "./spip.php?page=rubrique&id_rubrique=" + id_rubrique + "&type_objet=" + type_objet + "&mode=complet"
			);
		}
	);
}
/**
 * Appelle le chargement de l'événement
 * dans la sidebar principale et appelle
 * (…)
 *
 * @param {number} id_objet
 * @param {string} type_objet
 *
 * @see loadContentInMainSidebar
 * @see loadContentInLateralSidebar
 *
 * @todo Modifier le contenu de la sidebar secondaire
 * @todo Documenter
 */

function callArticleEvenement(id_objet, type_objet) {
	if (!Number.isInteger(Number(id_objet))) return;
	if (!['article', 'syndic_article'].includes(type_objet)) return;
	changeTimelineMode('evenements');
	setFullscreenModeToCols(false);
	updateMenuIcon(['evenements'], 'mainView');

	const url = CCN.projet.url_popup_evenement + "&page=" + type_objet + "&id_" + type_objet + "=" + id_objet;
	loadContentInMainSidebar(
		url, function () {
			updateUrl(
				{
					'type_objet': 'evenements',
					'id_article': id_objet,
					'page': type_objet
				}, "Événement", "./spip.php?page=" + type_objet + "&id_article=" + id_objet + "&mode=complet"
			);
		}
	);

}
/**
 * Appelle le chargement de l'agora
 * dans la sidebar principale et appelle
 * (…)
 *
 * @see loadContentInMainSidebar
 * @see loadContentInLateralSidebar
 *
 * @todo Modifier le contenu de la sidebar principale
 * @todo Modifier le contenu de la sidebar secondaire
 * @todo Documenter
 */

function callAgora() {
	changeTimelineMode('consignes');
	showSidebar();
	toggleSidebarExpand();
	updateMenuIcon(['agora'], 'sidebarView');

	blankMainSidebar('agora');
	setFullscreenModeToCols(true);

	const url_lateral = CCN.projet.url_popup_agora;

}

/**
 * @param {number} id_consigne
 * @param {number} id_rubrique_classe
 * @param {number} numero
 *
 * @todo Documenter
 */
function createReponse(id_consigne, id_rubrique_classe, numero) {

	changeTimelineMode('consignes');

	const consigneData = CCN.consignes && CCN.consignes.find(c => c.id == id_consigne);
	const nextConsigne = consigneData ? CCN.consignes.find(c => c.numero === consigneData.numero + 1) : null;
	const dateLimite   = nextConsigne ? nextConsigne.data.date_texte : '';
	const rang         = consigneData ? consigneData.numero : (numero || '');

	const url = CCN.projet.url_popup_reponseajout + "&id_consigne=" + id_consigne + "&id_rubrique=" + id_rubrique_classe + "&rang=" + rang + "&date_limite=" + dateLimite;
	loadContentInMainSidebar(url);

}
/**
 * Cherche la réponse correspondant à un id_reponse dans CCN.consignes.
 *
 * @param   {number} id_reponse
 * @returns {{consigne: object, reponse: object}|null}
 */
function findReponseById(id_reponse) {
	for (const consigne of CCN.consignes) {
		for (const reponse of consigne.reponses) {
			if (reponse.id === id_reponse) {
				return { consigne, reponse };
			}
		}
	}
	return null;
}

/**
 * @param {number} id_reponse
 * @returns {number|null} Id de la consigne parente
 */
function getIdConsigneFromIdReponse(id_reponse) {
	const found = findReponseById(id_reponse);
	return found ? found.consigne.id : null;
}

/**
 * @param {number} id_reponse
 * @returns {number|null} Id de la classe parente
 */
function getIdClasseFromIdReponse(id_reponse) {
	const found = findReponseById(id_reponse);
	return found ? found.reponse.classe_id : null;
}

/**
 * Met à jour les connecteurs de la timeline.
 * <br>
 * La fonction est appelée de manière récursive (<tt>setInterval(…, 1)</tt>)
 * afin de mettre à jour en même temps que la transition CSS de la timeline.
 *
 * @todo Éléments autres que DOM ?
 */
function updateConnecteurs() {
	$('.connecteur_timeline').each(
		function () {

			const connecteur_consigne = $('#consigne_haute' + $(this).data('consigne-id'));
			const connecteur_reponse = $('#reponse_haute' + $(this).data('reponse-id'));

			const connecteur = $(this);

			const x1 = connecteur_consigne.offset().left + connecteur_consigne.outerWidth();
			const y1 = connecteur_consigne.offset().top;
			const x2 = connecteur_reponse.offset().left;
			const y2 = connecteur_reponse.offset().top;

			const length = Math.sqrt((x1 - x2) * (x1 - x2) + (y1 - y2) * (y1 - y2));
			const angle = Math.atan2(y2 - y1, x2 - x1) * 180 / Math.PI;
			const transform = 'rotate(' + angle + 'deg)';

			connecteur.css(
				{
					'position': 'absolute',
					'transform': transform,
					'left': parseFloat(x1) + 'px',
					'top': parseFloat(y1) + 'px'
				}
			)
				.width(parseFloat(length) + 'px');
		}
	);
}

function handleCollision(y, responseHeight, timelineTop, timelineHeight) {
    const yMin = 0;
    const yMax = yMin + timelineHeight - responseHeight;
    if (y < yMin) return yMin;
    if (y > yMax) return yMax;
    return y;
}

function updateConnecteur(reponseObject, ui) {
	const reponseDOM = $(reponseObject)
	const idConsigne = reponseDOM.data('consigne-id')
	const idReponse = reponseDOM.data('reponse-id')
	const connecteurDOM = $(`#connecteur_consigne_${idConsigne}_reponse_${idReponse}`);
	const consigneDOM = $(`#consigne_haute${idConsigne}`);
	const timelineTop = CCN.timelineLayerConsignes.offset().top;
	const timelineHeight = CCN.timelineLayerConsignes.height();

	const x1 = consigneDOM.offset().left + consigneDOM.outerWidth();
	const y1 = consigneDOM.offset().top  + consigneDOM.outerHeight() / 2 - timelineTop;
	const x2 = reponseDOM.offset().left;
    const adjustedUiPositionTop = handleCollision(
		ui.position.top, 
		reponseDOM.outerHeight(),
		timelineTop, 
		timelineHeight
	);
    ui.position.top = adjustedUiPositionTop;
    const y2 = adjustedUiPositionTop + reponseDOM.outerHeight() / 2;

	const length = Math.sqrt((x1 - x2) * (x1 - x2) + (y1 - y2) * (y1 - y2));
	const angle = Math.atan2(y2 - y1, x2 - x1) * 180 / Math.PI;
	const transform = 'rotate(' + angle + 'deg)';

	connecteurDOM.css(
		{
			'position': 'absolute',
			'transform': transform,
			'left': parseFloat(x1) + 'px',
			'top': parseFloat(y1) + 'px'
		}
	)
		.width(parseFloat(length) + 'px');
}

/**
 * Change la couleur du bouton une fois cliqué.
 *
 * @param {string} val - Sélecteur de l'élément DOM en jQuery
 * @todo  Améliorer la récupération de la couleur ?
 */
function changeCouleurLogoMenu(val) {
	const color = $(val).css('background-color');
	$(val).closest('li').children('h3').css('background-color', color);
}

/**
 * Met à jour l'URL
 */

function updateUrl(object, title, url) {

	currentState = object;
	if (CCN.hash != '') {
		if (CCN.hash.substring(0, 5) == 'forum') {

		} else {

			History.pushState(object, title, url + '#' + CCN.hash);

		}

		setTimeout(
			function () {

				if (/^[\w-]+$/.test(CCN.hash)) {
					const anchor = $("#" + CCN.hash);
					if (anchor.length > 0) {
						// TODO : cela est appelé deux fois minimum à cause de History.js (donc un trigger('click') sur .triggertoggleshow ne fonctionne pas car il ouvre puis ferme)

						// Forum : ouvre les items
						anchor.find('.toggleshow').show();
						anchor.closest('.intervention_item_around').find('.toggleshow').show();

						$('#sidebar_content, #sidebar_main_inner, #sidebar_lateral_inner').animate({ scrollTop: anchor.offset().top - 60 }, 'slow');
					}
				}

				CCN.hash = '';
			}, 500
		);
	} else {
		History.pushState(object, title, url);
	}
}
/**
 * Met à jour le cookie et recharge la page.
 *
 * @param {string} url - URL de la page de destination
 * @param {string} cookie_nom - Nom du cookie
 * @param {string} cookie_valeur - Valeur du cookie
 */

function reloadAndSetCookie(url, cookie_nom, cookie_valeur) {
	document.cookie = cookie_nom + "=" + encodeURIComponent(cookie_valeur) + "; SameSite=Strict; Secure";
	reload(url + '/?rub=' + encodeURIComponent(cookie_valeur));
}
/**
 * Gère le rechargement de la page.
 *
 * @param {string} url - URL de la page à charger avec AJAX ou <tt>self</tt> pour recharger la même page
 *
 * @see reloadAndSetCookie
 */

function reload(url) {
	if (url === 'self') {
		location.reload(true);
	} else if ((url.startsWith('/') && !url.startsWith('//')) || url.startsWith(window.location.origin)) {
		window.location.href = url;
	}
}

/**
 * Charge l'URL dans la sidebar principale.
 *
 * @param {string} url - URL de la page à charger avec AJAX
 * @param {string} typePage - Type du contenu SPIP : <tt>article</tt>, <tt>rubrique</tt>…
 * @param {string} typeObjet - Type de l'objet principal de la page : <tt>consignes</tt>, <tt>travail_en_cours</tt>, <tt>blogs</tt>, <tt>evenements</tt>, <tt>ressources</tt>, <tt>classes</tt>…
 *
 * @see loadContentInLateralSidebar
 *
 * @todo Loading et son callback
 */

function loadContentInMainSidebar(url, callback) {
	$('body').addClass('loading');
	showSidebar();
	emptyMainSidebar();

	$('#sidebar_main_inner').load(url, function (response, status, xhr) {

		if (status === "error") {
			if (CCN.debug) { console.error("Erreur de chargement :", xhr.status, xhr.statusText); }
			return;
		}

		if (!response || response.trim() === "") {
			if (CCN.debug) { console.warn("Réponse vide !"); }
		}

		$('body').removeClass('loading');
		$('#sidebar_content').scrollTop(0);
		_sidebarFocusFirst();

		if (callback) {
			callback(response);
		}

		antifloodHashChange = false;
	});
}

/**
 * Update le menu
 */
function updateMenuIcon(ids, mode) {
	if (mode === 'timelineMode' || mode === 'mainView') {
		$('#menu_bas .logo a').removeClass('selected');
		for (const id of ids) {
			$('.menu_logo_' + id).addClass('selected');
		}
	}

	if (mode === 'sidebarView') {
		$('#menu_bas .logo a.menu_logo_type_sidebarView').removeClass('selected');
		$('#menu_bas .logo a.logo_menu_classe').removeClass('selected');
		for (const id of ids) {
			$('.menu_logo_' + id).addClass('selected');
		}
	}
}

/**
 * Vide la sidebar principale.
 *
 * @see loadContentInMainSidebar
 */
function emptyMainSidebar() {
	$('#sidebar_main_inner').html('<div class="popup"><div class="sidebar_bubble sidebar_bubble_empty"></div></div>');
}

/**
 * Définit l'affichage plein écran de/des sidebars
 */

function setFullscreenModeToCols(setCols) {
	if (setCols == true) {
		$('body').addClass('modeCols').removeClass('modeFullscreen');
	} else {
		$('body').removeClass('modeCols').addClass('modeFullscreen');
	}
}

/**
 * Ajoute un bloc blanc vide dans la sidebar principale.
 *
 * @see loadContentInMainSidebar
 */

const _blankMainSidebarTemplates = {
	'travail_en_cours': '<div class="sidebar_bubble"><div class="fiche_titre couleur_texte_ressources couleur_ressources0"><div class="texte"><div class="titre">Travail en cours</div></div></div></div><div class="sidebar_bubble sidebar_bubble_blank">Naviguez dans l\'espace travail en cours grâce à la barre latérale sur votre droite.</div>',
	'livrables':        '<div class="sidebar_bubble"><div class="fiche_titre couleur_texte_livrables couleur_livrables0"><div class="texte"><div class="titre">Espace livrables</div></div></div></div><div class="sidebar_bubble sidebar_bubble_blank">Naviguez dans l\'espace livrables grâce à la barre latérale sur votre droite.</div>',
	'ressources':       '<div class="sidebar_bubble"><div class="fiche_titre couleur_texte_ressources couleur_ressources0"><div class="texte"><div class="titre">Espace ressources</div></div></div></div><div class="sidebar_bubble sidebar_bubble_blank">Naviguez dans l\'espace ressources grâce à la barre latérale sur votre droite.</div>',
	'agora':            '<div class="sidebar_bubble"><div class="fiche_titre couleur_texte_ressources couleur_ressources0"><div class="texte"><div class="titre">Agora</div></div></div></div><div class="sidebar_bubble sidebar_bubble_blank">Naviguez dans Agora grâce à la barre latérale sur votre droite.</div>',
};

function blankMainSidebar(key) {
	const html = _blankMainSidebarTemplates[key] || '';
	$('#sidebar_main_inner').html('<div class="popup popup_blank">' + html + '</div>');
}

/**
 * Affiche la sidebar principale.
 *
 * @see loadContentInMainSidebar
 */

function showSidebar() {
	_sidebarTrigger = document.activeElement;
	$('body').addClass('hasSidebarOpen');
	$('#sidebar').addClass('show');
	updateConnecteurs();
}

function closeSidebar() {
	$('body').removeClass('hasSidebarOpen hasSidebarExpanded');
	$('#sidebar').removeClass('show');
	$('#menu_bas .logo a').removeClass('selected');
	if (_sidebarTrigger && typeof _sidebarTrigger.focus === 'function') {
		_sidebarTrigger.focus();
	}
	_sidebarTrigger = null;
	const interval = setInterval(updateConnecteurs, 16);
	setTimeout(() => clearInterval(interval), 500);
}

function _sidebarFocusFirst() {
	const $focusable = _sidebarFocusableElements();
	if ($focusable.length) {
		$focusable.first().focus();
	} else {
		$('#sidebar').attr('tabindex', '-1').focus();
	}
}
