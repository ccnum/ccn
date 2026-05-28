# Audit de sécurité — Plugin SPIP `thematique`

**Date** : 2026-05-28
**Périmètre** : JavaScript (`squelettes/js/`), squelettes SPIP, templates XML, PHP
**Outil** : Revue manuelle complète du code source

---

## Résumé

| Sévérité  | Nombre |
|-----------|--------|
| Critique  | 1      |
| Élevée    | 3      |
| Moyenne   | 2      |
| Faible    | 3      |
| **Total** | **9**  |

Toutes les vulnérabilités ont été corrigées — voir les commits `0815de9`, `d1874ab`, `ce1f4d6`, `bdedc7e`, `271d1ed`, `86c0977`, `96a18bd`, `0104155`, `d13af81`, `63d42cd`, `90ab64e`.

| ID  | Sévérité | Statut     | Description courte                                           | Commit(s)  |
|-----|----------|------------|--------------------------------------------------------------|------------|
| S1  | Critique | ✅ Corrigé | XSS DOM massif dans `controleurs.js` (15+ sites)            | `86c0977`  |
| N1  | Élevée   | ✅ Corrigé | XSS DOM — `innerHTML` non échappé dans `controleurs.js`     | `ce1f4d6`  |
| N2  | Élevée   | ✅ Corrigé | LFI potentielle dans `ajax.html` (whitelist ajoutée)         | `d1874ab`  |
| N3  | Élevée   | ✅ Corrigé | Worker PDF.js chargé depuis URL non validée                  | `ce1f4d6`  |
| N4  | Élevée   | ✅ Corrigé | XSS stocké — template literals `consigne.js` / `reponse.js` | `63d42cd`  |
| S2  | Élevée   | ✅ Corrigé | XSS dans `projet.js` et `bouton.js`                         | `86c0977`  |
| S4  | Élevée   | ✅ Corrigé | `$.get` sans CSRF → remplacé par `$.post`                   | `271d1ed`  |
| S5  | Moyenne  | ✅ Corrigé | Cookies sans `Secure` / `SameSite`                           | `96a18bd`  |
| S6  | Moyenne  | ✅ Corrigé | Routage AJAX sans whitelist                                  | `d13af81`  |
| N5  | Faible   | ✅ Corrigé | Cache non désactivé sur template XML d'événements            | `0815de9`  |
| F1  | Faible   | ✅ Corrigé | `console.log` en production dans `main.js`                   | `90ab64e`  |
| S7–S13 | Faible | ✅ Corrigé | Variables globales, entêtes HTTP                            | `96a18bd`  |

---

## Éléments hors périmètre / non vulnérables (confirmés)

- **`article-sauve-coordonnees.html`** : `#TYPE_OBJET` whitelisté par `match{article|syndic_article}` ; `$id_objet` revalidé par `sql_getfetsel` avant l'UPDATE.
- **`article-sauve-mot.html`** : `#TYPE_OBJET` whitelisté à `blogs` ; `sql_insertq` utilise des paramètres liés.
- **Formulaires SPIP** : protection CSRF assurée par `#ACTION_FORMULAIRE{}`.
- **`ajax.html` `#INCLURE`** : pas de LFI — SPIP résout les chemins uniquement dans les répertoires de squelettes déclarés.
- **`#SESSION` dans les templates** : SPIP échappe automatiquement les valeurs de session dans le contexte HTML.
- **`timeline.html` `CCN.urlRoot`/`CCN.urlXml`** : valeurs entièrement contrôlées par le serveur (`#DOSSIER_SQUELETTE`, `#CONST`) — non exploitables.
- **`bouton.js` `innerHTML`** : concatène `CCN.urlRoot` (chemin serveur) et `CCN.idRestreint` (entier) — pas d'entrée utilisateur impliquée.
- **`blankMainSidebar()` dans `controleurs.js`** : tous les appels utilisent des chaînes HTML statiques hardcodées.
- **`layout.html` ressources CDN** : Swiper et PDF.js chargés sans attribut `integrity` (SRI) — risque de chaîne d'approvisionnement accepté ; dérivation dynamique du worker PDF.js en place.
- **PHP** : `thematique_pipelines.php`, `thematique_autoriser.php`, `thematique_administrations.php`, `formulaires/public_editer_article.php` — aucune injection SQL ; toutes les valeurs passées à `sql_*` sont protégées par `intval()` ou `sql_quote()`.
- **`config/_config_cas.php`** : ne contient aucun secret (URL/port SSO uniquement).
