let CCN = {};

CCN.debug = false;
CCN.nomMois = ["Janv.", "Fév.", "Mars", "Avril", "Mai", "Juin", "Juil.", "Août", "Sept.", "Oct.", "Nov.", "Déc."];
CCN.nomCompletMois = ["janvier", "février", "mars", "avril", "mai", "juin", "juillet", "août", "septembre", "octobre", "novembre", "décembre"];
CCN.travailEnCoursId;
CCN.couleurBlog;
CCN.dureeTransition;

CCN.projet;
CCN.classes;
CCN.intervenants;
CCN.consignes;
CCN.reponses;
CCN.articlesBlog;         // blogs        : Blog du projet    (accessible par tous, bulles roses)
CCN.articlesEvenement;    // evenements   : Blog pédagogique  (caché aux élèves, losanges bleu ciel)

CCN.timelineLayerConsignes;
CCN.timelineLayerBlogs;
CCN.timelineLayerEvenements;

const CLASS_ICONS = ['🦋', '🦓', '🦊', '🐟', '🐱', '🦌', '🐝', '🦉', '🐜', '🦔'];

function getClassIcon(index) {
    return CLASS_ICONS[index % CLASS_ICONS.length];
}

function hexToR(h) {
	return parseInt((cutHex(h)).substring(0, 2), 16)
}
function hexToG(h) {
	return parseInt((cutHex(h)).substring(2, 4), 16)
}
function hexToB(h) {
	return parseInt((cutHex(h)).substring(4, 6), 16)
}
function cutHex(h) {
	return (h.charAt(0) === "#") ? h.substring(1, 7) : h
}

function escHtml(s) {
	return String(s)
		.replace(/&/g, '&amp;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;')
		.replace(/"/g, '&quot;')
		.replace(/'/g, '&#x27;');
}

function decodeHtmlEntities(str) {
	const el = document.createElement('textarea');
	el.innerHTML = str;
	return el.value;
}

/**
 *  Retourne la valeur du noeud XML demandé.
 *
 * @param   {string} tagName - Nom du noeud demandé
 * @param   xml - Ressource XML
 * @returns {string} Valeur du noeud demandé
 */

function getXMLNodeValue(tagName, xml) {
	const node = xml.getElementsByTagName(tagName)[0];
	return node && node.childNodes[0] ? node.childNodes[0].nodeValue : null;
}

/**
 * Retourne vrai si le noeud existe.
 *
 * @param   {string} tagName - Nom du noeud demandé
 * @param   xml - Ressource XML
 * @returns {boolean} <tt>true</tt> si le noeuf existe, <tt>false</tt> sinon
 */

function hasXMLNodeValue(tagName, xml) {
	const node = xml.getElementsByTagName(tagName)[0];
	return node ? node.childNodes[0] : null;
}

/**
 * Retourne un tableau des paramètres d'une URL passée en paramètre string
 * Voir : http://stackoverflow.com/questions/8486099/how-do-i-parse-a-url-query-parameters-in-javascript
 */
function getJsonFromUrl(query) {
	const result = {};

	query = query.substring(query.indexOf("?") + 1);
	query.split("&").forEach(
		function (part) {
			if (!part) {
				return;
			}
			part = part.split("+").join(" "); // replace every + with space, regexp-free version
			const eq = part.indexOf("=");
			let key = eq > -1 ? part.slice(0, eq) : part;
			const val = eq > -1 ? decodeURIComponent(part.slice(eq + 1)) : "";
			const from = key.indexOf("[");
			if (from === -1) {
				result[decodeURIComponent(key)] = val;
			} else {
				const to = key.indexOf("]");
				const index = decodeURIComponent(key.substring(from + 1, to));
				key = decodeURIComponent(key.substring(0, from));
				if (!result[key]) {
					result[key] = [];
				}
				if (!index) {
					result[key].push(val);
				} else {
					result[key][index] = val;
				}
			}
		}
	);
	return result;
}

/**
 * Parse une date au format JJ/MM/AAAA en objet Date.
 *
 * @param {string} str - Date au format "JJ/MM/AAAA"
 * @returns {Date}
 */
function parseDate(str) {
	const [day, month, year] = str.split('/').map(Number);
	return new Date(year, month - 1, day);
}