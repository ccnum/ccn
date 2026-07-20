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
    const etape1 = document.getElementById("etape-1-container") 
    const etape2 = document.getElementById("etape-2-container") 
    const boutonElement = document.querySelector("#bouton-etape-precedente-mission")
    etape1.style.display = "none"
    etape2.style.display = "block"
    boutonContainer.style.display = "block"
}

function creationMissionEtapePrecedente() {
    const etape1 = document.getElementById("etape-1-container") 
    const etape2 = document.getElementById("etape-2-container") 
    const boutonElement = document.querySelector("#bouton-etape-precedente-mission")
    etape1.style.display = "block"
    etape2.style.display = "none"
    boutonElement.style.display = "none"
}