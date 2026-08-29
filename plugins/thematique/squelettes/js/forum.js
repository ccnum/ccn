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
    const racineFormulaire = e.currentTarget.closest(".forum-commentaire-racine, .forum-commentaire")
    const cestLeFormulaireRepondre = !!racineFormulaire
    if(cestLeFormulaireRepondre) {
        const champParent = racineFormulaire.querySelector(".js-forum-id-parent")
        let formulaire
        if(champParent.value == 0) {  // Cas formulaire de publication racine
            const racinePage = e.currentTarget.closest(".forum-article")
            formulaire = racinePage.querySelector(".forum-commentaire-racine")
            const encadre = racinePage.querySelector(".encadre-message")
            encadre.removeAttribute("hidden");
        } else {  // Cas formulaire de réponse à une réponse
            formulaire = document.querySelector('#forum-formulaire-reponse');
        }
        formulaire.hidden = true
        const textarea = racineFormulaire.querySelector("textarea#texte")
        if (textarea) {
            textarea.value = ""
        }
    } else { // C'est le formulaire modifier
        const formulaireCourant = document.getElementById("forum-formulaire-reponse")
        const cardToDisplay = formulaireCourant.nextElementSibling
        cardToDisplay.removeAttribute("hidden");
        formulaireCourant.hidden = true
    }
    
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
