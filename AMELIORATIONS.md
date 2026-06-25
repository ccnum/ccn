# Améliorations à faire — Plugin SPIP `thematique` (CCN)

**Date** : 2026-06-25

---

## JavaScript

### 1.1 Migrer `$.ajax()` vers `fetch()` + `async/await`

**Fichiers** : `squelettes/js/main.js`, `squelettes/js/controleurs.js`

`$.ajax()` est couplé à `$.parseXML()` et `$.when()`. Les appels `$('#sidebar_main_inner').load()` déclenchent les mécanismes AJAX de SPIP (crayon, ajaxbloc). Migration à faire avec tests d'intégration complets.

---

### 1.7 Extraire le JavaScript inline de `swiper.script.html`

**Fichier** : `squelettes/noisettes/js/swiper.script.html` — 335 lignes de JS dans un squelette SPIP

L'essentiel devrait être dans `squelettes/js/swiper.js`, le squelette ne gardant que les quelques valeurs dynamiques (`#ENV{...}`).

---

## CSS

### 2.3 Ajouter des media queries mobile/tablette

**Fichier** : `thematique.css.html`

Il n'existe qu'une seule media query (`max-width: 1280px`). À traiter dans le cadre d'une refonte responsive dédiée.

Breakpoints à ajouter : `max-width: 768px` et `max-width: 1024px`.

---

### 2.4 Découper le fichier CSS en modules

**Fichier** : `thematique.css.html` — 4497 lignes

Découpage suggéré via `<INCLURE>` SPIP :
```
css/variables.css.html · css/reset.css.html · css/layout.css.html
css/timeline.css.html · css/sidebar.css.html · css/forms.css.html
css/components.css.html · css/responsive.css.html
```

---

## Architecture SPIP

### 4.1 Déplacer la logique métier vers des pipelines PHP

**Fichier** : `squelettes/noisettes/rubrique.html` — 389 lignes mêlant présentation et logique de droits

La logique conditionnelle (rôles, permissions, calculs) devrait être dans `thematique_pipelines.php` via `pre_boucle`/`post_boucle` ou des balises custom dans `balises.php`.

---

### 3.2 Réduire les boucles SPIP imbriquées

**Fichier** : `squelettes/noisettes/isotope-article.html` — 5 boucles imbriquées générant de nombreuses requêtes SQL

Piste : pipeline PHP précalculant les données, ou critère `{jointure}`.

---

## Accessibilité

### 5.3 Remplacer les `<div>` cliquables par des `<button>`

Plusieurs `<div onclick>` dans `consigne.js` et les squelettes. Le remplacement impacte les règles CSS ciblant `.bouton_reponse_consigne`. À faire conjointement avec une refonte des styles de composants.

> **Progrès** : les boutons de réaction sont désormais accessibles au clavier et aux lecteurs d'écran (#315, 2026-06-25), mais les `<div onclick>` restants dans `consigne.js` et les squelettes demeurent à traiter.

---

## Maintenabilité

### 6.3 Compléter la documentation JSDoc

Les fonctions JS publiques manquent de `@param {Type}` et `@returns {Type}`. À compléter progressivement lors des prochains développements.

### 6.2 TODO restant dans `controleurs.js`

Ligne ~893 : `// TODO : cela est appelé deux fois minimum à cause de History.js` — comportement connu, non trivial à corriger.
