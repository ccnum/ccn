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
            bouton.style.display = '';
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
	formulaireMobile.hidden = false;
}

function callbackCliqueSurModifierMonCommentaire(e) {
    console.log("modifier");
    
	const bouton = e.target;
	if (!bouton) return;

	const idForum = bouton.dataset.idForum;
	const texteBrut = bouton.dataset.texteBrut ?? '';
	const formulaireMobile = document.querySelector('#forum-formulaire-reponse');

	formulaireMobile.querySelector('.js-forum-id-parent').value = '';
	formulaireMobile.querySelector('.id-forum').value = idForum;
	formulaireMobile.querySelector('.forum-redaction__textarea').value = texteBrut; // <- le fix

	const formulaireDePublication = bouton.closest('.forum-commentaire');
	formulaireDePublication
		.querySelector(':scope > .forum-formulaire-reponse-zone')
		.appendChild(formulaireMobile);
	formulaireMobile.hidden = false;
}

function callbackCliqueSurCommenter(e) {
    const racineFormulaire = e.currentTarget.closest(".forum-article")
    const formulaireDePublication = racineFormulaire.querySelector(".forum-commentaire-racine")
    const encadre = racineFormulaire.querySelector(".encadre-message")
    encadre.style.display = "none" 
    formulaireDePublication.hidden = false
}

function callbackCliqueSurAnnuler(e) {
    const racineFormulaire = e.currentTarget.closest(".forum-commentaire-racine, .forum-commentaire")
    const champParent = racineFormulaire.querySelector(".js-forum-id-parent")
    let formulaire
    if(champParent.value == 0) {  // Cas formulaire de publication racine
        const racinePage = e.currentTarget.closest(".forum-article")
        formulaire = racinePage.querySelector(".forum-commentaire-racine")
        const encadre = racinePage.querySelector(".encadre-message")
        encadre.style.display = "flex"  
    } else {  // Cas formulaire de réponse à une réponse
        formulaire = document.querySelector('#forum-formulaire-reponse');
    }
    formulaire.hidden = true
    const textarea = racineFormulaire.querySelector("textarea#texte")
    if (textarea) {
        textarea.value = ""
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

	if (!window.confirm('Supprimer ce commentaire ?')) {
		return;
	}

	const url = bouton.dataset.url;
	const conteneurCommentaire = bouton.closest('.forum-commentaire');

	try {
		const reponse = await fetch(url, { method: 'GET', credentials: 'same-origin' });
		if (!reponse.ok) {
			throw new Error('Échec de la suppression');
		}
		conteneurCommentaire.remove();
	} catch (erreur) {
		window.alert('La suppression a échoué.');
	}
}
