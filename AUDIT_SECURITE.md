# Rapport d'audit de sécurité — Plugin SPIP `thematique` (CCN)

**Date** : 2026-05-29
**Périmètre** : `plugins/thematique/`
**Méthode** : Analyse statique exhaustive — PHP, HTML (squelettes SPIP), JavaScript

---

## Résumé exécutif

| Criticité | Nombre |
|-----------|--------|
| Critique  | 2      |
| Haute     | 5      |
| Moyenne   | 7      |
| Faible    | 4      |

---

## Vulnérabilités critiques

### C1 — Injection SQL via `#ID_OBJET` non casté dans une clause WHERE

**Fichier** : `squelettes/noisettes/ajax/article-sauve-coordonnees.html`, lignes 16–23

```php
$id_objet = #ID_OBJET;
if (($id_objet = sql_getfetsel("id_#TYPE_OBJET", "spip_#TYPE_OBJETs", "id_#TYPE_OBJET=$id_objet"))
```

`#ID_OBJET` est interpolé sans `intval()` directement dans la clause WHERE. Le filtre `#TYPE_OBJET|match{article|syndic_article}` conditionne l'affichage du bloc mais ne protège pas la construction SQL.

**Correction** :
```php
$id_objet = intval('#ID_OBJET');
$type_objet = in_array('#TYPE_OBJET', ['article', 'syndic_article']) ? '#TYPE_OBJET' : null;
if ($type_objet && $id_objet > 0) {
    $id_objet_bdd = sql_getfetsel("id_{$type_objet}", "spip_{$type_objet}s",
        "id_{$type_objet}=" . $id_objet);
}
```

---

### C2 — XSS stocké via `#TEXTE` sans filtre dans plusieurs templates

**Fichiers concernés** :
- `squelettes/noisettes/rubrique_detail.html` ligne 26 : `(#TEXTE)`
- `squelettes/noisettes/groupe_mot.html` ligne 83 : `#TEXTE`
- `squelettes/noisettes/ressources_detail.html` ligne 134 : `[(#TEXTE)]`
- `squelettes/noisettes/groupe_mot_detail.html` ligne 34 : `[(#TEXTE)]`
- `squelettes/noisettes/rubrique.html` ligne 382 : `#TEXTE`

`#TEXTE` produit du HTML après traitement typographique SPIP mais ne neutralise pas les balises `<script>`, attributs `on*` ni les URLs `javascript:` insérées via l'éditeur code source. Un administrateur restreint peut injecter du HTML malveillant visible par tous les visiteurs.

**Correction** : Remplacer par `(#TEXTE|propre)` pour le rendu standard ou `(#TEXTE|textebrut)` si seul le texte brut est attendu.

---

## Vulnérabilités hautes

### H1 — Inclusion de squelette dynamique sans liste blanche

**Fichiers** :
- `squelettes/layout.html` ligne 51 : `<INCLURE{fond=noisettes/#ENV{page}}>`
- `squelettes/xml.html` ligne 3 : `<INCLURE{fond=xml/#ENV{mode}}{env}>`

Le nom du squelette inclus est construit depuis un paramètre d'URL. SPIP ne charge que des `.html` présents sur disque (pas de path traversal), mais cela permet d'énumérer tous les squelettes disponibles et d'en inclure d'inattendus avec effets de bord.

**Correction** :
```spip
[(#ENV{page}|in_array{#LISTE{sommaire,article,rubrique,forum}}|oui)
    <INCLURE{fond=noisettes/#ENV{page}}>
]
```

---

### H2 — XSS via `$.html()` avec données serveur dans `consigne.js`

**Fichier** : `squelettes/js/consigne.js` ligne 113

```javascript
this.div_base.find('.titre').html(this.titre)
```

`$.html()` injecte du HTML brut dans le DOM. Si `this.titre` contient des entités HTML décodées par le parseur XML (`&lt;img onerror=...&gt;` → `<img onerror=...>`), une XSS est déclenchée.

**Correction** : Utiliser `$.text()` pour du contenu textuel :
```javascript
this.div_base.find('.titre').text(this.titre)
```

---

### H3 — XSS DOM via `blankMainSidebar()` dans `controleurs.js`

**Fichier** : `squelettes/js/controleurs.js` ligne 1066

```javascript
function blankMainSidebar(msg) {
    $('#sidebar_main_inner').html('<div class="popup popup_blank">' + message + '</div>');
}
```

La fonction accepte n'importe quel `msg` sans sanitisation. Les appels actuels sont hardcodés (sans risque), mais la fonction est exposée globalement — un futur appel avec une valeur contrôlée par l'utilisateur déclencherait une XSS DOM.

**Correction** : Restreindre à des templates prédéfinis ou sanitiser l'entrée.

---

### H4 — Contrôle d'accès par login hardcodé dans `plan_edition.html`

**Fichier** : `squelettes/plan_edition.html` ligne 24

```spip
{si #SESSION{login}|match{vincent|leroy}|oui}
```

Accès à la page d'administration conditionné par des logins hardcodés dans le code source versionné. Un changement de compte ne bloque pas l'accès. Ce n'est pas le mécanisme standard SPIP.

**Correction** :
```spip
[(#AUTORISER{webmestre}|oui)
    <BOUCLE_secteurs(RUBRIQUES){racine}{par titre}{tout}>
    ...
    </BOUCLE_secteurs>
]
```

---

### H5 — Iframes sans `sandbox` avec URLs fournies par les admins

**Fichiers** :
- `squelettes/noisettes/article.html` lignes 105 et 133
- `squelettes/noisettes/rubrique_detail.html` ligne 91

Les `src` des iframes proviennent de champs SPIP (`URL_SITE`, `DESCRIPTIF*`) sans validation de protocole. Le filtre `|textebrut` ne bloque pas `javascript:`.

**Correction** :
```html
<iframe src="[(#URL_SITE|textebrut)]"
        sandbox="allow-scripts allow-same-origin allow-forms"
        width="100%" height="100%"></iframe>
```
Ajouter aussi une validation que l'URL commence par `https://`.

---

## Vulnérabilités moyennes

### M1 — Concaténation SQL sans `sql_quote()` dans `thematique_administrations.php`

**Fichier** : `thematique_administrations.php` lignes 206, 214

```php
['sm.titre = "' . $mot . '"', 'sr.id_parent = 0']
$id_mot = (int) sql_getfetsel('id_mot', 'spip_mots', 'titre = "' . $mot . '"');
```

`$mot` provient d'un tableau hardcodé (pas d'injection possible actuellement), mais le pattern de concaténation directe est incorrect et risqué si la source évolue.

**Correction** : Utiliser `sql_quote()` systématiquement :
```php
['sm.titre = ' . sql_quote($mot), 'sr.id_parent = 0']
```

---

### M2 — `#ENV{admin}` injecté en JavaScript sans `|intval`

**Fichier** : `squelettes/noisettes/timeline.html` ligne 10

```javascript
CCN.admin = [(#ENV{admin})];
```

Valeur normalement sûre (entier calculé depuis SESSION), mais sans cast elle reste vulnérable si la source du paramètre change.

**Correction** : `CCN.admin = [(#ENV{admin}|intval)];`

---

### M3 — `#ENV{annee_scolaire}` non filtré dans des chaînes JavaScript

**Fichiers** : `squelettes/noisettes/timeline.html` ligne 6, `squelettes/noisettes/menu_haut.html` lignes 9, 11

```javascript
CCN.urlXml = "spip.php?page=xml&annee_scolaire=#ENV{annee_scolaire}&mode=";
```

Valeur lue depuis un cookie client, injectée dans une chaîne JS sans encodage. Un cookie forgé pourrait injecter du code.

**Correction** :
```javascript
CCN.urlXml = "spip.php?page=xml&annee_scolaire=[(#ENV{annee_scolaire}|json_encode)]&mode=";
```

---

### M4 — Absence d'attributs SRI sur les scripts CDN externes

**Fichier** : `squelettes/layout.html` lignes 43–47

```html
<script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
```

Aucun attribut `integrity` (SRI). Si le CDN est compromis, du code arbitraire s'exécute sur toutes les pages.

**Correction** : Générer les hashes SRI et les ajouter, ou héberger les bibliothèques localement dans `squelettes/js/bundled/`.

---

### M5 — Fuite d'adresses email dans les logs SPIP

**Fichier** : `thematique_pipelines.php` lignes 123, 142

```php
spip_log('les auteurs ' . $ar['email'], 'thematique');
```

Adresses email des enseignants écrites en clair dans `tmp/log/thematique.log`. Si ce répertoire est accessible depuis le web, ces données sont divulguées.

**Correction** : Logger uniquement l'`id_auteur`, ou supprimer ces logs de débogage.

---

### M6 — Cookies applicatifs sans `HttpOnly`

**Fichier** : `squelettes/js/controleurs.js` ligne 919

```javascript
document.cookie = cookie_nom + "=" + encodeURIComponent(cookie_valeur) + "; SameSite=Strict; Secure";
```

`SameSite=Strict` et `Secure` sont bien présents, mais `HttpOnly` est absent. En cas de XSS résiduelles, ces cookies (préférences d'affichage) seraient lisibles par du code malveillant.

---

### M7 — `#DESCRIPTIF*` (raw) utilisé comme `src` d'iframes sans validation

**Fichiers** : `squelettes/noisettes/rubrique_detail.html` ligne 80, `squelettes/noisettes/article.html` ligne 21

Le descriptif brut d'un objet SPIP est parsé ligne par ligne pour alimenter des iframes sans validation du protocole des URLs.

**Correction** : Valider que chaque ligne commence par `https://` avant injection.

---

## Vulnérabilités faibles

### F1 — Page `plan_edition.html` cachée 3600 s sans auth forte

La page expose l'arborescence complète (IDs, titres, liens) avec un cache long. Voir H4 pour la correction du contrôle d'accès.

---

### F2 — `innerHTML` avec `CCN.urlRoot` dans `bouton.js`

**Fichier** : `squelettes/js/bouton.js` lignes 17, 28

`CCN.urlRoot` provient de `#ENV{chemin}` (chemin serveur, normalement sûr), mais est injecté via `innerHTML`. Préférer la création d'éléments DOM :
```javascript
$('<img>').attr('src', CCN.urlRoot + 'img/reponse_plus.png')
```

---

### F3 — Absence d'en-têtes de sécurité HTTP

Aucun `Content-Security-Policy`, `X-Frame-Options`, `X-Content-Type-Options`, ni `Referrer-Policy` n'est défini dans le plugin ou la configuration serveur visible. Ces en-têtes doivent être ajoutés au niveau nginx/apache ou via `#HTTP_HEADER` dans les squelettes.

---

### F4 — Commentaires HTML exposant la structure interne dans les flux XML

**Fichier** : `squelettes/xml/consignes.html` lignes 5–8

```html
<!-- #ID_RUBRIQUE-->
<!-- date début : #CONST{_date_debut} -->
```

Des IDs et constantes internes sont exposés dans des commentaires HTML publics. Supprimer ces commentaires de débogage.

---

## Points positifs

- **CSRF** : Formulaires SPIP avec `#ACTION_FORMULAIRE` (token anti-CSRF). Actions destructrices avec `#URL_ACTION_AUTEUR` (signature cryptographique).
- **Contrôle d'accès** : Les noisettes AJAX sensibles vérifient `#SESSION{statut}` ET `#AUTORISER{modifier,...}`.
- **Échappement JS** : `escHtml()` définie dans `globales.js` et utilisée dans `reponse.js` pour les template literals.
- **PHP** : `thematique_pipelines.php` et les formulaires PHP utilisent `intval()` et `sql_quote()`.
- **Cookies** : `SameSite=Strict` et `Secure` présents.
- **Open Redirect** : `reload()` dans `controleurs.js` valide que l'URL commence par `/` ou l'origine courante.
- **Flux XML** : `|entites_html` appliqué sur les titres avant envoi au JavaScript.

---

## Récapitulatif par fichier

| Fichier | Vulnérabilités |
|---------|----------------|
| `squelettes/noisettes/ajax/article-sauve-coordonnees.html` | C1 |
| `squelettes/noisettes/rubrique_detail.html` | C2, M7 |
| `squelettes/noisettes/groupe_mot.html` | C2 |
| `squelettes/noisettes/ressources_detail.html` | C2 |
| `squelettes/noisettes/groupe_mot_detail.html` | C2 |
| `squelettes/noisettes/rubrique.html` | C2 |
| `squelettes/js/consigne.js` | H2 |
| `squelettes/js/controleurs.js` | H3, M6 |
| `squelettes/plan_edition.html` | H4, F1 |
| `squelettes/noisettes/article.html` | H5, M7 |
| `squelettes/layout.html` | H1, M4 |
| `squelettes/xml.html` | H1 |
| `squelettes/noisettes/timeline.html` | M2, M3 |
| `squelettes/noisettes/menu_haut.html` | M3 |
| `thematique_pipelines.php` | M5 |
| `thematique_administrations.php` | M1 |
| `squelettes/js/bouton.js` | F2 |
| `squelettes/xml/consignes.html` | F4 |
