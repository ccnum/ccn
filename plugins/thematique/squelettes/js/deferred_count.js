/**
 * Affiche dans un élément déjà présent dans la page un total calculé plus
 * loin dans le squelette (ex: nombre de classes, nombre de réponses à un
 * message de forum), pour éviter de relancer une deuxième fois les boucles
 * SPIP correspondantes uniquement pour compter leurs résultats.
 *
 * @param {string} elementId - id de l'élément cible
 * @param {string|number} value - valeur à afficher
 */
function thematiqueApplyDeferredCount(elementId, value) {
	const el = document.getElementById(elementId);
	if (el) {
		el.textContent = value;
	}
}
