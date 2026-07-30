


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
    console.log({idForum});
    
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
    formulaireDePublication.style.display = "block"
}

function callbackCliqueSurAnnuler(e) {
    const racineFormulaire = e.currentTarget.closest(".forum-article")
    const formulaireDePublication = racineFormulaire.querySelector(".forum-commentaire-racine")
    const encadre = racineFormulaire.querySelector(".encadre-message")
    encadre.style.display = "flex" 
    formulaireDePublication.style.display = "none"
}
