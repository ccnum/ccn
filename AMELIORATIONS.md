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
- ~~`squelettes/noisettes/inc/actus_timeline.html` — 24 boucles~~
- `squelettes/noisettes/sommaire.html` — 17 boucles

Piste : pipeline PHP précalculant les données, ou critère `{jointure}`.

> **Progrès (2026-08-03)** : `actus_timeline.html` refait — la résolution de hiérarchie (mot-clé → ids de rubriques : `evenements`/`ressources`/`travail_en_cours`/`consignes`) est déplacée dans 4 nouvelles fonctions PHP de `thematique_fonctions.php` (`thematique_ids_rubriques_racine_a_mot`, `thematique_id_rubrique_racine_a_mot`, `thematique_ids_rubriques_enfants`, `thematique_ids_rubriques_petits_enfants_a_mot`), qui remplacent les chaînes `BOUCLE(RUBRIQUES){id_parent}` imbriquées par un tableau résolu une seule fois. Boucles restantes : 24 → 19, profondeur d'imbrication max 5 → 3. Comportement vérifié équivalent (contenu réel généré sans erreur via `recuperer_fond()` en CLI, ce fond n'étant actuellement jamais rendu en usage web puisque conditionné à `_PROJET != 'laclasse'`, valeur qu'il vaut partout sur ce dépôt).
>
> `sommaire.html` (17 boucles) reste à traiter séparément — logique différente (menus, badges, timeline), pas la même resolution de hiérarchie.

---

### 4.1 Déplacer la logique métier vers des pipelines PHP

**Fichier** : `squelettes/noisettes/rubrique.html` — mêle présentation et logique de droits

La logique conditionnelle (rôles, permissions, calculs) devrait être dans `thematique_pipelines.php` via `pre_boucle`/`post_boucle` ou des balises custom dans `balises.php`.

---

## Accessibilité

### 5.3 Remplacer les `<div>` cliquables par des `<button>`

Plusieurs `<div onclick>` dans `consigne.js` et les squelettes. Le remplacement impacte les règles CSS ciblant `.bouton_reponse_consigne`. À faire conjointement avec une refonte des styles de composants.

> **Progrès** : les boutons de réaction sont désormais accessibles au clavier et aux lecteurs d'écran (#315, 2026-06-25).
>
> **Progrès (2026-08-03)** : toutes les div cliquables restantes (`consigne.js`, `reponse.js`, `article.js`, les modèles `actu_*`, `rubrique_detail.html`, `reponse_binome_head.html`, les icônes de sidebar) ont désormais `role="button"` + `tabindex="0"`, activables au clavier via le handler générique déjà en place (`controleurs.js`), avec un focus visible (`[role="button"]:focus-visible` dans `sidebar.css.html`). Le remplacement par de vrais `<button>` (qui impose de refaire les styles de composants) reste à faire dans le cadre d'une refonte dédiée.

---

## Maintenabilité

### 6.2 TODO restant dans `controleurs.js`

Ligne ~893 : `// TODO : cela est appelé deux fois minimum à cause de History.js` — comportement connu, non trivial à corriger.
