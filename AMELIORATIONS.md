# Améliorations à faire — Plugin SPIP `thematique` (CCN)

**Date** : 2026-06-26

---

## CSS

### 2.3 Ajouter des media queries mobile/tablette

**Fichier** : `thematique.css.html`

Il n'existe qu'une seule media query (`max-width: 1280px`). À traiter dans le cadre d'une refonte responsive dédiée.

Breakpoints à ajouter : `max-width: 768px` et `max-width: 1024px`.

---

## Architecture SPIP

### 3.2 Réduire les boucles SPIP imbriquées

**Fichiers candidats** :
- `squelettes/noisettes/inc/actus_timeline.html` — 24 boucles
- `squelettes/noisettes/sommaire.html` — 17 boucles

Piste : pipeline PHP précalculant les données, ou critère `{jointure}`.

---

### 4.1 Déplacer la logique métier vers des pipelines PHP

**Fichier** : `squelettes/noisettes/rubrique.html` — mêle présentation et logique de droits

La logique conditionnelle (rôles, permissions, calculs) devrait être dans `thematique_pipelines.php` via `pre_boucle`/`post_boucle` ou des balises custom dans `balises.php`.

---

## Accessibilité

### 5.3 Remplacer les `<div>` cliquables par des `<button>`

Plusieurs `<div onclick>` dans `consigne.js` et les squelettes. Le remplacement impacte les règles CSS ciblant `.bouton_reponse_consigne`. À faire conjointement avec une refonte des styles de composants.

> **Progrès** : les boutons de réaction sont désormais accessibles au clavier et aux lecteurs d'écran (#315, 2026-06-25), mais les `<div onclick>` restants dans `consigne.js` et les squelettes demeurent à traiter.

---

## Maintenabilité

### 6.2 TODO restant dans `controleurs.js`

Ligne ~893 : `// TODO : cela est appelé deux fois minimum à cause de History.js` — comportement connu, non trivial à corriger.
