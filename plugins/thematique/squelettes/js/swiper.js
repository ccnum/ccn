(function() {

const pdfScript = document.querySelector('script[src*="pdf.min.js"]');
const PDFJS_WORKER_SRC = pdfScript
	? pdfScript.src.replace('pdf.min.js', 'pdf.worker.min.js')
	: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

/**
 * Initialise un Swiper avec navigation, pagination et effet fondu.
 *
 * @param {HTMLElement} swiperEl - Élément DOM racine du Swiper
 * @param {Object} [opts={}] - Options de sélecteurs CSS
 * @param {string} [opts.nextSelector='.swiper-button-next-custom'] - Sélecteur du bouton suivant
 * @param {string} [opts.prevSelector='.swiper-button-prev-custom'] - Sélecteur du bouton précédent
 * @param {string} [opts.paginationSelector='.swiper-pagination'] - Sélecteur de la pagination
 * @returns {Swiper} Instance Swiper créée
 */
function initFlipSwiper(swiperEl, opts = {}) {
	const $swiper = $(swiperEl);

	const {
		nextSelector = '.swiper-button-next-custom',
		prevSelector = '.swiper-button-prev-custom',
		paginationSelector = '.swiper-pagination',
	} = opts;

	return new Swiper(swiperEl, {
		navigation: {
			nextEl: $swiper.find(nextSelector)[0],
			prevEl: $swiper.find(prevSelector)[0],
		},
		pagination: {
			el: $swiper.find(paginationSelector)[0],
			clickable: true,
		},
		slidesPerView: 1,
		centeredSlides: true,
		spaceBetween: 0,
		speed: 500,
		loop: true,
		autoHeight: true,
		autoplay: false,
		effect: 'fade',
		fadeEffect: {
			crossFade: true,
		},
	});
}

/**
 * Crée un Swiper image à partir des `.spip_documents` contenant des images dans le portfolio.
 *
 * @param {jQuery} $documents_portfolio - Conteneur parent des éléments `.spip_documents`
 * @returns {void}
 */
function initImagesSwiper($documents_portfolio) {
	// Get all spip_documents image
	const $imagesDocs = $documents_portfolio
		.find('.spip_documents')
		.filter(function () {
			return $(this).find('a.mediabox[type^="image/"]').length > 0;
		});

	if (!$imagesDocs.length) return;

	// If there's any image, create a Image Swiper
	const $swiperContainer = $(`
	<div class="swiper">
		<div class="swiper-wrapper"></div>
		<div class="swiper-button-prev-custom"></div>
		<div class="swiper-button-next-custom"></div>
		<div class="swiper-pagination"></div>
	</div>
`);

	// Create a Slide for each image
	$imagesDocs.each(function () {
		const $doc = $(this);
		// Hide old display
		const $link = $doc.find('a.mediabox[type^="image/"]').first();
		$link.hide();

		// Generate Slide
		const $slide = $('<div class="swiper-slide"></div>');

		// Clone image to add into Swiper Slide and avoid error in Lity index
		const $imgOriginal = $link.find('img').first();

		const $imgClone = $('<img class="swiper-img">')
			.attr('src', $imgOriginal.attr('src'))
			.attr('alt', $imgOriginal.attr('alt'))
			.css({ cursor: 'pointer' })
			.on('click', function () {
				$link[0].click();
			});

		$slide.append($imgClone);

		// Clone & add the delete button
		const $btnSupprimer = $doc.find('.action_supprimer').first();
		if ($btnSupprimer.length) {
			$slide.append($btnSupprimer.clone(true, true));
		}

		// Append the Slide into Swiper
		$swiperContainer.find('.swiper-wrapper').append($slide);
	});

	$documents_portfolio.prepend($swiperContainer);

	// Initialize Swiper
	initFlipSwiper($swiperContainer[0]);
}

/**
 * Crée un Swiper PDF par page pour chaque `.spip_documents` contenant un PDF dans le portfolio.
 * Nécessite que PDF.js (`window.pdfjsLib`) soit chargé.
 *
 * @param {jQuery} $documents_portfolio - Conteneur parent des éléments `.spip_documents`
 * @returns {Promise<void>}
 */
async function initPdfSwipers($documents_portfolio) {
	// Check if PDF.js is correctly loaded
	if (!window.pdfjsLib) return;

	pdfjsLib.GlobalWorkerOptions.workerSrc = PDFJS_WORKER_SRC;

	// Get all spip_documents PDF
	const $pdfDocs = $documents_portfolio
		.find('.spip_documents')
		.filter(function () {
			return (
				$(this).find('a.mediabox[type="application/pdf"]').length > 0
			);
		});

	if (!$pdfDocs.length) return;

	// Create a Slide for each PDF
	for (const docEl of $pdfDocs.toArray()) {
		const $doc = $(docEl);
		const $link = $doc.find('a.mediabox[type="application/pdf"]').first();
		const pdfUrl = $link.attr('href');
		if (!pdfUrl) continue;
		const pdfName = decodeURIComponent(pdfUrl.split('/').pop());
		const $imgSrc = $link.find('img').first().attr('src') ?? '';

		const $container = $('<div class="swiper-pdf-container"></div>');
		// Generate Container which will contain loader and PDF Swiper
		$doc.before($container);

		// Generate Loader
		const $loader = $('<div class="swiper-pdf-loader">');
		if ($imgSrc) { $('<img>').attr('src', $imgSrc).appendTo($loader); }
		$('<span>').text('Chargement de "' + pdfName + '" ...').appendTo($loader);
		$container.append($loader);

		// Extract delete button before deleting old display
		const $btnSupprimer = $doc.find('.action_supprimer').first();
		// Delete old display
		$doc.remove();

		// Generate PDF Swiper
		const $pdfSwiper = $(`
			<div class="pdf-swiper swiper" style="display:none;">
				<div class="swiper-wrapper"></div>
				<div class="swiper-button-prev-custom pdf-prev"></div>
				<div class="swiper-button-next-custom pdf-next"></div>
				<div class="swiper-pagination pdf-pagination"></div>
			</div>
		`);
		$container.append($pdfSwiper);

		let pdf;

		// Try to load PDF...
		try {
			pdf = await pdfjsLib.getDocument(pdfUrl).promise;
			// ... if not, display error message
		} catch (e) {
			const $erreur = $('<div class="swiper-pdf-loader">');
			if ($imgSrc) { $('<img>').attr('src', $imgSrc).appendTo($erreur); }
			$('<span>').text('Erreur lors du chargement de "' + pdfName + '"').appendTo($erreur);
			$container.empty().append($erreur);
			continue;
		}

		// For every PDF page, generate a slide which contains page converted to canvas.
		for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
			// Convert PDF page to canvas
			const page = await pdf.getPage(pageNum);
			const viewport = page.getViewport({ scale: 1 });

			const canvas = document.createElement('canvas');
			canvas.width = viewport.width;
			canvas.height = viewport.height;

			await page.render({
				canvasContext: canvas.getContext('2d'),
				viewport,
			}).promise;

			$(canvas).css({
				maxWidth: '100%',
				height: 'auto',
				display: 'block',
				margin: '0 auto',
			});

			// Generate Slide
			const $slide = $('<div class="swiper-slide"></div>');

			// Clone old link to attach old action on current Slide with corresponding page
			const $linkClone = $link
				.clone(false)
				.attr('href', pdfUrl + '#page=' + pageNum)
				.empty()
				.append(canvas);

			$slide.append($linkClone);
			$linkClone.mediabox();

			// Clone delete button if it's present
			if ($btnSupprimer.length) {
				$slide.append($btnSupprimer.clone(true, true));
			}

			$pdfSwiper.find('.swiper-wrapper').append($slide);
		}

		// Hide Loader and display PDF Swiper
		$loader.remove();
		$pdfSwiper.show();

		// Initialize Swiper
		initFlipSwiper($pdfSwiper[0], {
			nextSelector: '.pdf-next',
			prevSelector: '.pdf-prev',
			paginationSelector: '.pdf-pagination',
		});
	}
}

/**
 * Crée un Swiper PDF par page à partir d'un lien PDF dans un commentaire.
 * Nécessite que PDF.js (`window.pdfjsLib`) soit chargé.
 *
 * @param {jQuery} $portfolio_grand - Élément `<a>` pointant vers le PDF
 * @returns {Promise<void>}
 */
async function initPdfSwipersInComment($portfolio_grand) {
	// Check if PDF.js is correctly loaded
	if (!window.pdfjsLib) return;

	pdfjsLib.GlobalWorkerOptions.workerSrc = PDFJS_WORKER_SRC;

	if ($portfolio_grand.length !== 1) return;


	const pdfUrl = $portfolio_grand.attr('href');
	const pdfName = decodeURIComponent(pdfUrl.split('/').pop());
	const $imgSrc = "";

	const $container = $('<div class="swiper-pdf-comment-container"></div>');
	$portfolio_grand.before($container);

	// Generate Loader
	const $loader = $('<div class="swiper-pdf-loader">');
	if ($imgSrc) { $('<img>').attr('src', $imgSrc).appendTo($loader); }
	$('<span>').text('Chargement de "' + pdfName + '" ...').appendTo($loader);
	$container.append($loader);

	// Generate PDF Swiper
	const $pdfSwiper = $(`
		<div class="pdf-swiper swiper" style="display:none;">
			<div class="swiper-wrapper"></div>
			<div class="swiper-button-prev-custom pdf-prev"></div>
			<div class="swiper-button-next-custom pdf-next"></div>
			<div class="swiper-pagination pdf-pagination"></div>
		</div>
	`);
	// Delete old display
	$portfolio_grand.remove();
	$container.append($pdfSwiper);

	let pdf;

	// Try to load PDF...
	try {
		pdf = await pdfjsLib.getDocument(pdfUrl).promise;
		// ... if not, display error message
	} catch (e) {
		const $erreur = $('<div class="swiper-pdf-loader">');
		if ($imgSrc) { $('<img>').attr('src', $imgSrc).appendTo($erreur); }
		$('<span>').text('Erreur lors du chargement de "' + pdfName + '"').appendTo($erreur);
		$container.empty().append($erreur);
		return;
	}

	// For every PDF page, generate a slide which contains page converted to canvas.
	for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
		// Convert PDF page to canvas
		const page = await pdf.getPage(pageNum);
		const viewport = page.getViewport({ scale: 1 });

		const canvas = document.createElement('canvas');
		canvas.width = viewport.width;
		canvas.height = viewport.height;

		await page.render({
			canvasContext: canvas.getContext('2d'),
			viewport,
		}).promise;

		$(canvas).css({
			maxWidth: '100%',
			height: 'auto',
			display: 'block',
			margin: '0 auto',
		});

		// Generate Slide
		const $slide = $('<div class="swiper-slide"></div>');

		// Clone old link to attach old action on current Slide with corresponding page
		const $linkClone = $portfolio_grand
			.clone(false)
			.attr('href', pdfUrl + '#page=' + pageNum)
			.empty()
			.append(canvas);

		$slide.append($linkClone);
		$linkClone.mediabox();

		$pdfSwiper.find('.swiper-wrapper').append($slide);
	}

	// Hide Loader and display PDF Swiper
	$loader.remove();
	$pdfSwiper.show();

	// Initialize Swiper
	initFlipSwiper($pdfSwiper[0], {
		nextSelector: '.pdf-next',
		prevSelector: '.pdf-prev',
		paginationSelector: '.pdf-pagination',
	});
}

// Expose functions globally
window.initFlipSwiper = initFlipSwiper;
window.initImagesSwiper = initImagesSwiper;
window.initPdfSwipers = initPdfSwipers;
window.initPdfSwipersInComment = initPdfSwipersInComment;
})();
