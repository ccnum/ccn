function initPublierFormulaire() {
    const etape2 = document.getElementById("sidebar-etape-2-container")
    const boutonPrecedentElement = document.querySelector("#bouton-etape-precedente-mission")
    etape2.style.display = "none";
    boutonPrecedentElement.style.display = "none";
}

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

function creationMissionEnregistrer() {
    const formulaire = document.getElementById("formulaire_publier_mission")
    formulaire.requestSubmit();
}