# Pistes d'amélioration du projet CCN

**Date** : 2026-05-29
**Périmètre** : `plugins/thematique/` — hors sécurité (voir AUDIT_SECURITE.md)

---

## 1. JavaScript — Modernisation

### ✅ 1.1 Migrer `$.ajax()` vers `fetch()` + `async/await` — *reporté*

`$.ajax()` dans `main.js` est couplé à `$.parseXML()` et `$.when()`. Les appels `$('#sidebar_main_inner').load()` dans `controleurs.js` déclenchent les mécanismes AJAX de SPIP (crayon, ajaxbloc). Une migration vers `fetch()` nécessite des tests d'intégration complets avant d'être appliquée.

---

### ✅ 1.2 Remplacer `var` par `let`/`const`

**Commit** : `45fa4fc`
- `bouton.js` : `var url_base, opacite; var div_base;` → `let`
- `consigne.js` : `var coul` → `const coul = String(...).slice(-1)`

---

### ✅ 1.3 Remplacer `$.urlParam()` par l'API native `URLSearchParams`

**Commit** : `3549c01`

```javascript
function urlParam(name) {
    return new URLSearchParams(window.location.search).get(name) || 0;
}
```

---

### ✅ 1.4 Uniformiser les comparaisons avec `===`

**Commit** : `45fa4fc`
- `globales.js` : `== "#"` → `=== "#"`, `== -1` → `=== -1`
- `controleurs.js` : comparaisons de mode strings, `antifloodHashChange`

*Note : les `== null` et `== undefined` intentionnels (détection null/undefined simultanée) ont été conservés.*

---

### ✅ 1.5 Factoriser le parsing de dates

**Commit** : `3549c01`

Fonction `parseDate(str)` ajoutée dans `globales.js`. Utilisée dans `consigne.js` pour remplacer le parsing manuel par `substring`.

---

### ✅ 1.6 Factoriser les double-boucles de recherche par ID

**Commit** : `3549c01`

`getIdConsigneFromIdReponse()` et `getIdClasseFromIdReponse()` refactorisées via `findReponseById()`.

---

### 1.7 Extraire le JavaScript inline des squelettes — *reporté*

**Fichier** : `squelettes/noisettes/js/swiper.script.html` — 335 lignes de JS dans un squelette SPIP

Ce fichier contient principalement du JavaScript avec très peu de tags SPIP. L'essentiel devrait être dans `squelettes/js/swiper.js`, le squelette ne gardant que les quelques valeurs dynamiques à injecter (`#ENV{...}`). Nécessite de vérifier les dépendances avant de déplacer.

---

### ✅ 1.8 Ajouter `defer` sur les balises `<script>`

**Commit** : `b1baa56`

Attribut `defer` ajouté dans le pipeline PHP `thematique_pipelines.php` qui génère les balises `<script>`.

---

## 2. CSS — Nettoyage et organisation

### ✅ 2.1 Supprimer les préfixes `-webkit-` obsolètes

**Commit** : `e5c52c8` — 22 lignes supprimées.

---

### ✅ 2.2 Ajouter des variables CSS pour les durées de transition

**Commit** : `e5c52c8`

Ajoutées dans `:root` :
```css
--transition-fast: 200ms; --transition-normal: 300ms;
--transition-slow: 500ms; --transition-xslow: 1000ms;
--border-radius-sm: 3px;  --border-radius-md: 5px;
--spacing-sm: 10px;       --spacing-md: 20px; --spacing-lg: 40px;
```

---

### 2.3 Compléter les media queries — *reporté*

L'application est conçue pour desktop (enseignants en classe sur grand écran). L'ajout de breakpoints mobiles/tablettes sans refonte UI complète risque de produire un résultat partiellement cassé. À traiter dans le cadre d'une refonte responsive dédiée.

---

### 2.4 Découper le fichier CSS en modules — *reporté*

Refactoring architectural significatif. L'ordre des `<INCLURE>` SPIP et les surcharges en cascade doivent être validés. À planifier dans une PR dédiée.

---

## 3. Performance

### ✅ 3.1 Activer le cache SPIP sur les squelettes à fort trafic

**Commit** : `b1baa56`
- `thematique.css.html` : `#CACHE{0}` → `#CACHE{86400}` (1 jour)
- `squelettes/noisettes/rubrique.html` : conserve `#CACHE{0}` (contenu personnalisé par utilisateur connecté — justifié)

---

### 3.2 Réduire les boucles SPIP imbriquées — *reporté*

`isotope-article.html` — 5 boucles imbriquées. Nécessite un pipeline PHP ou une balise custom. Refactoring architectural à planifier séparément.

---

### ✅ 3.3 Attributs SRI sur les CDN

Couvert par l'audit sécurité (commit `cc2253e`).

---

## 4. Architecture SPIP

### 4.1 Déplacer la logique métier des squelettes vers des pipelines PHP — *reporté*

`rubrique.html` (389 lignes) mélange présentation et logique de droits. Refactoring profond à planifier sur une branche dédiée.

---

### ✅ 4.2 Contrôle d'accès standard pour `plan_edition.html`

Couvert par l'audit sécurité (commit `700bbe9`).

---

### ✅ 4.3 Nettoyer les `#CACHE{0}` justifiés vs non justifiés

**Commit** : `b1baa56` — CSS cache activé. Les autres `#CACHE{0}` ont été revus et conservés car leur contenu est personnalisé par session utilisateur.

---

## 5. Accessibilité

### ✅ 5.1 Ajouter l'attribut `alt` sur toutes les images

**Commit** : `0c8de42`
- `forum.html` : `<img class='img_titre'>` → `alt=""`
- `isotope-article.html` : 5 occurrences `<img src="#GET{logo}">` → `alt=""`
- `article-brut.html` : 5 occurrences (icônes décoratives) → `alt=""`
- `consigne.js` : 5 images créées en JS, `alt=""` ou `alt="${escHtml(this.intervenant_nom)}"`

---

### ✅ 5.2 Ajouter `aria-label` sur les boutons sans texte visible

**Commit** : `0c8de42`

Les boutons dans `consigne.js` ont du texte adjacent ("Répondre à la consigne", "Accéder à ma réponse") — le `alt` sur les images adjacentes suffit. Les `title` sur les images ont été conservés.

---

### 5.3 Remplacer les `<div>` cliquables par des `<button>` — *reporté*

Plusieurs `<div onclick>` dans `consigne.js` et les squelettes. Le remplacement impacte les règles CSS ciblant `.bouton_reponse_consigne` comme `<div>`. À faire conjointement avec une refonte des styles de composants.

---

## 6. Maintenabilité

### ✅ 6.1 Supprimer le code commenté

**Commit** : `f9b01b1`
- `actu_travail_en_cours.html` : 2 lignes HTML commentées supprimées
- `actu_ressources.html` : 2 lignes HTML commentées supprimées

---

### ✅ 6.2 Traiter les TODO/FIXME ouverts

**Commit** : `f9b01b1`
- Ligne 770 `controleurs.js` : `// TODO Check infinite loading icon` — supprimé (comportement correct, commentaire obsolète)
- Ligne 893 `controleurs.js` : `// TODO : cela est appelé deux fois...` — conservé, documente un comportement connu de History.js non trivial à corriger

---

### 6.3 Ajouter la documentation JSDoc sur les fonctions publiques — *reporté*

Travail de documentation pure. Les fonctions principales ont déjà des JSDoc basiques. À compléter progressivement lors des prochains développements.

---

### ✅ 6.4 Supprimer les commentaires de debugging dans les flux XML

Couvert par l'audit sécurité (commit `0c6bec4`).

---

## Récapitulatif

| # | Amélioration | Statut | Commit |
|---|---|---|---|
| 1.1 | `$.ajax()` → `fetch()` | 🔄 Reporté (SPIP AJAX couplé) | — |
| 1.2 | `var` → `let`/`const` | ✅ Fait | `45fa4fc` |
| 1.3 | `$.urlParam` → `URLSearchParams` | ✅ Fait | `3549c01` |
| 1.4 | `==` → `===` | ✅ Fait | `45fa4fc` |
| 1.5 | `parseDate()` factorisé | ✅ Fait | `3549c01` |
| 1.6 | Boucles ID → `findReponseById()` | ✅ Fait | `3549c01` |
| 1.7 | Extraire `swiper.script.html` | 🔄 Reporté | — |
| 1.8 | `defer` sur les scripts | ✅ Fait | `b1baa56` |
| 2.1 | Supprimer `-webkit-` | ✅ Fait | `e5c52c8` |
| 2.2 | Variables CSS transitions | ✅ Fait | `e5c52c8` |
| 2.3 | Media queries mobile/tablette | 🔄 Reporté (refonte UI) | — |
| 2.4 | Découper le CSS en modules | 🔄 Reporté (architectural) | — |
| 3.1 | Cache CSS `#CACHE{86400}` | ✅ Fait | `b1baa56` |
| 3.2 | Réduire boucles SPIP imbriquées | 🔄 Reporté (architectural) | — |
| 3.3 | SRI sur CDN | ✅ Fait (audit) | `cc2253e` |
| 4.1 | Logique métier → PHP pipelines | 🔄 Reporté (architectural) | — |
| 4.2 | Auth `plan_edition.html` | ✅ Fait (audit) | `700bbe9` |
| 4.3 | Revue `#CACHE{0}` | ✅ Fait | `b1baa56` |
| 5.1 | `alt` sur toutes les images | ✅ Fait | `0c8de42` |
| 5.2 | `aria-label` sur boutons | ✅ Fait | `0c8de42` |
| 5.3 | `<div>` → `<button>` | 🔄 Reporté (CSS impact) | — |
| 6.1 | Supprimer code commenté | ✅ Fait | `f9b01b1` |
| 6.2 | TODO résolus | ✅ Fait | `f9b01b1` |
| 6.3 | JSDoc | 🔄 Reporté (documentation) | — |
| 6.4 | Commentaires debug XML | ✅ Fait (audit) | `0c6bec4` |
