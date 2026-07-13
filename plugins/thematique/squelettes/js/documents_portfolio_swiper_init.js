$(function () {
	const $documents_portfolio = $('#documents_portfolio');
	if ($documents_portfolio.length) {
		initImagesSwiper($documents_portfolio);
		initPdfSwipers($documents_portfolio);
	}
});
