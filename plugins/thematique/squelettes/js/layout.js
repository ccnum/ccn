////////////////////////////////////////////////////////////////
// Fonctions chargées dans le layout (différentes des fonctions du projet)
////////////////////////////////////////////////////////////////

(function(){
	if (typeof window !== 'undefined') {
		window.ajaxbloc_selecteur = window.ajaxbloc_selecteur || '.pagination a, a.ajax, a.lienss, .titre_article a';
	}
})();

function confirmation(txt) {
	return window.confirm(txt);
}
