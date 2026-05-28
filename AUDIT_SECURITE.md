# Audit de sécurité — Plugin SPIP `thematique`

**Date** : 2026-05-28
**Périmètre** : JavaScript (`squelettes/js/`), squelettes SPIP (`squelettes/`), templates XML, PHP (pipelines, formulaires, administrations, autoriser)
**Outil** : Revue manuelle complète du code source

---

## Résumé

| Sévérité  | Nombre |
|-----------|--------|
| Critique  | 1      |
| Élevée    | 2      |
| Moyenne   | 1      |
| Faible    | 1      |
| **Total** | **5**  |

> **Périmètre PHP** : `thematique_pipelines.php`, `thematique_autoriser.php`, `thematique_administrations.php`, `thematique_options.php`, `formulaires/public_editer_article.php`, `base/th_cextras.php`, `base/th_install.php` — aucune injection SQL détectée ; toutes les valeurs passées à `sql_*` sont protégées par `intval()` ou `sql_quote()`.
>
> **Templates ajax SPIP** : `noisettes/ajax/article-sauve-coordonnees.html` et `noisettes/ajax/article-sauve-mot.html` — non vulnérables à l'injection SQL (confirmés par la revue précédente). Les formulaires SPIP utilisent `#ACTION_FORMULAIRE{}` (token CSRF automatique).

---

## Critique

### N1 — XSS DOM : `pdfName` et `$imgSrc` non échappés dans des template literals

**Fichier** : `squelettes/noisettes/js/swiper.script.html` — fonctions `initPdfSwipers`, `initPdfSwipersInComment`

```js
const pdfName = decodeURIComponent(pdfUrl.split('/').pop());

// Injecté tel quel dans .html() via template literal
const $loader = $(`
    <div class="swiper-pdf-loader">
        <span>Chargement de "${pdfName}" ...</span>
    </div>
`);
$container.html(`
    <div class="swiper-pdf-loader">
        <span>Erreur lors du chargement de "${pdfName}"</span>
    </div>
`);
```

**Risque** : `pdfName` est décodé depuis l'URL du fichier PDF (nom du fichier uploadé). Si SPIP ne sanitise pas suffisamment les noms de fichiers lors de l'upload, un fichier nommé `"><img src=x onerror=alert(1)>.pdf` produirait une XSS pour tous les utilisateurs consultant l'article contenant ce document.

Le même problème existe avec `$imgSrc` interpolé dans `<img src="${$imgSrc}"/>`.

**Correction suggérée** : Construire les éléments DOM avec jQuery plutôt que par template literal.

```js
const $loader = $('<div class="swiper-pdf-loader">');
$('<span>').text('Chargement de "' + pdfName + '" ...').appendTo($loader);
```

---

## Élevée

### N2 — Routage AJAX sans whitelist dans `xml/ajax.html` (S6 bis)

**Fichier** : `squelettes/xml/ajax.html`

```
<INCLURE{fond=noisettes/ajax/#MODE, env, ajax} />
```

Le fichier `squelettes/ajax.html` a été corrigé en whitelist (commit `d13af81`), mais `xml/ajax.html` conserve l'ancien pattern ouvert. Toute valeur de `#MODE` non filtrée y route vers `noisettes/ajax/#MODE`.

**Correction suggérée** : Appliquer la même whitelist que dans `ajax.html`.

```
[(#MODE|match{article-sauve-coordonnees|article-sauve-mot|article-forum-detail}|oui)
    <INCLURE{fond=noisettes/ajax/#MODE}{env}>
]
```

---

### N3 — Chargement de PDF.js depuis un CDN tiers sans Subresource Integrity

**Fichier** : `squelettes/noisettes/js/swiper.script.html` ligne 5

```js
const PDFJS_WORKER_SRC =
    'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
```

Ce worker est chargé dynamiquement via `pdfjsLib.GlobalWorkerOptions.workerSrc`. Si le CDN est compromis ou si le fichier est altéré, il s'exécute dans le contexte de la page avec accès complet au DOM. Il n'y a pas de vérification d'intégrité (SRI).

**Correction suggérée** : Auto-héberger le worker dans `squelettes/js/` ou ajouter un hash SRI.

```js
// Option 1 : path local
const PDFJS_WORKER_SRC = '[(#CHEMIN{js/pdf.worker.min.js})]';

// Option 2 : integrity sur le CDN (hash SHA-256 du fichier)
// pdfjsLib.GlobalWorkerOptions.workerSrc ne supporte pas SRI nativement ;
// préférer l'option 1.
```

---

## Moyenne

### N4 — `background-image` construit par concaténation depuis les données XML

**Fichier** : `squelettes/js/projet.js` ligne 63

```js
this.timeline_parent.css({ 'background-image': 'url(' + data.image_fond + ')' });
```

`data.image_fond` provient du nœud `<image_fond>` du flux XML (`xml/projet.html`), lui-même généré par SPIP. La valeur est contrôlée par le serveur, mais elle est injectée sans échappement dans une valeur CSS. Sur des navigateurs anciens, `url(javascript:...)` dans une propriété CSS background-image peut déclencher du code. Sur des navigateurs modernes ce vecteur est bloqué, mais la concaténation brute peut casser le rendu si la valeur contient des parenthèses ou des guillemets.

**Correction suggérée** : Échapper la valeur pour le contexte CSS URL.

```js
const imageUrl = CSS.escape
    ? 'url("' + data.image_fond.replace(/"/g, '\\"') + '")'
    : 'url(' + data.image_fond + ')';
this.timeline_parent.css({ 'background-image': imageUrl });
```

---

## Faible

### N5 — Absence de `#CACHE{0}` dans `xml/articles_evenement.html` malgré `#SESSION`

**Fichier** : `squelettes/xml/articles_evenement.html`

Ce template utilise `[(#SESSION{statut}|=={0minirezo}|oui)` pour filtrer le contenu selon le statut de session, mais ne déclare pas `#CACHE{0}`. Si le cache SPIP est actif et que ce template est mis en cache, un utilisateur non connecté pourrait recevoir la réponse d'un utilisateur connecté (et voir des articles réservés aux enseignants).

SPIP détecte normalement `#SESSION` et force le cache à 0 automatiquement, mais l'absence de déclaration explicite est une mauvaise pratique.

**Correction suggérée** : Ajouter `#CACHE{0}` en première ligne, comme dans les autres templates XML du plugin.

---

## Recommandations par priorité

| Priorité | Action |
|----------|--------|
| 🔴 Immédiat | Construire les nœuds DOM avec jQuery dans `swiper.script.html` (N1) |
| 🟠 Court terme | Ajouter la whitelist dans `xml/ajax.html` (N2) |
| 🟠 Court terme | Auto-héberger le worker PDF.js (N3) |
| 🟡 Moyen terme | Échapper `image_fond` dans le contexte CSS (N4) |
| 🟢 Amélioration | Ajouter `#CACHE{0}` explicite dans `articles_evenement.html` (N5) |

---

## Éléments hors périmètre / non vulnérables (confirmés)

- **`article-sauve-coordonnees.html`** : pas d'injection SQL — `#TYPE_OBJET` est whitelisté par `match{article|syndic_article}` et `$id_objet` est revalidé par `sql_getfetsel` avant l'UPDATE.
- **`article-sauve-mot.html`** : pas d'injection SQL — `#TYPE_OBJET` whitelisté à `blogs`, `sql_insertq` utilise des paramètres liés.
- **Formulaires SPIP** (`forum0.html`, `public_editer_article.html`) : protection CSRF assurée par `#ACTION_FORMULAIRE{}`.
- **`ajax.html` `#INCLURE`** : pas de LFI — SPIP résout les chemins uniquement dans les répertoires de squelettes déclarés.
- **PHP pipelines/formulaires** : toutes les valeurs passées à `sql_*` sont protégées par `intval()` ou `sql_quote()`.
- **Clés de session SPIP** affichées dans les templates (ex : `#SESSION{nom}`) : SPIP échappe automatiquement les valeurs de session dans le contexte HTML.
