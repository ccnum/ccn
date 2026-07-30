/**
 * Formulaire de publication d'une mission (consigne), affiché dans la
 * sidebar principale en 2 étapes. Fonctions appelées directement depuis
 * les attributs onclick du squelette du formulaire.
 */

/**
 * Réinitialise le formulaire à l'étape 1 (masque l'étape 2 et le bouton
 * "précédent"). Appelé au chargement du fond "publication_mission"
 * (cf loadContentInMainSidebar dans controleurs.js).
 */
function initPublierFormulaire() {
    const etape2 = document.getElementById("sidebar-etape-2-container")
    const boutonPrecedentElement = document.querySelector("#bouton-etape-precedente-mission")
    etape2.style.display = "none";
    boutonPrecedentElement.style.display = "none";
}

/**
 * Branche le compteur de caractères sur le champ associé au label
 * ".nb-caracteres" (via son attribut "for"), et ajoute la classe "warn"
 * sur ".compteur-caracteres" au-delà de 50 caractères.
 */
function initCompteurCaracteres() {
    const nbCaracteresElement = document.querySelector(".nb-caracteres")
    const inputId = nbCaracteresElement.getAttribute("for")
    const inputElement = document.getElementById(inputId)
    const compteurRoot = document.querySelector(".compteur-caracteres")
    nbCaracteresElement.innerText = 0
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
 * Passe le formulaire de l'étape 1 à l'étape 2.
 */
function creationMissionEtapeSuivante() {
    const etape1 = document.getElementById("sidebar-etape-1-container")
    const etape2 = document.getElementById("sidebar-etape-2-container")
    const boutonPrecedentElement = document.querySelector("#bouton-etape-precedente-mission")
    const boutonSuivantElement = document.querySelector("#bouton-etape-suivante-mission")
    const boutonEnregistrerElement = document.querySelector("#bouton-enregistrer-mission")
    etape1.style.display = "none"
    etape2.style.display = "block"
    boutonPrecedentElement.style.display = "block";
    boutonSuivantElement.style.display = "none";
    boutonEnregistrerElement.style.display = "block";
}

/**
 * Revient de l'étape 2 à l'étape 1 du formulaire.
 */
function creationMissionEtapePrecedente() {
    const etape1 = document.getElementById("sidebar-etape-1-container")
    const etape2 = document.getElementById("sidebar-etape-2-container")
    const boutonPrecedentElement = document.querySelector("#bouton-etape-precedente-mission")
    const boutonSuivantElement = document.querySelector("#bouton-etape-suivante-mission")
    const boutonEnregistrerElement = document.querySelector("#bouton-enregistrer-mission")
    etape1.style.display = "block"
    etape2.style.display = "none"
    boutonPrecedentElement.style.display = "none";
    boutonSuivantElement.style.display = "block";
    boutonEnregistrerElement.style.display = "none";
}

/**
 * Soumet le formulaire "#formulaire_publier_mission".
 */
function creationMissionEnregistrer() {
    const formulaire = document.getElementById("formulaire_publier_mission")
    formulaire.requestSubmit();
}