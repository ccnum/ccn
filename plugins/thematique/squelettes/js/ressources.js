function voirLaRessource(e) {
    const element = e.currentTarget
    const id_article = element.dataset.idArticle
    selectionnerRessource(id_article)
}

function selectionnerRessource(id_article) {
    loadArticleInLateralSidebar(id_article);

    document.querySelectorAll(".ressource-card-container").forEach(card => {
        card.classList.remove("selected");
    });

    const card = document.querySelector(
        `.ressource-card-container [data-id-article="${id_article}"]`
    )?.closest(".ressource-card-container");

    card?.classList.add("selected");

    updateUrl(
        null,
        "",
        setInUrl({ id_article })
    );

    replaceInCurrentState({ id_objet: id_article });
}