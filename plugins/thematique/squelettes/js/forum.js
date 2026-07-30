function initCommentaires() {
    console.log("initCommentaires");
    
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
     console.log("initForum");
    document.querySelector(".forum-liste").querySelectorAll('.forum-card').forEach(card => {
        const contenu = card.querySelector('.contenu-texte');
        contenu.classList.remove('redimmensionne');
        const hauteurComplete = contenu.scrollHeight;
        contenu.classList.add('redimmensionne');
        const hauteurVisible = contenu.clientHeight;
        console.log(hauteurComplete, hauteurVisible);
        
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
        bouton.innerText = "Voir les réponses"
    } else {
        reponses.style.display = "flex";
        bouton.innerText = "Masquer les réponses"
    }
}

/**
 * Déplace le formulaire caché en dessous du message auquel on veut répondre.
 */
function callbackCliqueSurRepondreAuCommentaire(e) {
    console.log({e});
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
        .querySelector('.forum-formulaire-reponse-zone')
        .appendChild(formulaireCache);
    console.log({formulaireDePublication});
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
    console.log("annuler");
    const racineFormulaire = e.currentTarget.closest(".forum-commentaire")
    const champParent = racineFormulaire.querySelector(".js-forum-id-parent")
    console.log(champParent.value);
    let formulaire
    if(champParent.value == 0) {
        const racinePage = e.currentTarget.closest(".forum-article")
        formulaire = racinePage.querySelector(".forum-commentaire-racine")
        const encadre = racinePage.querySelector(".encadre-message")
        encadre.style.display = "flex"  
    } else {
        formulaire = document.querySelector('#forum-formulaire-reponse');
    }
    formulaire.hidden = true
}

function callback_clique_sur_lire_la_suite_du_commentaire(e) {
    const racineCard = e.currentTarget.closest('.forum-card');
    const contenu = racineCard.querySelector(".content")
    const ouvert = contenu.classList.toggle('ouvert');
    e.currentTarget.textContent = ouvert
    ? 'Réduire'
    : 'Lire la suite du commentaire';
}
