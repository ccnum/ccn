# Audit de sécurité — Plugin SPIP `thematique`

**Date** : 2026-05-27
**Périmètre** : JavaScript (`squelettes/js/`), squelettes SPIP (`squelettes/`), formulaires, pipelines PHP
**Outil** : Revue manuelle du code source

---

## Résumé

| Sévérité  | Nombre |
|-----------|--------|
| Critique  | 3      |
| Élevée    | 4      |
| Moyenne   | 3      |
| Faible    | 3      |
| **Total** | **13** |

> Les faux-positifs signalés par des outils automatiques ont été écartés après vérification :
> les templates `article-sauve-coordonnees.html` et `article-sauve-mot.html` sont protégés par des
> whitelists SPIP (`match{article|syndic_article}`) et `sql_insertq` paramétré — aucune injection SQL.
> Les formulaires SPIP utilisent `#ACTION_FORMULAIRE{}` qui génère un token CSRF automatique.

---

## Critique

### S1 — XSS DOM : transformation `[ ]` → `< >` + insertion dans `.html()`

**Fichiers** : `squelettes/js/main.js` lignes 160, 267, 332, 391

```js
dataForConsigne.titre = dataForConsigne.titre.replaceAll("[", "<").replaceAll("]", ">");
dataForReponse.titre  = dataForReponse.titre.replaceAll("[", "<").replaceAll("]", ">");
dataForArticleBlog.titre = dataForArticleBlog.titre.replaceAll("[", "<").replaceAll("]", ">");
dataForEvenement.titre   = dataForEvenement.titre.replaceAll("[", "<").replaceAll("]", ">");
```

Ces données sont ensuite injectées via `.html()` dans le DOM (consigne.js:84, reponse.js:63,
article_blog.js:44, article_evenement.js:52) sans aucun échappement supplémentaire.

**Risque** : Tout utilisateur ayant le droit d'éditer un titre dans SPIP (enseignant, admin restreint)
peut y écrire `[script]alert(document.cookie)[/script]`. Ce contenu est converti en
`<script>alert(document.cookie)</script>` puis injecté dans le DOM de tous les visiteurs.

**Correction suggérée** : Remplacer `.html()` par `.text()` pour les données textuelles pures, ou
utiliser `document.createElement` + `.textContent` pour les nœuds qui ne nécessitent pas de HTML.
Si du HTML limité est voulu, utiliser une whitelist (DOMPurify).

---

### S2 — XSS DOM : données XML serveur non échappées dans `.html()`

**Fichiers** :
- `squelettes/js/consigne.js` lignes 84–94 : `this.titre`, `this.data.image`, `this.intervenant_nom`
- `squelettes/js/reponse.js` lignes 63–71 : `this.titre`, `this.nom_classe`, `vignette`
- `squelettes/js/article_blog.js` lignes 43–53 : `this.titre`
- `squelettes/js/article_evenement.js` lignes 45–58 : `this.titre`

```js
// Exemple dans consigne.js
this.div_titre.html(
    "<div class=\"photo\"><img src=\"" + this.data.image + "\" /></div>" +
    "<div class=\"titre\">" + this.titre + "</div>"
);
```

**Risque** : Ces valeurs proviennent du XML SPIP. Si un champ `image` ou `titre` en base contient
`" onerror="alert(1)` ou du HTML arbitraire, il sera injecté brut dans le DOM.
Ce vecteur est aggravé par S1 (la transformation des crochets amplifie la surface).

**Correction suggérée** : Construire les nœuds DOM avec jQuery (`.attr()`, `.text()`) plutôt que par
concaténation de chaînes dans `.html()`.

```js
// Avant
this.div_titre.html('<div class="titre">' + this.titre + '</div>');

// Après
const divTitre = $('<div class="titre">').text(this.titre);
this.div_titre.append(divTitre);
```

---

### S3 — XSS Reflected : variables ENV injectées dans `<script>` sans échappement JS

**Fichier** : `squelettes/noisettes/timeline.html` lignes 12–21

```html
<script language="Javascript">
    CCN.typeRestreint       = '[(#ENV{type_restreint})]';
    CCN.dateToShowAtInit    = '[(#ENV{vue_date,0})]';
    CCN.typePopupToShowAtInit = '[(#ENV{type_objet,0})]';
    CCN.idObjetToShowAtInit = '[(#ENV{id_objet,0})]';
    CCN.pageToShowAtInit    = '[(#SELF|parametre_url{page})]';
    CCN.idRubriqueToShowAtInit    = '[(#SELF|parametre_url{id_rubrique})]';
    CCN.idArticleToShowAtInit     = '[(#SELF|parametre_url{id_article})]';
    CCN.idSyndicArticleToShowAtInit = '[(#SELF|parametre_url{id_syndic_article})]';
</script>
```

SPIP encode les entités HTML (`<`, `>`, `"`) mais **ne protège pas contre l'injection dans un
contexte JavaScript**. Le caractère `\` n'est pas échappé. En conséquence :

- `?type_objet=\\'; alert(document.cookie); //` produit dans le script rendu :
  `CCN.typePopupToShowAtInit = '\\'; alert(document.cookie); //'`
- Le `\\` est un backslash littéral, la quote `'` ferme la chaîne JS, et `alert(...)` s'exécute.

**Vecteur** : lien forgé envoyé à un utilisateur connecté → XSS reflected → vol de session SPIP.

**Correction suggérée** : Utiliser `|json_encode` (filtre SPIP) pour les valeurs interpolées dans JS.

```html
CCN.typePopupToShowAtInit = [(#ENV{type_objet,0}|json_encode)];
```

---

## Élevée

### S4 — Opérations d'écriture en base via requête GET (absence de protection CSRF)

**Fichiers** :
- `squelettes/js/consigne.js:132`
- `squelettes/js/reponse.js:112`
- `squelettes/js/article_blog.js:79`
- `squelettes/js/article_evenement.js:85`

```js
$.get("spip.php?page=ajax&mode=article-sauve-coordonnees",
    { id_objet: _thisId, type_objet: "article", X: 0, Y: yy });
```

Le verbe HTTP GET ne doit jamais provoquer d'effets de bord. Ces requêtes modifient les colonnes `X`
et `Y` en base de données. Un attaquant peut déclencher ces modifications à distance via une balise
`<img>` sur un site tiers, sans que la victime connectée ne le remarque.

**Correction suggérée** : Passer à `$.post()` avec le token CSRF SPIP (`spip_action` ou le token
formulaire) ; ou utiliser un nonce one-time côté serveur.

---

### S5 — Cookie applicatif créé sans flags de sécurité

**Fichier** : `squelettes/js/controleurs.js:908`

```js
function reloadAndSetCookie(url, cookie_nom, cookie_valeur) {
    document.cookie = cookie_nom + "=" + cookie_valeur;
    reload(url + '/?rub=' + cookie_valeur);
}
```

Le cookie est créé sans :
- `HttpOnly` → lisible et modifiable par JavaScript (vol par XSS)
- `Secure` → transmis en clair sur HTTP
- `SameSite=Strict` → envoyé lors de requêtes cross-site (CSRF)

Le même problème existe pour le cookie de visite dans `main.js:491–494`.

**Correction suggérée** :
```js
document.cookie = `${cookie_nom}=${encodeURIComponent(cookie_valeur)}; SameSite=Strict; Secure`;
```
Pour les cookies sensibles, les créer côté serveur avec `HttpOnly`.

---

### S6 — Endpoints AJAX exposés sans whitelist exhaustive

**Fichier** : `squelettes/ajax.html:15–17`

```
[(#MODE|match{timeline|classe}|non)
    <INCLURE{fond=noisettes/ajax/#MODE}{env}>
]
```

La logique est inversée : tout `MODE` qui ne correspond **pas** à `timeline` ou `classe` est routé
vers `noisettes/ajax/#MODE`. Cela expose automatiquement tout nouveau fichier créé sous
`noisettes/ajax/` sans qu'aucune décision explicite ne soit prise.

Actuellement exposés : `article-sauve-coordonnees`, `article-sauve-mot`, `article-forum-detail`.
Chacun a ses propres contrôles (`#AUTORISER`), mais l'architecture crée un risque d'exposition
accidentelle.

**Correction suggérée** : Utiliser une whitelist explicite des modes autorisés.

```
[(#MODE|match{article-sauve-coordonnees|article-sauve-mot|article-forum-detail}|oui)
    <INCLURE{fond=noisettes/ajax/#MODE}{env}>
]
```

---

### S7 — Open redirect non validé

**Fichier** : `squelettes/js/controleurs.js:919–925`

```js
function reload(url) {
    if (url == 'self') {
        location.reload(true);
    } else {
        window.location.href = url;
    }
}
```

`url` est passé par `reloadAndSetCookie(url, ...)`. Si ce paramètre peut être influencé par un
attaquant (via XSS ou une autre entrée), la redirection peut pointer vers un domaine arbitraire.

**Correction suggérée** : Valider que l'URL est relative ou appartient au même domaine avant
d'appliquer la redirection.

```js
function reload(url) {
    if (url === 'self') {
        location.reload(true);
    } else if (url.startsWith('/') || url.startsWith(window.location.origin)) {
        window.location.href = url;
    }
}
```

---

## Moyenne

### S8 — CSS Selector injection via fragment d'URL (`CCN.hash`)

**Fichier** : `squelettes/js/controleurs.js:881`

```js
// timeline.html:22
CCN.hash = (hash.length > 0) ? hash.slice(1) : '';

// controleurs.js:881
const anchor = $("#" + CCN.hash);
```

`CCN.hash` est dérivé de `window.location.hash` (fragment d'URL, contrôlé par le client).
La valeur est passée directement à `$()` comme sélecteur CSS. Selon la version de jQuery,
un hash contenant des caractères spéciaux (`[`, `]`, `.`, `#`) peut entraîner des erreurs
silencieuses ou un comportement non intentionnel du sélecteur.

**Correction suggérée** :
```js
// Valider que le hash ne contient que des caractères sûrs pour un id HTML
if (/^[\w-]+$/.test(CCN.hash)) {
    const anchor = $("#" + CCN.hash);
}
```

---

### S9 — Injection de métacaractères RegExp dans `$.urlParam`

**Fichier** : `squelettes/js/controleurs.js:6`

```js
$.urlParam = function (name) {
    const results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
    ...
}
```

`name` est injecté sans échappement dans une expression régulière. Si cette fonction est appelée
avec une valeur contrôlée par l'utilisateur contenant des métacaractères regex (`(`, `)`, `+`,
`*`, `{`…), le comportement est imprévisible (résultats erronés, voire ReDoS avec une chaîne
crafée).

**Correction suggérée** :
```js
$.urlParam = function (name) {
    const escapedName = name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const results = new RegExp('[?&]' + escapedName + '=([^&#]*)').exec(window.location.href);
    ...
}
```

---

### S10 — `for...in` sur un objet d'état (prototype pollution)

**Fichier** : `squelettes/js/controleurs.js:120–125`

```js
for (const index in state) {
    if (state[index] != currentState[index]) {
        isSamePage = false;
        break;
    }
}
```

`for...in` itère sur toutes les propriétés énumérables, **y compris celles héritées de
`Object.prototype`**. Si une bibliothèque tierce ou une extension navigateur pollue le prototype,
la comparaison `isSamePage` peut être contournée, déclenchant des rechargements ou des
comportements inattendus.

**Correction suggérée** :
```js
for (const index of Object.keys(state)) {
    ...
}
```

---

## Faible

### S11 — Exposition de détails techniques dans la console navigateur

**Fichier** : `squelettes/js/controleurs.js:948–953`

```js
console.error("Erreur de chargement :", xhr.status, xhr.statusText);
console.warn("Réponse vide !");
```

Les codes d'erreur HTTP et messages exposés dans la console facilitent la reconnaissance pour un
attaquant (identification des endpoints valides, codes d'état, structure interne).

**Correction suggérée** : Supprimer ou conditionner ces logs à un mode debug (`CCN.debug === true`).

---

### S12 — Cookie de visite sans flags de sécurité

**Fichier** : `squelettes/js/main.js:491–494`

```js
document.cookie = "visited=true; expires=" + expires.toUTCString();
```

Même problème que S5 : pas de `Secure`, `SameSite`, ni `HttpOnly`. Ce cookie est modifiable par
JavaScript et transmissible en clair.

---

### S13 — IDs non validés numériquement côté client avant envoi AJAX

**Fichiers** : multiple call-sites dans `controleurs.js`

```js
const url = CCN.projet.url_popup_consigne + "&id_article=" + id_consigne;
```

Les identifiants `id_consigne`, `id_reponse`, `id_classe` sont passés dans les URLs AJAX sans
vérification `parseInt()` / `isNaN()` côté client. La validation est présente côté serveur
(SPIP + `intval()`), mais un contrôle client ajouterait une couche de défense.

**Correction suggérée** :
```js
if (!Number.isInteger(Number(id_consigne))) return;
```

---

## Recommandations par priorité

| Priorité | Action |
|----------|--------|
| 🔴 Immédiat | Remplacer `.html(chaîne_concaténée)` par construction DOM jQuery (S1, S2) |
| 🔴 Immédiat | Utiliser `\|json_encode` pour les variables ENV dans `<script>` (S3) |
| 🟠 Court terme | Passer les sauvegardes de coordonnées en POST avec token CSRF (S4) |
| 🟠 Court terme | Ajouter les flags `Secure`, `SameSite=Strict` sur tous les cookies JS (S5, S12) |
| 🟠 Court terme | Convertir `ajax.html` en whitelist explicite des modes (S6) |
| 🟡 Moyen terme | Valider `url` avant redirection dans `reload()` (S7) |
| 🟡 Moyen terme | Sanitiser `CCN.hash` avant usage dans `$()` (S8) |
| 🟡 Moyen terme | Échapper `name` dans `$.urlParam` (S9) |
| 🟢 Amélioration | Remplacer `for...in` par `Object.keys()` dans `setContentFromState` (S10) |
| 🟢 Amélioration | Conditionner les `console.error/warn` à un flag debug (S11) |

---

## Éléments hors périmètre / non vulnérables (confirmés)

- **`article-sauve-coordonnees.html`** : pas d'injection SQL — `#TYPE_OBJET` est whitelisté par
  `match{article|syndic_article}` et `$id_objet` est revalidé par `sql_getfetsel` avant l'UPDATE.
- **`article-sauve-mot.html`** : pas d'injection SQL — `#TYPE_OBJET` whitelisté à `blogs`,
  `sql_insertq` utilise des paramètres liés.
- **Formulaires SPIP** (`forum0.html`, `public_editer_article.html`) : protection CSRF assurée
  par la balise `#ACTION_FORMULAIRE{}` qui génère un token one-time automatique.
- **`ajax.html` `#INCLURE`** : pas de LFI au sens classique — SPIP résout les chemins de templates
  uniquement dans les répertoires de squelettes déclarés (pas d'accès au système de fichiers).
