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
    document.querySelector(".forum-liste").querySelectorAll('.forum-card').forEach(mesurerLireLaSuite);
}

/**
 * Affiche "Lire la suite" si le texte du commentaire dépasse les 5 lignes
 * du mode replié. Mesuré dans ce même style (classe mesure-hauteur, cf
 * forum.css.html) plutôt qu'en retirant redimmensionne, qui change la
 * taille de police et les marges des paragraphes. Une carte masquée (réponses
 * repliées) mesure 0 : elle est mesurée à l'ouverture de ses réponses
 * (cf callbackCliqueSurVoirLesReponses).
 */
function mesurerLireLaSuite(card) {
    const contenu = card.querySelector('.contenu-texte');
    const bouton = card.querySelector('.lire-la-suite');
    if (!contenu || !bouton || !contenu.getClientRects().length) {
        return;
    }
    contenu.classList.add('mesure-hauteur');
    const hauteurComplete = contenu.scrollHeight;
    contenu.classList.remove('mesure-hauteur');
    const hauteurVisible = contenu.clientHeight;
    // Vérifie si le texte dépasse la limite
    if (hauteurComplete > hauteurVisible) {
        bouton.removeAttribute("hidden")
    }
}

function callbackCliqueSurVoirLesReponses(e) {
     const bouton = e.currentTarget;

    if (!bouton) {
        return;
    }
    const commentaire = bouton.closest('.forum-commentaire');
    if (commentaire.classList.toggle("is-opened")) {
        commentaire.querySelectorAll('.reponse-list .forum-card').forEach(mesurerLireLaSuite);
    }
}

/**
 * Déplace le formulaire caché en dessous du message auquel on veut répondre.
 */
function callbackCliqueSurRepondreAuCommentaire(e) {
    const bouton = e.currentTarget
    const formulaireMobile = trouverFormulaireMobile()
    const zone = bouton
        .closest('.forum-commentaire')
        ?.querySelector(':scope > .forum-formulaire-reponse-zone')
    if (!formulaireMobile || !zone) return

    formulaireMobile.querySelector('.js-forum-id-parent').value = bouton.dataset.idForum
    formulaireMobile.querySelector('.id-forum').value = 0
    remplirTexte(formulaireMobile, '')

    zone.appendChild(formulaireMobile)
    formulaireMobile.hidden = false

    document.querySelectorAll('.forum-liste .forum-commentaire-container')
        .forEach(carte => { carte.hidden = false })
}

function trouverFormulaireMobile() {
    return document.getElementById('forum-formulaire-reponse')
}

// Pas de textarea quand le formulaire est en mode prévisualisation.
function remplirTexte(conteneur, texte) {
    const textarea = conteneur.querySelector('textarea')
    if (!textarea) return
    textarea.value = texte
    ajusterHauteurRedaction(textarea)
}

/**
 * La zone de rédaction grandit avec le texte, sans descendre sous ses
 * 6 lignes (rows) ni dépasser 60 % de la fenêtre (au-delà, elle défile).
 * height = auto d'abord pour revenir à la hauteur des rows et que
 * scrollHeight mesure le contenu, pas la hauteur courante.
 */
function ajusterHauteurRedaction(textarea) {
    textarea.style.height = 'auto'
    // Masquée (formulaire Répondre/Modifier pas encore affiché) : scrollHeight
    // vaut 0, on garde la hauteur des rows jusqu'à la prochaine saisie.
    if (!textarea.getClientRects().length) return
    const max = Math.round(window.innerHeight * 0.6)
    textarea.style.height = Math.min(textarea.scrollHeight, max) + 'px'
    textarea.style.overflowY = textarea.scrollHeight > max ? 'auto' : 'hidden'
}

// Délégué sur document : le formulaire arrive dans une popup chargée en ajax.
document.addEventListener('input', e => {
    if (e.target.matches?.('.forum-redaction__textarea')) {
        ajusterHauteurRedaction(e.target)
    }
})

function callbackCliqueSurModifierMonCommentaire(e) {
    const bouton = e.currentTarget
    const formulaireContainer = bouton.closest('.forum-commentaire-container')
    const formulaireMobile = trouverFormulaireMobile()
    if (!formulaireContainer || !formulaireMobile) return

    const { idForum, texteBrut = '' } = bouton.dataset

    formulaireMobile.querySelector('.js-forum-id-parent').value = ''
    formulaireMobile.querySelector('.id-forum').value = idForum
    formulaireContainer.hidden = true
    formulaireContainer.before(formulaireMobile)
    formulaireMobile.hidden = false
    remplirTexte(formulaireMobile, texteBrut)
}

function callbackCliqueSurCommenter(e) {
    const racineFormulaire = e.currentTarget.closest(".forum-article")
    const formulaireDePublication = racineFormulaire.querySelector(".forum-commentaire-racine")
    const encadre = racineFormulaire.querySelector(".encadre-message")
    encadre.hidden = true 
    formulaireDePublication.removeAttribute("hidden");
}

function callbackCliqueSurAnnuler(e) {
    const bouton = e.currentTarget
    const formulaireRacine = bouton.closest(".forum-commentaire-racine")
    if (formulaireRacine) {
        const encadre = bouton.closest(".forum-article")?.querySelector(".encadre-message")
        if (encadre) encadre.hidden = false
        formulaireRacine.hidden = true
        remplirTexte(formulaireRacine, "")
        return
    }

    // Formulaire partagé « Répondre » / « Modifier » : id-forum > 0 = modification,
    // la carte masquée est juste après lui (cf callbackCliqueSurModifierMonCommentaire).
    const formulaireMobile = trouverFormulaireMobile()
    if (!formulaireMobile) return
    remplirTexte(formulaireMobile, "")
    if (Number(formulaireMobile.querySelector(".id-forum")?.value) > 0) {
        const carte = formulaireMobile.nextElementSibling
        if (carte?.matches(".forum-commentaire-container")) carte.hidden = false
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
	const bouton = e.currentTarget;
	// cf appelerActionBouton() dans controleurs.js
	const ok = await appelerActionBouton(bouton, {
		confirmation: CCN.lang.message_avant_supression_commentaire,
		echec: CCN.lang.echec_de_la_supression,
	});
	if (ok) {
		bouton.closest('.forum-commentaire')?.remove();
	}
}
