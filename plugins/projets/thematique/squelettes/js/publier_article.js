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
 * Désactive le bouton "Publier" dès l'envoi du formulaire
 * "#formulaire_publier_article", pour qu'un double clic ne crée pas deux
 * articles : le formulaire ajax SPIP (ajaxForm, cf
 * prive/javascript/ajaxCallback.js) renvoie un POST à chaque soumission, et
 * l'anti-doublon de formulaires_public_publier_article_traiter_dist() (clé
 * en session) ne voit pas une 2e requête partie avant la fin de la 1re.
 *
 * Délégué sur document : ajaxForm, bindé directement sur le formulaire, a
 * déjà sérialisé et envoyé la requête quand l'évènement arrive ici. Le
 * formulaire est réinjecté (bouton réactivé) au retour ajax ; réactivation
 * de secours au cas où la requête échoue sans rechargement.
 */
jQuery(document).on("submit", "#formulaire_publier_article", function () {
    const bouton = document.getElementById("bouton-enregistrer-article")
    if (!bouton) return
    bouton.disabled = true
    setTimeout(() => { bouton.disabled = false }, 10000)
})

/**
 * Insère le raccourci SPIP (<docXX>/<imgXX>) d'un document listé dans
 * sidebar-etape-2-container (cf noisettes/inc/publier_article_documents.html)
 * directement dans le champ "texte" de l'article - comme dans le BO SPIP, où
 * cliquer sur un document l'insère dans le texte plutôt que d'obliger un
 * copier-coller.
 *
 * Si ce raccourci est déjà présent dans le texte, on ne le réinsère pas une
 * deuxième fois (retour visuel "copie" sur l'élément cliqué pour signaler
 * qu'il est déjà là) ; sinon il est inséré à la position du curseur (ou
 * ajouté en fin de texte si le champ n'a pas le focus).
 */
function insererRaccourciDocument(element) {
    const texte = document.getElementById("texte");
    if (!texte || !element) return;
    const { prefixe, idDocument } = element.dataset;
    if (!prefixe || !idDocument) return;

    const raccourci = `<${prefixe}${idDocument}>`;

    if (!texte.value.includes(raccourci)) {
        const debut = texte.selectionStart ?? texte.value.length;
        const fin = texte.selectionEnd ?? texte.value.length;

        texte.value =
            texte.value.slice(0, debut) +
            raccourci +
            texte.value.slice(fin);

        const position = debut + raccourci.length;

        texte.setSelectionRange(position, position);
        texte.dispatchEvent(new Event("input", { bubbles: true }));
        texte.focus();
    }

    element.classList.add("copie");
    setTimeout(() => element.classList.remove("copie"), 1000);
}

/**
 * Passe le titre d'un document de la liste des pièces jointes en mode
 * édition (#497, cf formulaires/titrer_document.html) : clic sur le crayon
 * de la carte. Entrée ou sortie du champ avec un titre changé enregistre
 * (envoi ajax du formulaire, qui se recharge en mode affichage), Échap ou
 * sortie sans changement annule.
 */
function editerTitreDocument(bouton) {
    const fichier = bouton.closest(".fichier")
    const formulaire = fichier && fichier.querySelector(".formulaire_titrer_document")
    const saisie = formulaire && formulaire.querySelector(".titre-document-saisie")
    if (!saisie) return
    saisie.dataset.initial = saisie.value
    formulaire.classList.add("edition")
    saisie.focus()
    saisie.select()
}

function fermerEditionTitreDocument(saisie) {
    saisie.value = saisie.dataset.initial ?? saisie.value
    saisie.closest(".formulaire_titrer_document")?.classList.remove("edition")
}

jQuery(document)
    .on("keydown", ".titre-document-saisie", function (e) {
        if (e.key === "Escape") {
            e.preventDefault()
            fermerEditionTitreDocument(this)
        }
    })
    .on("submit", ".formulaire_titrer_document form", function () {
        const saisie = this.querySelector(".titre-document-saisie")
        if (saisie) saisie.dataset.envoi = "1"
    })
    .on("blur", ".titre-document-saisie", function () {
        if (this.dataset.envoi) return
        if (this.value === (this.dataset.initial ?? this.value)) {
            fermerEditionTitreDocument(this)
            return
        }
        jQuery(this.form).trigger("submit")
    })

/**
 * Grise dans la liste des documents joints (cf
 * noisettes/inc/publier_article_documents.html) ceux dont le raccourci est
 * déjà présent dans le champ "texte" (#498) : ces documents s'affichent dans
 * le corps de l'article, les autres en bas de l'article (critère {vu=non},
 * cf noisettes/inc/ajouter_document.html).
 *
 * Même règle que le marquage "vu" du plugin medias (cf
 * plugins-dist/medias/inc/marquer_doublons_doc.php) : <docN>, <imgN> ou
 * <embN>, avec ou sans paramètres (<img12|left>).
 */
function majDocumentsInseres() {
    const texte = document.getElementById("texte")
    const valeur = texte ? texte.value : ""
    document.querySelectorAll("#documents_publier_article .racourcis-container").forEach(element => {
        const fichier = element.closest(".fichier")
        if (!fichier) return
        const id = element.dataset.idDocument
        const insere = new RegExp(`<(doc|img|emb)${id}[|>]`, "i").test(valeur)
        fichier.classList.toggle("insere", insere)
    })
}

jQuery(document).on("input change", "#texte", majDocumentsInseres)

/**
 * Supprimer un document de la liste (croix de sa carte, #BOUTON_ACTION
 * dissocier_document) retire aussi son raccourci du champ "texte" : sinon
 * l'article enregistré garderait un <imgN> pointant vers un document
 * supprimé. L'évènement submit n'arrive qu'une fois la confirmation
 * acceptée (le onclick du bouton renvoie false sinon).
 */
jQuery(document).on("submit", "#documents_publier_article .bouton_action_post", function () {
    const texte = document.getElementById("texte")
    const fichier = this.closest(".fichier")
    const id = fichier && fichier.id.replace(/^doc/, "")
    if (!texte || !/^\d+$/.test(id)) return
    const valeur = texte.value.replace(new RegExp(`<(doc|img|emb)${id}(\\|[^>]*)?>`, "gi"), "")
    if (valeur !== texte.value) {
        texte.value = valeur
        texte.dispatchEvent(new Event("input", { bubbles: true }))
    }
})
// Liste rechargée en ajax après chaque upload/suppression, et injectée à
// l'ouverture du popup (cf loadContentInMainSidebar dans controleurs.js,
// qui relance triggerAjaxLoad).
if (typeof onAjaxLoad === "function") {
    onAjaxLoad(majDocumentsInseres)
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