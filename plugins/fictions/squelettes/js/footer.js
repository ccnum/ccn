function reload_cookie(url, cookie_nom, cookie_valeur) {
	if (cookie_valeur <= 2011) {
		url = 'http://airchive.laclasse.com/?annee_scolaire=' + cookie_valeur;
	}
	else {
		document.cookie = cookie_nom + "=" + escape(cookie_valeur);
		url = url + '/?annee_scolaire=' + cookie_valeur;
	}
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
