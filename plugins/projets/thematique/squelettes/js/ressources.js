function voirLaRessource(e) {
    const element = e.currentTarget
    const id_article = element.dataset.idArticle
    selectionnerRessource(id_article)
}

/**
 * Filtre les cartes de la liste des ressources par sous-rubrique.
 * "Tout" (data-id-rubrique="") réaffiche tout le contenu de l'arbre
 * "Ressources", y compris les ressources publiées à la racine (donc sans
 * sous-rubrique) - cf #299.
 *
 * @param {HTMLElement} bouton - Le bouton de filtre cliqué (.filtre-ressources)
 */
function filtrerRessources(bouton) {
    const idRubrique = bouton.dataset.idRubrique;

    document.querySelectorAll('.filtre-ressources').forEach(b => {
        b.classList.toggle('actif', b === bouton);
    });

    document.querySelectorAll('.ressource-card-container').forEach(carte => {
        const correspond = !idRubrique || carte.dataset.idRubrique === idRubrique;
        carte.style.display = correspond ? '' : 'none';
    });
}

function selectionnerRessource(id_article) {
    loadArticleInLateralSidebar(id_article);
    deselectionnerTout()
    const card = document.querySelector(
        `.ressource-card-container[data-id-article="${id_article}"]`
    );

    card?.classList.add("selected");

    updateUrl(
        null,
        "",
        setInUrl({ id_article })
    );

    replaceInCurrentState({ id_objet: id_article });
}

function deselectionnerTout() {
    document.querySelectorAll(".ressource-card-container").forEach(card => {
        card.classList.remove("selected");
    });
}

function fermerUneRessource() {
    deselectionnerTout()
}