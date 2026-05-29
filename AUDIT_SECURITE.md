# Rapport d'audit de sécurité — Plugin SPIP `thematique` (CCN)

**Date** : 2026-05-29
**Périmètre** : `plugins/thematique/`
**Méthode** : Analyse statique exhaustive — PHP, HTML (squelettes SPIP), JavaScript
**Statut** : ✅ Toutes les vulnérabilités corrigées (commit `0c6bec4`)

---

## Résumé

| Criticité | Nombre | Statut |
|-----------|--------|--------|
| Critique  | 2      | ✅ Corrigé |
| Haute     | 5      | ✅ Corrigé |
| Moyenne   | 7      | ✅ Corrigé (M6 non applicable) |
| Faible    | 4      | ✅ Corrigé (F1 via H4, F3 hors périmètre plugin) |

---

## Vulnérabilités critiques — corrigées

### ✅ C1 — Injection SQL via `#ID_OBJET` non casté

**Fichier** : `squelettes/noisettes/ajax/article-sauve-coordonnees.html`
**Correction** : `intval()` sur `#ID_OBJET` + liste blanche PHP sur `#TYPE_OBJET` via `in_array()`.
**Commit** : `cbb1dcf`

---

### ✅ C2 — XSS stocké via `#TEXTE` sans filtre

**Fichiers** : `rubrique_detail.html`, `groupe_mot.html`, `ressources_detail.html`, `groupe_mot_detail.html`, `rubrique.html`
**Correction** : Filtre `|propre` ajouté sur toutes les occurrences de `(#TEXTE)` hors contexte `#EDIT` (les spans Crayon conservent le brut pour l'éditeur inline).
**Commit** : `cbb1dcf`

---

## Vulnérabilités hautes — corrigées

### ✅ H1 — Inclusion de squelette dynamique sans liste blanche

**Fichiers** : `layout.html`, `xml.html`
**Correction** : Validation `|in_array{#LISTE{...}}` avant l'inclusion. Fallback sur `sommaire` si valeur non reconnue.
**Commit** : `700bbe9`

---

### ✅ H2 — XSS via `$.html()` dans `consigne.js`

**Fichier** : `squelettes/js/consigne.js` ligne 113
**Correction** : Remplacement de `.html(this.titre)` par `.text(this.titre)`.
**Commit** : `3acfcc6`

---

### ✅ H3 — XSS DOM via `blankMainSidebar()` dans `controleurs.js`

**Fichier** : `squelettes/js/controleurs.js`
**Correction** : La fonction n'accepte plus de HTML arbitraire. Elle reçoit une clé (`'travail_en_cours'`, `'livrables'`, `'ressources'`, `'agora'`) et construit le HTML depuis un dictionnaire interne de templates. Les 4 call-sites ont été mis à jour.
**Commit** : `3acfcc6`

---

### ✅ H4 — Contrôle d'accès par login hardcodé dans `plan_edition.html`

**Fichier** : `squelettes/plan_edition.html`
**Correction** : Remplacement du `|match{vincent|leroy}` par `#AUTORISER{webmestre}` (mécanisme standard SPIP).
**Commit** : `700bbe9`

---

### ✅ H5 — Iframes sans `sandbox`

**Fichiers** : `squelettes/noisettes/article.html`, `squelettes/noisettes/rubrique_detail.html`
**Correction** : Attribut `sandbox="allow-scripts allow-same-origin allow-forms allow-popups"` ajouté sur toutes les iframes. Validation `|match{^https?://}` ajoutée sur les URLs issues de `#DESCRIPTIF*` (couvre aussi M7).
**Commit** : `e9fcb88`

---

## Vulnérabilités moyennes — corrigées

### ✅ M1 — Concaténation SQL sans `sql_quote()` dans `thematique_administrations.php`

**Correction** : Remplacement des concaténations directes par `sql_quote($mot)`.
**Commit** : `51c8632`

---

### ✅ M2 — `#ENV{admin}` injecté en JavaScript sans `|intval`

**Fichier** : `squelettes/noisettes/timeline.html`
**Correction** : `[(#ENV{admin}|intval)]`
**Commit** : `51c8632`

---

### ✅ M3 — `#ENV{annee_scolaire}` non filtré dans des chaînes JavaScript

**Fichier** : `squelettes/noisettes/timeline.html`
**Correction** : `encodeURIComponent([(#ENV{annee_scolaire}|json_encode)])`
**Commit** : `51c8632`

---

### ✅ M4 — Absence d'attributs SRI sur les scripts CDN externes

**Fichier** : `squelettes/layout.html`
**Correction** : Attributs `integrity` (SHA-384) et `crossorigin="anonymous"` ajoutés sur Swiper CSS, Swiper JS et PDF.js.
**Commit** : `cc2253e`

---

### ✅ M5 — Fuite d'adresses email dans les logs SPIP

**Fichier** : `thematique_pipelines.php`
**Correction** : Le `sql_select` récupère désormais `id_auteur` en plus de `email`. Les logs utilisent `id_auteur` uniquement, sans exposer l'adresse email.
**Commit** : `cc2253e`

---

### ⚠️ M6 — Cookies applicatifs sans `HttpOnly` (non applicable)

**Fichier** : `squelettes/js/controleurs.js`
Les cookies (`laclasse_annee_scolaire`, etc.) sont définis depuis JavaScript pour stocker des préférences d'affichage. `HttpOnly` est incompatible avec des cookies gérés par JS. Ils contiennent exclusivement des préférences UI (pas de session, pas de token). `SameSite=Strict` et `Secure` sont déjà présents. Risque résiduel acceptable.

---

### ✅ M7 — `#DESCRIPTIF*` utilisé comme `src` d'iframes sans validation d'URL

**Correction** : Filtre `|match{^https?://}` appliqué avant injection dans `src`. Couvert par H5.
**Commit** : `e9fcb88`

---

## Vulnérabilités faibles — corrigées

### ✅ F1 — Page `plan_edition.html` sans auth forte

**Correction** : Couverte par H4 (`#AUTORISER{webmestre}`). **Commit** : `700bbe9`

---

### ✅ F2 — `innerHTML` avec `CCN.urlRoot` dans `bouton.js`

**Fichier** : `squelettes/js/bouton.js`
**Correction** : Remplacement par création d'éléments DOM (`createElement`, `appendChild`, `addEventListener`).
**Commit** : `0c6bec4`

---

### ⚠️ F3 — Absence d'en-têtes de sécurité HTTP

Hors périmètre du plugin — à configurer au niveau nginx/apache (`Content-Security-Policy`, `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`).

---

### ✅ F4 — Commentaires HTML exposant la structure interne dans les flux XML

**Fichier** : `squelettes/xml/consignes.html`
**Correction** : Suppression des 4 commentaires de debug (`#ID_RUBRIQUE`, `_date_debut`, `_date_fin`, `#DATE`).
**Commit** : `0c6bec4`

---

## Points positifs (inchangés)

- **CSRF** : `#ACTION_FORMULAIRE` et `#URL_ACTION_AUTEUR` sur tous les formulaires.
- **Contrôle d'accès** : `#SESSION{statut}` + `#AUTORISER{modifier,...}` sur les noisettes AJAX sensibles.
- **Échappement JS** : `escHtml()` dans `globales.js` utilisée dans `reponse.js`.
- **PHP** : `intval()` et `sql_quote()` sur les entrées externes.
- **Open Redirect** : `reload()` valide que l'URL commence par `/` ou l'origine courante.
- **Flux XML** : `|entites_html` appliqué sur les titres.
