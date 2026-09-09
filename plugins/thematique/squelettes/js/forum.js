function initCommentaires() {
    $('#mission-tabs').on(
        'customtabs:activated',
        function handler(e, data) {

            if (data.tabId !== 'commentaires') {
                return;
            }

            initForum();

            $(this).off('customtabs:activated', handler);
        }
    );
}

function initForum() {
    document.querySelector(".forum-liste").querySelectorAll('.forum-card').forEach(card => {
        const contenu = card.querySelector('.contenu-texte');
        contenu.classList.remove('redimmensionne');
        const hauteurComplete = contenu.scrollHeight;
        contenu.classList.add('redimmensionne');
        const hauteurVisible = contenu.clientHeight;
        const bouton = card.querySelector('.lire-la-suite');
        // Vérifie si le texte dépasse la limite
        if (hauteurComplete > hauteurVisible) {
            bouton.removeAttribute("hidden")
        }
    });
}

function callbackCliqueSurVoirLesReponses(e) {
     const bouton = e.currentTarget;

    if (!bouton) {
        return;
    }
    const commentaire = bouton.closest('.forum-commentaire');
    commentaire.classList.toggle("is-opened")
}

/**
 * Déplace le formulaire caché en dessous du message auquel on veut répondre.
 */
function callbackCliqueSurRepondreAuCommentaire(e) {
	const bouton = e.target;
	if (!bouton) return;

	const idForum = bouton.dataset.idForum;
	const formulaireMobile = document.querySelector('#forum-formulaire-reponse');

	formulaireMobile.querySelector('.js-forum-id-parent').value = idForum;
	formulaireMobile.querySelector('.id-forum').value = 0;
	formulaireMobile.querySelector('.forum-redaction__textarea').value = ''; // on repart d'un textarea vide

	const formulaireDePublication = bouton.closest('.forum-commentaire');
	formulaireDePublication
		.querySelector(':scope > .forum-formulaire-reponse-zone')
		.appendChild(formulaireMobile);
	formulaireMobile.removeAttribute("hidden");
    const rootForum = document.querySelector(".forum-liste")
    rootForum.querySelectorAll(".forum-commentaire-container").forEach(cardRoot=>{
        cardRoot.removeAttribute("hidden");
    })
}

function callbackCliqueSurModifierMonCommentaire(e) {
	const bouton = e.target;
	if (!bouton) return;

	const idForum = bouton.dataset.idForum;
	const texteBrut = bouton.dataset.texteBrut ?? '';
	const formulaireMobile = document.querySelector('#forum-formulaire-reponse');

	formulaireMobile.querySelector('.js-forum-id-parent').value = '';
	formulaireMobile.querySelector('.id-forum').value = idForum;
	formulaireMobile.querySelector('.forum-redaction__textarea').value = texteBrut; // <- le fix

    const formulaireContainer = bouton.closest(".forum-commentaire-container")
    formulaireContainer.hidden = true;

    formulaireContainer.parentNode.insertBefore(formulaireMobile, formulaireContainer)
	formulaireMobile.removeAttribute("hidden");
}

function callbackCliqueSurCommenter(e) {
    const racineFormulaire = e.currentTarget.closest(".forum-article")
    const formulaireDePublication = racineFormulaire.querySelector(".forum-commentaire-racine")
    const encadre = racineFormulaire.querySelector(".encadre-message")
    encadre.hidden = true 
    formulaireDePublication.removeAttribute("hidden");
}

function callbackCliqueSurAnnuler(e) {
    const formulaireRacine = e.currentTarget.closest(".forum-commentaire-racine")
    if (formulaireRacine) { // Cas formulaire de publication racine
        const racinePage = e.currentTarget.closest(".forum-article")
        const encadre = racinePage.querySelector(".encadre-message")
        encadre.removeAttribute("hidden");
        formulaireRacine.hidden = true
        const textarea = formulaireRacine.querySelector("textarea#texte")
        if (textarea) {
            textarea.value = ""
        }
        return
    }

    // Formulaire mobile partagé (#forum-formulaire-reponse), utilisé aussi
    // bien pour répondre à un commentaire que pour modifier le sien : on ne
    // peut pas distinguer les deux cas via la position dans le DOM (une
    // réponse imbriquée déplace ce formulaire à l'intérieur d'un ancêtre
    // .forum-commentaire, comme le ferait aussi une modification), donc on
    // se base sur le champ id-forum du formulaire lui-même.
    const formulaireMobile = document.getElementById("forum-formulaire-reponse")
    const champIdForum = formulaireMobile.querySelector(".id-forum")
    const cestUneModification = !!champIdForum && champIdForum.value != 0
    const textarea = formulaireMobile.querySelector("textarea#texte")
    if (textarea) {
        textarea.value = ""
    }
    if (cestUneModification) {
        const cardToDisplay = formulaireMobile.nextElementSibling
        cardToDisplay.removeAttribute("hidden");
    }
    formulaireMobile.hidden = true
}

function callback_clique_sur_lire_la_suite_du_commentaire(e) {
    const racineCard = e.currentTarget.closest('.forum-card');
    const contenu = racineCard.querySelector(".content")
    const ouvert = contenu.classList.toggle('ouvert');
    e.currentTarget.textContent = ouvert
    ? CCN.lang.forum_reduire
    : CCN.lang.lire_la_suite_commentaire;
}

async function callbackCliqueSurSupprimerMonCommentaire(e) {
	const bouton = e.target;
	if (!bouton) return;

	if (!window.confirm(CCN.lang.message_avant_supression_commentaire)) {
		return;
	}

	const url = bouton.dataset.url;
	const conteneurCommentaire = bouton.closest('.forum-commentaire');

	try {
		const reponse = await fetch(url, { method: 'GET', credentials: 'same-origin' });
		if (!reponse.ok) {
			throw new Error(CCN.lang.echec_de_la_supression);
		}
		conteneurCommentaire.remove();
	} catch (erreur) {
		window.alert(CCN.lang.echec_de_la_supression);
	}
}
