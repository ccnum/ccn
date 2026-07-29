


function callbackCliqueSurVoirLesReponses(e) {
     const bouton = e.currentTarget;

    if (!bouton) {
        return;
    }
    const commentaire = bouton.closest('.forum-commentaire');
    const reponses = commentaire.querySelector('.forum-reponses');
    reponses.hidden = !reponses.hidden;
}

function callbackCliqueSurRepondreAuCommentaire(e) {
    console.log({e});
    
    const bouton = e.target
    if (!bouton) {
        return;
    }


    const idForum = bouton.dataset.idForum;
    console.log({idForum});
    
    const formulaire = document.querySelector(
        '#forum-formulaire-reponse'
    );

    const champParent = formulaire.querySelector(
        '.js-forum-id-parent'
    );

    champParent.value = idForum;


    const formulaireDePublication = bouton.closest('.forum-commentaire');

    formulaireDePublication
        .querySelector('.forum-formulaire-reponse-zone')
        .after(formulaire);


    console.log({formulaireDePublication});
    
    formulaireDePublication.hidden = false;
}

