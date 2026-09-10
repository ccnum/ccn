/**
 * Formulaire de publication d'un article, affiché dans la sidebar
 * principale avec ses 2 blocs (rédaction / document) visibles simultanément
 * (cf editor.css.html). Fonctions appelées directement depuis les attributs
 * onclick du squelette du formulaire.
 */

/**
 * Branche le compteur de caractères sur le champ associé au label
 * ".nb-caracteres" (via son attribut "for"), et ajoute la classe "warn"
 * sur ".compteur-caracteres" au-delà de 50 caractères.
 */
function initCompteurCaracteres() {
    const nbCaracteresElement = document.querySelector(".nb-caracteres")
    if (!nbCaracteresElement) return
    const inputId = nbCaracteresElement.getAttribute("for")
    const inputElement = document.getElementById(inputId)
    const compteurRoot = document.querySelector(".compteur-caracteres")
    if (!inputElement || !compteurRoot) return
    // Longueur initiale du champ et pas 0 en dur : cas d'une réponse à une
    // consigne déjà rédigée, rechargée avec son titre pré-rempli (cf
    // formulaires_public_publier_article_charger_dist).
    nbCaracteresElement.innerText = inputElement.value.length
    inputElement.addEventListener("input", e=>{
        const length = inputElement.value.length
        nbCaracteresElement.innerText = length
        if(length > 50) {
            if(!compteurRoot.classList.contains("warn")) {
                compteurRoot.classList.add("warn")
            }
        } else {
            compteurRoot.classList.remove("warn")
        }
    })
}

/**
 * Soumet le formulaire "#formulaire_publier_article".
 */
function creationArticleEnregistrer() {
    const formulaire = document.getElementById("formulaire_publier_article")
    formulaire.requestSubmit();
}

/**
 * Copie dans le presse-papier le raccourci SPIP (<docXX>/<imgXX>) d'un
 * document listé dans sidebar-etape-2-container (cf
 * noisettes/inc/publier_article_documents.html), pour le coller dans le
 * champ "texte". Affiche brièvement la classe "copie" sur l'élément cliqué
 * en retour visuel.
 */
function copierRaccourciDocument(element) {
    const raccourci = element.textContent
    navigator.clipboard.writeText(raccourci).then(() => {
        element.classList.add("copie")
        setTimeout(() => element.classList.remove("copie"), 1000)
    })
}