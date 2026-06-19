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
 * Parse une date au format ISO AAAA-MM-JJ en objet Date (heure locale).
 *
 * @param {string} str - Date au format "AAAA-MM-JJ"
 * @returns {Date}
 */
function parseDate(str) {
	const [year, month, day] = str.split('-').map(Number);
	return new Date(year, month - 1, day);
}

function formatDateCourte(dateStr) {
	const d = parseDate(dateStr);
	return d.getDate() + " " + CCN.nomMois[d.getMonth()];
}

function formatDateLongue(dateStr) {
	const d = parseDate(dateStr);
	return d.getDate() + " " + CCN.nomMois[d.getMonth()] + " " + d.getFullYear();
}