/**
 * Ouvre directement une consigne dans la sidebar principale quand la page
 * est atteinte depuis un lien de notification par email
 * (paramètres d'URL "id_article" et "depuis=email").
 *
 * @see callConsigne
 */
document.addEventListener("DOMContentLoaded", function() {
	const params = new URLSearchParams(window.location.search);
	const id_consigne = params.get("id_article");
	const depuis = params.get("depuis");

	// On n'agit que si le mode est "email"
	if (depuis === "email") {
		callConsigne(id_consigne);
	}
});
