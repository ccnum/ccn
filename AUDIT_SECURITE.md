# Audit de sécurité — plugins maison CCN

**Date** : 2026-10-05 · **Méthode** : skill `securite-spip` (chasse par plugin, puis un vérificateur
indépendant par candidat qui tente de le réfuter) · **Preuve** : lecture du code uniquement, aucune
requête exécutée.

**Périmètre** : `thematique`, `fictions` (ex-`fictionsv2`), `petitfablab` (ex-`petitfablabv2`),
`ccn`, `cadavrexquis`. Hors périmètre : `*_archive` (jamais activés — ils contiennent encore le bloc PHP
corrigé dans `petitfablab/squelettes/lecture-script.html`), plugins tiers (`plugins/spip/`), cœur.

---

## Bilan

11 vulnérabilités confirmées (2 élevées : exécution de PHP dans des squelettes ; 6 moyennes ; 3
faibles), toutes corrigées le 2026-10-05 avec les durcissements associés — détail dans l'historique
git (`git log --grep="audit 2026-10"` et commits `fix`/`chore` du 2026-10-05).

Aucun candidat confirmé dans `ccn` ni `cadavrexquis`. Aucune injection SQL : toutes les requêtes
relevées passent par `intval`/`sql_quote`/`sql_in`.

À retenir : la traversée de chemin via `mode=../…` sur `page=ajax` est bloquée par `find_in_path`
(`ecrire/inc/utils.php:1689`) ; la protection du dispatcher reste sa liste blanche exacte des `mode`.

---

## Reste à faire (sans vulnérabilité établie)

### thematique
- `inc/thematique_cioidc.php` : le repli par email reste actif quand l'email (non vide) correspond à un autre compte — à retirer une fois les comptes historiques sans login SSO rapprochés (mesure : requête ci-dessous).

```sql
-- Comptes qu'un repli par email pourrait encore viser : email partagé par plusieurs comptes,
-- ou compte dont le login n'est pas un identifiant ENT (ex. VEB64876)
SELECT id_auteur, login, email, statut FROM spip_auteurs
WHERE statut <> '5poubelle' AND email <> ''
  AND (email IN (SELECT email FROM spip_auteurs WHERE email <> '' GROUP BY email HAVING COUNT(*) > 1)
       OR login NOT REGEXP '^[A-Z]{3}[0-9]{5}$');
```

### Transverse
- **Logs SSO complets** : le JSON complet des attributs ENT (identité, classes, groupes, élèves compris) est écrit dans `tmp/log/cioidc.log` à chaque connexion, pour le diagnostic (`plugins/projets/ccn/inc/ccn_cioidc.php`). Activé par défaut ; à couper quand il n'est plus utile via la variable Docker `CCN_CIOIDC_LOG_COMPLET=false`.
- **Cookies applicatifs sans `HttpOnly`** (`thematique/squelettes/js/controleurs.js` `setCookie()`, `main.js` `visited`) : posés et lus par le JS, préférences d'affichage, `SameSite=Strict; Secure` — risque résiduel accepté.
- **`Content-Security-Policy` absente** (les autres en-têtes sont posés dans `Dockerfile`, `spip_headers.conf`) : une CSP stricte suppose de sortir les nombreux `<script>`/`onclick` inline des squelettes.
- **thematique et fictions** : le JavaScript des rédacteurs (profs) reste actif sur le site public (`filtrer_javascript` par défaut, 0). Passé à -1 pour petitfablab seulement ; à étendre si les profs ne collent jamais de code légitime (iframes, scripts d'intégration) dans leurs textes.

---

## Couverture

- **ccn**, **cadavrexquis** : intégralité du PHP et des squelettes (`cadavrexquis` = `paquet.xml` seul).
- **fictions** : actions, formulaires, autorisations, `inc/*`, pipelines, fonctions, options en entier ; rentrée/genie/administrations par recherche ciblée ; squelettes publics principaux en entier, les autres par recherche (`#ENV`, `#EDIT`, `#AUTORISER`, `#SESSION`). Non lus : JS, config crayons.
- **petitfablab** : PHP et formulaires en entier ; `ecriture`, `sommaire`, `lecture-script` en entier ; autres squelettes par recherche. Non lus : `lecture.html`, `edition.html`, `auteur_editer.html`, JS.
- **thematique** : `thematique_autoriser.php`, `thematique_pipelines.php`, `thematique_options.php`, `action/*`, `formulaires/*.php` en entier ; toutes les requêtes SQL de `thematique_fonctions.php` ; dispatchers `ajax`/`json` et fragments atteignables ; recherche `#ENV*`, `<?php`, `#ENV` en JS/attribut sur tout `squelettes/`. Non lus : `squelettes/js/*`, `json/*.html` en détail, `noisettes/sidebar/consigne_pour_*`/`reponse_pour_*` au-delà des chemins suivis, `base/`, `prive/`. Une première passe de chasse s'est interrompue (erreur API) ; la seconde a repris le périmètre sans s'appuyer sur sa couverture.
