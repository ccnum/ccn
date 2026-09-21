function reload_cookie(url, cookie_nom, cookie_valeur) {
	// #440 : le select n'offre plus que des années scolaires réellement
	// existantes (cf footer.html) - plus besoin de rediriger les anciennes
	// années vers airchive.laclasse.com, qui n'existe plus (404).
	document.cookie = cookie_nom + "=" + escape(cookie_valeur);
	url = url + '/?annee_scolaire=' + cookie_valeur;
	reload(url);
}

function reload(url) {
	if (url == 'self') {
		location.reload(true);
		window.location.reload();
	} else {
		window.location.href = url;
	}
}
