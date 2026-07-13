/**
 * Fonction pour récupérer le numéro d'icône d'une classe depuis le menu-classes
 * en se basant sur le nom de la classe.
 *
 * @param {string} className - Nom de la classe
 */
function getClassIconNumberByClassName(className) {
	const $ulClasses = $('#menu-classes').find('ul').first();
	let iconNumber = null;
	$ulClasses.find('li.logo').each(function () {
		const $a = $(this).find('a').first();
		if ($a.attr('data-tip').trim() === className) {
			const logo_classe_classes = $a.attr('class').split(' ').filter(c => c.startsWith('logo_classe_'));
			if (logo_classe_classes.length == 1) {
				iconNumber = Number(logo_classe_classes[0].split("logo_classe_")[1])
			}
			return false;
		}
	});
	return iconNumber || null;
}
