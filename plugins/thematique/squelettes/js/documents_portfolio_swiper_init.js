/**
 * Initialise les carrousels Swiper (images et PDF) de "#documents_portfolio"
 * s'il est présent sur la page.
 *
 * @see initImagesSwiper
 * @see initPdfSwipers
 */
$(function () {
	const $documents_portfolio = $('#documents_portfolio');
	if ($documents_portfolio.length) {
		initImagesSwiper($documents_portfolio);
		initPdfSwipers($documents_portfolio);
	}
});
