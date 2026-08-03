////////////////////////////////////////////////////////////////
// Fonctions chargées dans le layout (différentes des fonctions du projet)
////////////////////////////////////////////////////////////////

(function(){
	if (typeof window !== 'undefined') {
		window.ajaxbloc_selecteur = window.ajaxbloc_selecteur || '.pagination a, a.ajax, a.lienss, .titre_article a';
	}
})();

/**
 * Affiche une boîte de confirmation native du navigateur.
 *
 * @param {string} txt - Message à afficher
 * @returns {boolean} `true` si l'utilisateur a confirmé, `false` sinon
 */
function confirmation(txt) {
	return window.confirm(txt);
}
