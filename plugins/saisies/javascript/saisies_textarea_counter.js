/*!
  Scampi textarea-counter
  Décompte les caractères restants dans les éléments textarea disposant d’un maxlength
  Ajoute des attributs aria pour annoncer le nombre de caractères restant
*/
var Scampi = Scampi || {};

Scampi.textareaCounter = function textareaCounter() {
  var textAreaFields = document.querySelectorAll("textarea[maxlength]");
  var stepPolite = 100;
  var stepAssertive = 20;

  Array.prototype.forEach.call(textAreaFields, function (textarea) {
    var idTextarea = textarea.id;
    if(!document.querySelector("#"+idTextarea + "-counter")) {
      var maxLength = textarea.getAttribute("maxlength");
      var messageLength = textarea.value.length;
      var activeValue = countRest(maxLength, messageLength);
      textarea.setAttribute("aria-describedby", idTextarea + "-counter");
      textarea.insertAdjacentHTML("afterend", "<p class='textarea-counter' id='" + idTextarea + "-counter'><span class='textarea-counter-nb'>" + activeValue + "</span> " + saisies_caracteres_restants + "</p>");
      textarea.addEventListener("input", handleInput);
      textarea.addEventListener("keypress", handleInput);
    }
  });

  function handleInput(evt) {
    textarea = evt.target;
    paragraph = textarea.nextElementSibling;
    updateValue(textarea, paragraph);
  }

  function countRest(maxlength, messageLength) {
    return maxlength - messageLength;
  }

  function countStepPolite(maxLengthValue) {
    return maxLengthValue - stepPolite;
  }

  function countStepAssertive(maxLengthValue) {
    return maxLengthValue - stepAssertive;
  }

  function updateAria(maxLengthValue, messageLength, paragraph) {
    politeFlag = countStepPolite(maxLengthValue);
    assertiveFlag = countStepAssertive(maxLengthValue);

    if (messageLength < politeFlag) {
      paragraph.removeAttribute("aria-live");
      paragraph.removeAttribute("aria-atomic");
    }
    else if (messageLength >= politeFlag && messageLength < assertiveFlag) {
      paragraph.setAttribute("aria-live", "polite");
      paragraph.setAttribute("aria-atomic", "true");
    }
    else if (messageLength >= assertiveFlag) {
      paragraph.setAttribute("aria-live", "assertive");
      paragraph.setAttribute("aria-atomic", "true");
    }
  }

  function updateValue(textarea, paragraph) {
    var maxLength = textarea.getAttribute("maxlength");
    var messageLength = textarea.value.length;
    var counter = paragraph.querySelector(".textarea-counter-nb")

    counter.innerText = countRest(maxLength, messageLength);
    updateAria(maxLength, messageLength, paragraph);
  }
}

jQuery(function(){
  Scampi.textareaCounter();
  onAjaxLoad(Scampi.textareaCounter);
});
