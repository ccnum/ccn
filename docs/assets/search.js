/* Recherche dans la documentation, sans index à générer : au premier usage du
   champ, les pages listées dans la barre latérale sont téléchargées et découpées
   en sections (un h2 ou h3 = une section, ancre = id du titre). La recherche
   ignore accents et majuscules ; tous les mots doivent être présents. */
(function () {
	'use strict';

	var MAX_RESULTATS = 20;
	var EXTRAIT = 70; // caractères de contexte de part et d'autre du mot trouvé

	var input, panneau, nav;
	var sections = null, chargement = null;
	var resultats = [], actif = -1;

	// Accents retirés sans changer la longueur (texte NFC) : les positions trouvées
	// dans le texte normalisé restent valables dans le texte d'origine.
	function normaliser(t) {
		return t.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
	}

	function echapper(t) {
		return t.replace(/[&<>"]/g, function (c) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
		});
	}

	function texte(el) {
		return el.textContent.replace(/\s+/g, ' ').trim().normalize('NFC');
	}

	function indexerPage(page, doc) {
		var main = doc.querySelector('main');
		if (!main) return [];
		var h1 = main.querySelector('h1');
		var liste = [];
		var courante = { page: page.titre, url: page.url, titre: h1 ? texte(h1) : page.titre, morceaux: [] };
		liste.push(courante);
		main.querySelectorAll('h2, h3, p, li, td, th, pre, dt, dd, .callout').forEach(function (el) {
			if (el.closest('.page-footer')) return;
			if (el.tagName === 'H2' || el.tagName === 'H3') {
				courante = { page: page.titre, url: page.url + (el.id ? '#' + el.id : ''), titre: texte(el), morceaux: [] };
				liste.push(courante);
			} else if (!el.parentElement.closest('li, td, th, dd, .callout')) {
				// Les blocs imbriqués (p dans li, etc.) sont déjà dans le texte du parent.
				courante.morceaux.push(texte(el));
			}
		});
		return liste.map(function (s) {
			var t = s.morceaux.join(' ');
			return { page: s.page, url: s.url, titre: s.titre, texte: t, titreN: normaliser(s.titre), texteN: normaliser(t) };
		}).filter(function (s) { return s.texte || s.titre; });
	}

	function charger() {
		if (chargement) return chargement;
		var pages = Array.prototype.map.call(nav.querySelectorAll('a[href$=".html"]'), function (a) {
			return { url: a.getAttribute('href'), titre: texte(a) };
		});
		var parser = new DOMParser();
		chargement = Promise.all(pages.map(function (page) {
			return fetch(page.url)
				.then(function (r) { return r.ok ? r.text() : ''; })
				.then(function (html) { return html ? indexerPage(page, parser.parseFromString(html, 'text/html')) : []; })
				.catch(function () { return []; });
		})).then(function (listes) {
			sections = [].concat.apply([], listes);
		});
		return chargement;
	}

	function compter(texte, mot) {
		var n = 0, i = texte.indexOf(mot);
		while (i !== -1 && n < 10) { n++; i = texte.indexOf(mot, i + mot.length); }
		return n;
	}

	function surligner(t, mots) {
		var tN = normaliser(t), plages = [];
		mots.forEach(function (mot) {
			var i = tN.indexOf(mot);
			while (i !== -1) { plages.push([i, i + mot.length]); i = tN.indexOf(mot, i + mot.length); }
		});
		plages.sort(function (a, b) { return a[0] - b[0]; });
		var html = '', pos = 0;
		plages.forEach(function (p) {
			if (p[0] < pos) return;
			html += echapper(t.slice(pos, p[0])) + '<mark>' + echapper(t.slice(p[0], p[1])) + '</mark>';
			pos = p[1];
		});
		return html + echapper(t.slice(pos));
	}

	function extrait(s, mots) {
		var i = s.texteN.indexOf(mots[0]);
		if (i === -1) i = 0;
		var debut = Math.max(0, i - EXTRAIT), fin = Math.min(s.texte.length, i + mots[0].length + EXTRAIT);
		return (debut > 0 ? '… ' : '') + surligner(s.texte.slice(debut, fin), mots) + (fin < s.texte.length ? ' …' : '');
	}

	function chercher(requete) {
		var mots = normaliser(requete).split(/\s+/).filter(function (m) { return m.length > 1; });
		if (!mots.length) return { mots: mots, liste: [] };
		var liste = sections.map(function (s) {
			var score = 0;
			for (var k = 0; k < mots.length; k++) {
				var dansTitre = s.titreN.indexOf(mots[k]) !== -1;
				var n = compter(s.texteN, mots[k]);
				if (!dansTitre && !n) return null;
				score += (dansTitre ? 10 : 0) + n;
			}
			return { s: s, score: score };
		}).filter(Boolean);
		liste.sort(function (a, b) { return b.score - a.score; });
		return { mots: mots, liste: liste.slice(0, MAX_RESULTATS) };
	}

	function afficher() {
		var requete = input.value.trim();
		if (!requete) { fermer(); return; }
		var r = chercher(requete);
		resultats = r.liste;
		actif = -1;
		if (!resultats.length) {
			panneau.innerHTML = '<p class="search-vide">Aucun résultat pour « ' + echapper(requete) + ' ».</p>';
		} else {
			panneau.innerHTML = '<ul>' + resultats.map(function (x, i) {
				var s = x.s;
				return '<li><a href="' + echapper(s.url) + '" data-index="' + i + '">'
					+ '<span class="search-page">' + echapper(s.page) + '</span>'
					+ '<span class="search-titre">' + surligner(s.titre, r.mots) + '</span>'
					+ (s.texte ? '<span class="search-extrait">' + extrait(s, r.mots) + '</span>' : '')
					+ '</a></li>';
			}).join('') + '</ul>';
		}
		panneau.hidden = false;
		nav.hidden = true;
	}

	function fermer() {
		panneau.hidden = true;
		panneau.innerHTML = '';
		nav.hidden = false;
		resultats = [];
		actif = -1;
	}

	function selectionner(i) {
		var liens = panneau.querySelectorAll('a');
		if (!liens.length) return;
		actif = (i + liens.length) % liens.length;
		liens.forEach(function (a, k) { a.classList.toggle('actif', k === actif); });
		liens[actif].scrollIntoView({ block: 'nearest' });
	}

	function init() {
		input = document.getElementById('search-input');
		panneau = document.getElementById('search-results');
		nav = document.querySelector('.sidebar nav');
		if (!input || !panneau || !nav) return;

		input.addEventListener('focus', charger);
		input.addEventListener('input', function () { charger().then(afficher); });
		input.addEventListener('keydown', function (e) {
			if (e.key === 'ArrowDown') { e.preventDefault(); selectionner(actif + 1); }
			else if (e.key === 'ArrowUp') { e.preventDefault(); selectionner(actif - 1); }
			else if (e.key === 'Enter') {
				var liens = panneau.querySelectorAll('a');
				var cible = liens[actif] || liens[0];
				if (cible) { e.preventDefault(); window.location.href = cible.href; }
			} else if (e.key === 'Escape') { input.value = ''; fermer(); }
		});
		// Un lien vers une section de la page courante ne recharge pas la page :
		// on referme les résultats pour retrouver la navigation.
		panneau.addEventListener('click', function (e) {
			if (e.target.closest('a')) { input.value = ''; fermer(); }
		});
		// « / » place le curseur dans le champ, comme sur GitHub.
		document.addEventListener('keydown', function (e) {
			if (e.key === '/' && !/^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement.tagName)) {
				e.preventDefault();
				input.focus();
			}
		});
	}

	init();
})();
