# Pistes d'amélioration du projet CCN

**Date** : 2026-05-29
**Périmètre** : `plugins/thematique/` — hors sécurité (voir AUDIT_SECURITE.md)

---

## 1. JavaScript — Modernisation

### 1.1 Migrer `$.ajax()` vers `fetch()` + `async/await`

**Fichiers** : `squelettes/js/main.js` lignes 48, 112, 147, 344, 401 · `squelettes/js/controleurs.js` lignes 956–1005

Tous les appels réseau utilisent `$.ajax()` avec des callbacks. La migration vers `fetch()` améliore la lisibilité et supprime une dépendance jQuery.

```javascript
// Avant
$.ajax({ url: fichier, dataType: 'text' }).then(function(data) { ... });

// Après
const data = await fetch(fichier).then(r => r.text());
```

---

### 1.2 Remplacer `var` par `let`/`const`

**Fichiers** : `squelettes/js/bouton.js` lignes 7–8 · `squelettes/js/consigne.js` ligne 53 · nombreux endroits dans `main.js` et `controleurs.js`

`var` a une portée de fonction et des comportements de hoisting sources de bugs. Remplacer systématiquement par `const` (valeur non réassignée) ou `let`.

---

### 1.3 Remplacer `$.urlParam()` par l'API native `URLSearchParams`

**Fichier** : `squelettes/js/controleurs.js` lignes 4–12

```javascript
// Avant
$.urlParam = function(name) {
    const results = new RegExp('[?&]' + name + '=([^&#]*)').exec(window.location.href);
    ...
}

// Après
const params = new URLSearchParams(window.location.search);
params.get('page');
```

---

### 1.4 Uniformiser les comparaisons avec `===`

**Fichiers** : `squelettes/js/globales.js` lignes 32, 88, 94, 97, 100 · `squelettes/js/controleurs.js` lignes 88, 100, 233 · ~37 occurrences dans `main.js`

Les comparaisons `==` permettent des coercions implicites inattendues. Remplacer toutes les occurrences par `===`.

---

### 1.5 Factoriser le parsing de dates

Le même pattern de découpage de chaîne date apparaît dans 5 fichiers différents :
```javascript
// Répété dans globales.js, consigne.js, main.js (×4)
const jour = str.substring(0, 2);
const mois = str.substring(3, 5);
const annee = str.substring(6, 10);
```

Créer une fonction utilitaire unique dans `globales.js` :
```javascript
function parseDate(str) {
    const [day, month, year] = str.split('/').map(Number);
    return new Date(year, month - 1, day);
}
```

---

### 1.6 Factoriser les double-boucles de recherche par ID

**Fichier** : `squelettes/js/controleurs.js` lignes 789 et 810

`getIdConsigneFromIdReponse()` et `getIdClasseFromIdReponse()` ont le même pattern de double boucle imbriquée. Les unifier en une fonction générique.

---

### 1.7 Extraire le JavaScript inline des squelettes

**Fichier** : `squelettes/noisettes/js/swiper.script.html` — 335 lignes de JS dans un squelette SPIP

Ce fichier contient principalement du JavaScript avec très peu de tags SPIP. L'essentiel devrait être dans `squelettes/js/swiper.js`, le squelette ne gardant que les quelques valeurs dynamiques à injecter (`#ENV{...}`).

---

### 1.8 Ajouter `defer` sur les balises `<script>`

**Fichier** : `squelettes/layout.html` ligne 75

Les 10 fichiers JS sont chargés de façon synchrone, bloquant le rendu de la page. Ajouter l'attribut `defer` pour les charger sans bloquer.

```html
<script src="..." defer></script>
```

---

## 2. CSS — Nettoyage et organisation

### 2.1 Supprimer les préfixes `-webkit-` obsolètes

**Fichier** : `thematique.css.html` — plus de 30 occurrences

`-webkit-transition` et `-webkit-transform` sont supportés nativement dans tous les navigateurs modernes depuis des années. Ces préfixes alourdissent inutilement le fichier.

Occurrences principales aux lignes : 902, 2091, 2109, 2123, 2137, 2155, 2171, 2241, 2256, 2812, 2842, 2850, 2880, 2929, 4146, 4212, 4334, 4367.

---

### 2.2 Ajouter des variables CSS pour les durées de transition

**Fichier** : `thematique.css.html`

Les valeurs `200ms`, `250ms`, `300ms`, `500ms`, `1000ms` apparaissent 37+ fois chacune sans variable. Ajouter au bloc `:root` (ligne 27) :

```css
:root {
    --transition-fast: 200ms;
    --transition-normal: 300ms;
    --transition-slow: 500ms;
    --transition-xslow: 1000ms;
    --border-radius-sm: 3px;
    --border-radius-md: 5px;
    --spacing-sm: 10px;
    --spacing-md: 20px;
    --spacing-lg: 40px;
}
```

---

### 2.3 Compléter les media queries

**Fichier** : `thematique.css.html` lignes 4476–4519

Il n'existe qu'une seule media query (`max-width: 1280px`). L'interface n'est pas adaptée pour les usages mobiles ou tablettes qui pourraient survenir.

Breakpoints recommandés à ajouter :
- `max-width: 768px` — mobile portrait
- `max-width: 1024px` — tablette / petits écrans

---

### 2.4 Découper le fichier CSS en modules

**Fichier** : `thematique.css.html` — 4519 lignes

Le fichier est difficile à maintenir à cette taille. Découpage suggéré via `<INCLURE>` SPIP :

```
css/
  variables.css.html       (variables :root)
  reset.css.html           (reset, typo globale)
  layout.css.html          (menu_haut, menu_bas, zones)
  timeline.css.html        (timeline, layers, consignes)
  sidebar.css.html         (sidebar, popup, interventions)
  forms.css.html           (formulaires, crayon, upload)
  components.css.html      (portfolio, swiper, tooltips)
  responsive.css.html      (media queries)
```

---

## 3. Performance

### 3.1 Activer le cache SPIP sur les squelettes à fort trafic

Plusieurs squelettes fréquemment appelés ont `#CACHE{0}` (pas de cache du tout) :

| Fichier | Impact |
|---------|--------|
| `thematique.css.html` ligne 3 | CSS régénéré à chaque requête |
| `squelettes/noisettes/rubrique.html` ligne 2 | Vue principale rechargée sans cache |

Pour le CSS, utiliser `#CACHE{86400}` (1 jour) avec invalidation sur modification. Pour les vues dynamiques par utilisateur, envisager un cache court (`#CACHE{60}`) ou un cache conditionnel.

---

### 3.2 Réduire les boucles SPIP imbriquées

**Fichier** : `squelettes/noisettes/isotope-article.html` lignes 1–55

5 boucles imbriquées génèrent de nombreuses requêtes SQL pour chaque article. Envisager :
- Un pipeline PHP qui précalcule les données dans une seule requête
- L'utilisation du critère `{jointure}` SPIP pour limiter les requêtes

---

### 3.3 Ajouter les attributs SRI sur les CDN

**Fichier** : `squelettes/layout.html` lignes 43–47

Swiper et PDF.js sont chargés depuis des CDN sans `integrity`. En plus du risque sécurité (voir AUDIT), l'absence de SRI empêche une éventuelle mise en cache de service worker.

Alternative recommandée : déplacer ces bibliothèques dans `squelettes/js/bundled/` pour s'affranchir des CDN.

---

## 4. Architecture SPIP

### 4.1 Déplacer la logique métier des squelettes vers des pipelines PHP

**Fichier** : `squelettes/noisettes/rubrique.html` — 389 lignes avec logique de permissions et de filtrage imbriquée

Les squelettes SPIP doivent idéalement ne contenir que de la présentation. La logique conditionnelle complexe (rôles, droits d'accès, calculs) devrait être dans :
- `thematique_pipelines.php` via le pipeline `pre_boucle` ou `post_boucle`
- Des balises SPIP custom dans `balises.php`

---

### 4.2 Contrôle d'accès standard pour `plan_edition.html`

Voir AUDIT_SECURITE.md H4. Remplacer la liste de logins hardcodés par `#AUTORISER{webmestre}`.

---

### 4.3 Nettoyer les `#CACHE{0}` justifiés vs non justifiés

Passer en revue tous les `#CACHE{0}` du projet et distinguer :
- Ceux qui sont nécessaires (contenu personnalisé par utilisateur connecté) → documenter pourquoi
- Ceux qui sont là "par défaut" ou par précaution → activer un cache adapté

---

## 5. Accessibilité

### 5.1 Ajouter l'attribut `alt` sur toutes les images

**Fichiers concernés** :
- `squelettes/noisettes/forum.html` : `<img class='img_titre' src="...">` sans `alt`
- `squelettes/noisettes/isotope-article.html` : 7+ occurrences `<img src="#GET{logo}" />`
- `squelettes/noisettes/article-brut.html` : 7+ occurrences
- `squelettes/js/consigne.js` ligne 86 : images créées en JS sans `alt`

Pour les images décoratives : `alt=""`. Pour les images porteuses de sens : décrire le contenu.

---

### 5.2 Ajouter `aria-label` sur les boutons sans texte visible

**Fichier** : `squelettes/js/consigne.js` lignes 97–105

Les boutons d'action (répondre, supprimer) créés en JavaScript n'ont pas d'attribut `aria-label`, ce qui les rend inaccessibles aux lecteurs d'écran.

```javascript
// Avant
'<div class="bouton_reponse_consigne" onclick="...">'

// Après
'<button class="bouton_reponse_consigne" aria-label="Répondre à la consigne" onclick="...">'
```

---

### 5.3 Remplacer les `<div>` cliquables par des `<button>`

Dans plusieurs endroits, des `<div>` avec `onclick` sont utilisés à la place de `<button>`. Les `<button>` sont nativement accessibles (focusable au clavier, rôle ARIA implicite).

---

## 6. Maintenabilité

### 6.1 Supprimer le code commenté

**Fichiers** :
- `squelettes/modeles/actu_travail_en_cours.html` : blocs HTML commentés
- `squelettes/modeles/actu_ressources.html` : idem
- `thematique.css.html` : plusieurs blocs CSS commentés résiduels

Le code versionné avec git rend les commentaires de "sauvegarde" inutiles.

---

### 6.2 Traiter les TODO/FIXME ouverts

**Fichier** : `squelettes/js/controleurs.js`
- Ligne 776 : `// TODO Check infinite loading icon`
- Ligne 893 : `// TODO : cela est appelé deux fois minimum à cause de History.js`

Ces TODO sont des dettes techniques documentées. Les convertir en issues ou les corriger.

---

### 6.3 Ajouter la documentation JSDoc sur les fonctions publiques

Les fonctions et classes JS n'ont pas de documentation de types (`@param`, `@returns`), ce qui complique la maintenance et empêche l'autocomplétion dans les IDEs.

```javascript
/**
 * @param {number} idReponse
 * @returns {number|null} id de la consigne parente
 */
function getIdConsigneFromIdReponse(idReponse) { ... }
```

---

### 6.4 Supprimer les commentaires de debugging dans les flux XML

**Fichier** : `squelettes/xml/consignes.html` lignes 5–8

```html
<!-- #ID_RUBRIQUE-->
<!-- date début : #CONST{_date_debut} -->
```

Ces commentaires exposent des informations internes dans les réponses publiques (voir AUDIT F4).

---

## Récapitulatif par priorité

### Priorité haute (impact fort, effort modéré)
1. **Activer le cache** sur `thematique.css.html` et `rubrique.html` (#CACHE)
2. **Corriger les vulnérabilités de sécurité** (voir AUDIT_SECURITE.md)
3. **Ajouter `defer`** sur les balises `<script>` dans `layout.html`
4. **Factoriser le parsing de dates** dans `globales.js`

### Priorité moyenne (amélioration progressive)
5. **Migrer `$.ajax()` → `fetch()`** dans `main.js` et `controleurs.js`
6. **Supprimer les préfixes `-webkit-`** dans le CSS
7. **Ajouter les variables CSS** pour les transitions et espacements
8. **Ajouter `alt`** sur toutes les images manquantes

### Priorité basse (dette technique)
9. **Extraire `swiper.script.html`** vers un fichier JS externe
10. **Découper `thematique.css.html`** en modules
11. **Remplacer `var` par `let`/`const`** dans tout le JS
12. **Ajouter JSDoc** sur les fonctions publiques
13. **Nettoyer le code commenté** et les TODO
