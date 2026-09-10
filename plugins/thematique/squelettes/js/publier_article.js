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

/**
 * Bascule l'alignement (left/center/right) associé au raccourci d'un
 * document listé dans sidebar-etape-2-container (cf .raccourci-ligne dans
 * noisettes/inc/publier_article_documents.html), en mettant à jour le
 * texte du raccourci affiché (<docXX|left> etc., syntaxe des raccourcis
 * SPIP) — c'est ce texte que copierRaccourciDocument() copie ensuite.
 * Recliquer sur le bouton déjà actif retire l'alignement.
 */
function basculerAlignementDocument(bouton) {
    const ligne = bouton.closest(".raccourci-ligne")
    const raccourciElement = ligne && ligne.querySelector(".raccourci")
    if (!raccourciElement) return

    const dejaSelectionne = bouton.classList.contains("selected")
    ligne.querySelectorAll(".lien-alignement").forEach(b => b.classList.remove("selected"))

    const { prefixe, idDocument } = raccourciElement.dataset
    if (dejaSelectionne) {
        raccourciElement.textContent = `<${prefixe}${idDocument}>`
    } else {
        bouton.classList.add("selected")
        raccourciElement.textContent = `<${prefixe}${idDocument}|${bouton.dataset.align}>`
    }
}

/**
 * Soumet automatiquement le formulaire d'ajout de document
 * (formulaires/joindre_document_mission.html, sidebar-etape-2-container)
 * une fois l'upload bigup terminé.
 *
 * Ce formulaire n'a pas de bouton "Envoyer" visible (masqué en CSS, cf
 * .formulaire_joindre_document .boutons dans editor.css.html) : sans ce
 * déclenchement, rien ne le soumet jamais. Le fichier reste alors "en
 * attente" côté bigup (chunks envoyés, prévisualisation affichée avec son
 * bouton "Enlever") sans jamais être réellement associé à l'article —
 * formulaires_joindre_document_mission_traiter_dist() (qui crée le document
 * SPIP et déclenche le rechargement ajax de sidebar-etape-2-container, cf
 * noisettes/inc/publier_article_documents.html) n'est jamais appelé.
 *
 * "bigup.complete" (cf plugins-dist/bigup/javascript/bigup.js) est déclenché
 * sur le champ <input type=file>, avec le nombre de fichiers envoyés dans
 * cette salve ; on ignore l'évènement à 0 fichier (déclenché aussi par
 * flow.js quand la file d'attente est vide, ex. juste après un "Enlever").
 *
 * Délégué sur document plutôt que bindé au chargement du popup : le champ
 * n'existe pas au chargement initial de la page et est réinjecté à chaque
 * ouverture du popup "Publier une mission" (cf loadContentInMainSidebar
 * dans controleurs.js) — la délégation évite d'avoir à répéter ce binding à
 * chaque réouverture.
 */
jQuery(document).on("bigup.complete", ".formulaire_joindre_document input.bigup", function (event, data) {
    if (!data || !data.count) return
    const formulaire = this.closest("form")
    if (formulaire) formulaire.requestSubmit()
})