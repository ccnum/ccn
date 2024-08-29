# Textarea counter

Script extrait de Scampi, une biliothèque légère de composants scss, html, javascript respectant la Checklist Pidila : RGAA, Charte internet de l’État, Éco-conception, Bonnes pratiques Opquast, RGI.

https://pidila.gitlab.io/scampi/

Présentation
-------------------------------------------

Ce script permet d’ajouter le décompte de caractères restant sur un élément de formulaire de type `textarea` lorsque que l’attribut `maxlength` est défini.

Les utilisateurs de lecteurs d’écran sont prévenus du nombre de caractères restant lorsque certains seuils sont atteint grâce aux attributs `aria-live="polite"` ou `aria-live="assertive"` et `aria-atomic="true"`.

Ces seuils sont configurables dans le script à travers les variables `stepPolite` et `stepAssertive`.

Ils sont fixés pour :
-  `aria-live="polite"` lorsqu’il reste 100 caractères ou moins
-  `aria-live="assertive"` lorsqu’il reste 20 caractères ou moins


Utilisation
---------------------------------------------------

Inclure le script et le style à votre projet.

Renseigner l’attribut `maxlength` sur l’élément `textarea`.

### Configuration

La variable proposée dans ce module est :

- `$textarea-counter-nb-color` : couleur du compteur, sa valeur par défaut est `$primary-color`

Exemple d’utilisation
---------------------------------------------------------------------


### Code écrit

```html
<div class="form-group">
  <label for="textarea-1">Test maxlength à 240</label>
  <textarea class="form-control" id="textarea-1" cols="30" rows="4" maxlength="240"></textarea>
</div>
```

### Code généré

```html
<div class="form-group">
  <label for="textarea-1">Test maxlength à 240</label>
  <textarea class="form-control" id="textarea-1" cols="30" rows="4" maxlength="240" ariadescribedby="textarea-1-counter"></textarea>
  <p class="textarea-counter" id="textarea-1-counter"><span class="textarea-counter-nb">240</span> caractères restants</p>
</div>
```

