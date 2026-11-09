/**
 * Aperçu des PDF joints dans la sidebar (portfolio de
 * noisettes/inc/ajouter_document.html) : la première page s'affiche à la
 * place de l'icône du document, avec une navigation page précédente /
 * suivante quand le PDF en a plusieurs.
 *
 * - Bibliothèque : PDF.js 3.11.174 et son visualiseur (PDFSinglePageViewer,
 *   pdf_viewer.min.js/.css), chargés depuis cdnjs dans layout.html. Sans eux
 *   (CDN bloqué, échec du chargement), l'icône d'origine reste en place et le
 *   document reste téléchargeable comme avant.
 * - Taille et orientation : chaque page est affichée aussi grande que
 *   possible dans la largeur de la sidebar ET dans sa hauteur visible,
 *   flèches comprises (mode 'page-fit' de PDF.js) : une page paysage
 *   s'affiche en paysage, une page portrait est réduite pour être vue en
 *   entier sans défiler. Le clic l'ouvre en grand (mediabox).
 * - Initialisation : le contenu de la sidebar arrive toujours en ajax
 *   (loadContentInMainSidebar, qui appelle jQuery.spip.triggerAjaxLoad) et le
 *   portfolio est rechargé par ajaxReload('documents') : on s'accroche donc à
 *   onAjaxLoad en plus du chargement initial. Chaque lien n'est traité qu'une
 *   fois (data-pdf-apercu), et le PDF n'est téléchargé que lorsque l'aperçu
 *   devient visible (onglet masqué de la sidebar, document en bas de liste).
 */
(function () {
	const PDFJS_WORKER_SRC = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
	// Deux emplacements : la liste des pièces jointes (#documents_portfolio,
	// documents {vu=non}) et les documents insérés dans le texte par un
	// raccourci <docXX> (modèle SPIP : div.spip_document > figure > a.spip_doc_lien),
	// ce que fait la modale de publication.
	const SELECTEUR = [
		'.portfolio > .spip_documents > a[type="application/pdf"]',
		'.spip_document .spip_doc_inner > a.spip_doc_lien[type="application/pdf"]',
	].join(', ');
	// Hauteur de la barre page précédente / suivante (boutons 32 px + 8 px de
	// marge, cf .pdf_apercu_pager dans sidebar.css.html) et marge de confort
	// sous l'aperçu, déduites de la hauteur visible.
	const HAUTEUR_PAGER = 40;
	const MARGE = 10;

	/**
	 * Worker PDF.js unique, partagé par tous les aperçus. Sans lui, chaque
	 * getDocument() démarre son propre worker (téléchargement et compilation
	 * de pdf.worker.min.js, ~1 Mo) : environ 0,5 s avant même de commencer à
	 * télécharger le PDF, multiplié par le nombre de PDF de la fiche.
	 *
	 * @returns {Object} pdfjsLib.PDFWorker
	 */
	let worker = null;
	function workerPartage() {
		if (!worker) {
			window.pdfjsLib.GlobalWorkerOptions.workerSrc = PDFJS_WORKER_SRC;
			worker = new window.pdfjsLib.PDFWorker({ verbosity: window.pdfjsLib.VerbosityLevel.ERRORS });
		}
		return worker;
	}

	/**
	 * Remplace "@cle@" par sa valeur dans une chaîne de langue.
	 *
	 * @param {string} texte
	 * @param {Object} valeurs
	 * @returns {string}
	 */
	function remplacer(texte, valeurs) {
		return String(texte || '').replace(/@(\w+)@/g, (m, cle) => (cle in valeurs ? valeurs[cle] : m));
	}

	/**
	 * Zone qui défile autour de `el` (dans la sidebar : .ctabs-content,
	 * overflow-y auto), ou null s'il n'y en a pas (la fenêtre défile).
	 *
	 * @param {Element} el
	 * @returns {Element|null}
	 */
	function zoneDefilement(el) {
		for (let parent = el.parentElement; parent && parent !== document.body; parent = parent.parentElement) {
			if (/(auto|scroll)/.test(getComputedStyle(parent).overflowY) && parent.clientHeight > 0) {
				return parent;
			}
		}
		return null;
	}

	/**
	 * Hauteur visible de cette zone, padding et MARGE déduits ; à défaut, celle
	 * de la fenêtre.
	 *
	 * @param {Element|null} zone - Résultat de zoneDefilement()
	 * @returns {number} Hauteur en px CSS
	 */
	function hauteurVisible(zone) {
		if (!zone) return window.innerHeight - MARGE;
		const style = getComputedStyle(zone);
		return zone.clientHeight - parseFloat(style.paddingTop) - parseFloat(style.paddingBottom) - MARGE;
	}

	/**
	 * Construit l'aperçu d'un lien PDF du portfolio : un PDFSinglePageViewer
	 * (une page à la fois, rendu et mise à l'échelle par PDF.js) dans un cadre
	 * dont on fixe la hauteur, plus la barre page précédente / suivante.
	 *
	 * @param {HTMLAnchorElement} lien - Lien `.mediabox` vers le PDF
	 * @returns {Promise<void>}
	 */
	async function creerApercu(lien) {
		const urlPdf = lien.getAttribute('href');
		if (!urlPdf) return;

		let pdf;
		try {
			pdf = await window.pdfjsLib.getDocument({
				url: urlPdf,
				worker: workerPartage(),
				// Erreurs seulement dans la console, ici et dans le worker (cf
				// workerPartage) : PDF.js signale par défaut des défauts sans effet
				// sur l'affichage, comme les instructions de hinting d'une police
				// TrueType intégrée au PDF ("TT: undefined function: 32"), répétés
				// pour chaque police et chaque aperçu.
				verbosity: window.pdfjsLib.VerbosityLevel.ERRORS,
				// Ne télécharger que les morceaux du fichier utiles aux pages
				// affichées (requêtes partielles, Range) plutôt que le PDF entier :
				// un storyboard de photos pèse plusieurs Mo pour une page 1 de
				// quelques centaines de Ko. Sans Range côté serveur, PDF.js
				// retombe de lui-même sur un téléchargement complet.
				disableAutoFetch: true,
				disableStream: true,
			}).promise;
		} catch (e) {
			// L'icône d'origine reste affichée : le document reste accessible.
			if (CCN.debug) { console.warn(CCN.lang.erreur_chargement_de, urlPdf, e); }
			return;
		}

		// Le visualiseur exige un conteneur en position absolue (contenant un div
		// .pdfViewer) : il est posé dans un cadre en position relative, dont la
		// hauteur est fixée par ajuster() ci-dessous.
		const $cadre = $('<div class="pdf_apercu_cadre"><div class="pdf_apercu_conteneur"><div class="pdfViewer"></div></div></div>');
		$(lien).addClass('pdf_apercu').empty().append($cadre);

		// Un document inséré dans le texte n'a pas la mediabox (contrairement au
		// portfolio) : son clic quitterait l'application pour ouvrir le PDF. On
		// la lui pose, pour ouvrir le PDF en grand comme depuis le portfolio.
		// height (et non minHeight) : la mediabox fait alors remplir le cadre à
		// l'iframe au lieu de lui imposer un format 16:9 calculé sur la largeur,
		// qui dépassait en bas sur un écran large (cf data-box-height de
		// ajouter_document.html).
		if (!$(lien).hasClass('lity-enabled') && $.fn.mediabox) {
			$(lien).removeAttr('onclick').mediabox({ type: 'iframe', height: '90vh' }).addClass('mediabox hasbox');
		}

		const eventBus = new window.pdfjsViewer.EventBus();
		const viewer = new window.pdfjsViewer.PDFSinglePageViewer({
			container: $cadre.children()[0],
			eventBus,
			removePageBorders: true,
			// Aperçu seulement : ni sélection de texte ni liens/formulaires du PDF,
			// le clic doit ouvrir la mediabox.
			textLayerMode: 0,
			annotationMode: window.pdfjsLib.AnnotationMode.DISABLE,
		});

		// Ne dessiner que la page affichée : par défaut, PDF.js dessine aussi la
		// page suivante en tâche de fond. Pour un aperçu, ce rendu anticipé
		// télécharge des données en plus (requêtes partielles) et occupe le
		// worker partagé, retardant la page 1 des autres PDF de la fiche. Une
		// page suivante s'affiche donc au clic, après son téléchargement.
		// getHighestPriority est interne (PDFRenderingQueue, non exportée) :
		// à revérifier en cas de montée de version de PDF.js.
		const file = viewer.renderingQueue;
		file.getHighestPriority = (visible) => {
			for (const { view } of visible.views) {
				if (!file.isViewFinished(view)) return view;
			}
			return null;
		};

		const avecPager = pdf.numPages > 1;
		const $pager = $('<div class="pdf_apercu_pager"></div>');
		const $prec = $('<button type="button" class="pdf_apercu_prec">‹</button>')
			.attr({ title: CCN.lang.pdf_page_precedente, 'aria-label': CCN.lang.pdf_page_precedente })
			.on('click', () => { viewer.previousPage(); });
		const $suiv = $('<button type="button" class="pdf_apercu_suiv">›</button>')
			.attr({ title: CCN.lang.pdf_page_suivante, 'aria-label': CCN.lang.pdf_page_suivante })
			.on('click', () => { viewer.nextPage(); });
		const $compteur = $('<span class="pdf_apercu_compteur" aria-live="polite"></span>');
		$pager.append($prec, $compteur, $suiv);
		if (avecPager) { $(lien).after($pager); }

		/**
		 * Donne au cadre la hauteur de la page affichée à pleine largeur, sans
		 * dépasser la hauteur visible (moins la barre de navigation), puis laisse
		 * PDF.js faire tenir la page dedans ('page-fit' : largeur ET hauteur).
		 * Une page paysage donne un cadre bas, une page portrait est réduite.
		 */
		async function ajuster() {
			const page = await pdf.getPage(viewer.currentPageNumber);
			const { width, height } = page.getViewport({ scale: 1 }); // tient compte du /Rotate
			const largeur = lien.clientWidth || 380;
			const hauteurMax = hauteurVisible(zoneDefilement(lien)) - (avecPager ? HAUTEUR_PAGER : 0);
			$cadre.css('height', Math.floor(Math.min(hauteurMax, largeur * height / width)) + 'px');
			viewer.currentScaleValue = 'page-fit';
		}

		/**
		 * Compteur, état des flèches et page ouverte au clic.
		 *
		 * @param {number} numero - Page affichée (1-indexé)
		 */
		function majNavigation(numero) {
			// La mediabox lit data-href-popin (posé par spip.mediabox.js) avant
			// href, via .data() qui met la valeur en cache : on met à jour les trois.
			const urlPage = urlPdf + '#page=' + numero;
			lien.setAttribute('href', urlPage);
			$(lien).attr('data-href-popin', urlPage).data('href-popin', urlPage);
			$compteur.text(remplacer(CCN.lang.pdf_page_sur, { page: numero, total: pdf.numPages }));
			$prec.prop('disabled', numero <= 1);
			$suiv.prop('disabled', numero >= pdf.numPages);
		}

		// pagechanging n'est pas émis pour la page 1 au chargement : pagesinit.
		eventBus.on('pagesinit', () => { majNavigation(1); ajuster(); });
		eventBus.on('pagechanging', ({ pageNumber }) => { majNavigation(pageNumber); ajuster(); });
		viewer.setDocument(pdf);

		// Largeur du lien ou hauteur de la zone qui défile modifiée (fenêtre
		// redimensionnée, barre de défilement apparue avec l'aperçu lui-même) :
		// on réajuste. Stable : un ajustement sans changement de taille ne
		// redéclenche rien. L'observateur s'arrête si l'aperçu a disparu (autre
		// contenu chargé dans la sidebar).
		const observateurTaille = new ResizeObserver(() => {
			if (!document.contains(lien)) {
				observateurTaille.disconnect();
				return;
			}
			ajuster();
		});
		observateurTaille.observe(lien);
		const zone = zoneDefilement(lien);
		if (zone) { observateurTaille.observe(zone); }
	}

	/**
	 * Prépare l'aperçu des liens PDF non encore traités sous `racine`.
	 *
	 * @param {Element|Document} racine
	 */
	function initApercusPdf(racine) {
		// CCN est déclaré par `let` (globales.js) : il n'est pas une propriété de
		// window, d'où typeof plutôt que window.CCN.
		if (!window.pdfjsLib || !window.pdfjsViewer || typeof CCN === 'undefined' || !CCN.lang) return;
		const liens = $(racine || document).find(SELECTEUR).filter(function () {
			return !this.hasAttribute('data-pdf-apercu');
		}).attr('data-pdf-apercu', '1').toArray();
		if (!liens.length) return;

		if (!('IntersectionObserver' in window)) {
			liens.forEach(creerApercu);
			return;
		}
		const observateur = new IntersectionObserver((entrees) => {
			entrees.forEach((entree) => {
				if (!entree.isIntersecting) return;
				observateur.unobserve(entree.target);
				creerApercu(entree.target);
			});
		}, { rootMargin: '200px' });
		liens.forEach((lien) => observateur.observe(lien));
	}

	window.initApercusPdf = initApercusPdf;

	$(function () {
		initApercusPdf(document);
		// Démarre le worker dès que le navigateur est inoccupé après le chargement
		// de la page : il est prêt quand la sidebar s'ouvre sur une fiche avec PDF,
		// au lieu d'ajouter son démarrage au délai d'affichage de l'aperçu.
		const prechauffer = () => { if (window.pdfjsLib) workerPartage(); };
		if ('requestIdleCallback' in window) {
			requestIdleCallback(prechauffer, { timeout: 3000 });
		} else {
			setTimeout(prechauffer, 1000);
		}
	});
	if (typeof onAjaxLoad === 'function') {
		onAjaxLoad(function () { initApercusPdf(this); });
	}
})();
