# Audit de sécurité — Plugin SPIP `thematique`

**Date** : 2026-05-28
**Périmètre** : JavaScript (`squelettes/js/`), squelettes SPIP, templates XML, PHP
**Outil** : Revue manuelle complète du code source

---

## Résumé

| Sévérité  | Nombre |
|-----------|--------|
| Critique  | 1      |
| Élevée    | 2      |
| Moyenne   | 2      |
| Faible    | 2      |
| **Total** | **7**  |

Toutes les vulnérabilités ont été corrigées — voir les commits `0815de9`, `d1874ab`, `ce1f4d6`, `bdedc7e`, `271d1ed` et les commits précédents (`86c0977`, `96a18bd`, `0104155`, `d13af81`).

> **PHP** : `thematique_pipelines.php`, `thematique_autoriser.php`, `thematique_administrations.php`, `formulaires/public_editer_article.php` — aucune injection SQL ; toutes les valeurs passées à `sql_*` sont protégées par `intval()` ou `sql_quote()`.
>
> **Templates ajax** : `noisettes/ajax/article-sauve-coordonnees.html` et `article-sauve-mot.html` — non vulnérables à l'injection SQL (confirmés). Les formulaires SPIP utilisent `#ACTION_FORMULAIRE{}` (CSRF automatique).

---

## Éléments hors périmètre / non vulnérables (confirmés)

- **`article-sauve-coordonnees.html`** : `#TYPE_OBJET` whitelisté par `match{article|syndic_article}` ; `$id_objet` revalidé par `sql_getfetsel` avant l'UPDATE.
- **`article-sauve-mot.html`** : `#TYPE_OBJET` whitelisté à `blogs` ; `sql_insertq` utilise des paramètres liés.
- **Formulaires SPIP** : protection CSRF assurée par `#ACTION_FORMULAIRE{}`.
- **`ajax.html` `#INCLURE`** : pas de LFI — SPIP résout les chemins uniquement dans les répertoires de squelettes déclarés.
- **`#SESSION` dans les templates** : SPIP échappe automatiquement les valeurs de session dans le contexte HTML.
- **`timeline.html` `CCN.urlRoot`/`CCN.urlXml`** : valeurs interpolées sans `json_encode` mais entièrement contrôlées par le serveur (`#DOSSIER_SQUELETTE`, `#CONST`) — non exploitables.
- **`bouton.js` `innerHTML`** : concatène `CCN.urlRoot` (chemin serveur) et `CCN.idRestreint` (entier) — pas d'entrée utilisateur impliquée.
- **`layout.html` ressources CDN** : Swiper et PDF.js chargés sans attribut `integrity` (SRI) — risque de chaîne d'approvisionnement accepté ; la dérivation dynamique du worker PDF.js est en place.
