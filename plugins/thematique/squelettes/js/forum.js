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
    const reponses = commentaire.querySelector('.reponse-list');
    if(reponses.style.display == "flex") {
        reponses.style.display = "none";
        bouton.innerText = CCN.lang.forum_voir_reponses
    } else {
        reponses.style.display = "flex";
        bouton.innerText = CCN.lang.forum_masquer_reponses
    }
}

/**
 * Déplace le formulaire caché en dessous du message auquel on veut répondre.
 */
function callbackCliqueSurRepondreAuCommentaire(e) {
    const bouton = e.target
    if (!bouton) {
        return;
    }
    const idForum = bouton.dataset.idForum;
    const formulaireCache = document.querySelector(
        '#forum-formulaire-reponse'
    );
    const champParent = formulaireCache.querySelector(
        '.js-forum-id-parent'
    );
    champParent.value = idForum;
    const formulaireDePublication = bouton.closest('.forum-commentaire');
    formulaireDePublication
        .querySelector(':scope > .forum-formulaire-reponse-zone')
        .appendChild(formulaireCache);
    formulaireCache.hidden = false;
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
