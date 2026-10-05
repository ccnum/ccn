# Audit de sécurité — plugins maison CCN

**Date** : 2026-10-05 · **Méthode** : skill `securite-spip` (chasse par plugin, puis un vérificateur
indépendant par candidat qui tente de le réfuter) · **Preuve** : lecture du code uniquement, aucune
requête exécutée.

**Périmètre** : `thematique`, `fictions` (ex-`fictionsv2`), `petitfablab` (ex-`petitfablabv2`),
`ccn`, `cadavrexquis`. Hors périmètre : `*_archive` (jamais activés — mais `fictions_archive` et
`petitfablab_archive` contiennent le même bloc PHP que P1), plugins tiers (`plugins/spip/`), cœur.

---

## Vulnérabilités confirmées — toutes corrigées le 2026-10-05

| Id | Gravité | Plugin | Titre | Sink | Correctif |
|----|---------|--------|-------|------|-----------|
| T1 ✅ corrigé (`7db933e1`) | **Élevée** | thematique | Exécution de PHP : `#ENV{X}`/`#ENV{Y}` écrits dans un bloc `<?php` du squelette, évalué par SPIP. Atteignable par tout rédacteur pouvant modifier un article, ou par lien GET piégé (pas de jeton). | `squelettes/noisettes/ajax/article-sauve-coordonnees.html:18-19` | Supprimer le PHP du squelette ; action `action/thematique_sauver_coordonnees.php` avec `securiser_action()`, `autoriser('modifier', …)`, liste blanche du type, `floatval` sur X/Y, `sql_updateq`. |
| P1 ✅ corrigé (`affcbf1c`) | **Élevée** | petitfablab | Exécution de PHP : `$redirect="#SELF";` dans un bloc `<?php` ; `self()` laisse passer `$ { } ( )`, donc `${…}` est interpolé. Exécuté pour un compte pouvant modifier l'article (ou via lien piégé) ; un anonyme peut seulement faire planter la page. | `squelettes/lecture-script.html:27` | Supprimer le bloc PHP : `[(#AUTORISER{modifier,article,#ID_ARTICLE}) <a href="[(#URL_ACTION_AUTEUR{dissocier_document,#ID_ARTICLE-article-#ID_DOCUMENT-suppr-safe,#SELF})]" …>]`. |
| T2 ✅ corrigé (`3819d631`) | Moyenne | thematique | XSS réfléchie anonyme : `page=ajax` filtre `mode` par une regex non ancrée (`timeline\|classe`) et inclut le fragment avec `{env}` ; `header_presentation_classe` affiche `#ENV*{description_classe}` brut. | `squelettes/noisettes/sidebar/page_participant/header_presentation_classe.html:18` (entrée `squelettes/ajax.html:9-10`) | Liste blanche exacte des `mode` (`in_array`, comme `json.html`) ; ne pas lire `description_classe` dans l'ENV du fragment. |
| T3 ✅ corrigé (`3819d631`) | Moyenne | thematique | XSS réfléchie (prof/admin, au clic) : `createReponse( #ENV{id_article}, … )` hors chaîne JS ; `id_article` arrive brut via `page=ajax&mode=sidebar/consigne_pour_classe`. | `squelettes/noisettes/sidebar/onglet_description_commun.html:84` | `[(#GET{id_article}\|intval)]` ou `#ID_ARTICLE` ; `intval` dès le `#SET` dans `onglet_description_mission_tab.html` ; liste blanche (T2). |
| T4 ✅ corrigé (`b2464d94`) | Moyenne | thematique | SSO : si l'uid ne correspond à aucun login, l'auteur est cherché par email — même vide. À la 1re connexion, statut, webmestre, nom, avatar et liens de rubriques d'un **autre** compte (ex. premier auteur à email vide, créé ainsi par cioidc) sont écrasés. | `inc/thematique_cioidc.php:21` (écritures `thematique_pipelines.php:242-301`) | Pas de repli si `$email === ''`, exclure `5poubelle` ; idéalement ne modifier que le compte authentifié par cioidc (après `cioidc_session`). |
| T5 ✅ corrigé (`a0cf2b41`) | Moyenne | thematique | En édition, un prof/intervenant change `id_parent` (champ caché) : son article est déplacé dans n'importe quelle rubrique (autre classe, Consignes, autre année) puis publié grâce à l'`autoriser_exception('publierdans')`. | `formulaires/public_publier_article.php:325-332` (vérif. l.141, 163) | En édition, forcer `set_request('id_parent', <rubrique actuelle>)` dans `verifier()`/`traiter()`, ou refuser si `thematique_auteur_peut_creer_dans_rubrique()` est faux. |
| T6 ✅ corrigé (`a0cf2b41`) | Moyenne | thematique | `charger()` préremplit titre/texte de la réponse d'une classe à une mission sans filtre de statut ni contrôle de session : un anonyme lit les réponses non publiées (dépubliées, `prepa`…) via `?page=publier&id_rubrique=…&id_consigne=…`. | `formulaires/public_publier_article.php:71-83` (requête `thematique_fonctions.php:2639-2643`) | Ne préremplir que si `autoriser('modifier','article',$reponse['id_article'])`. |
| P2 ✅ corrigé (`89f48527`) | Moyenne | petitfablab | `creer_histoire` : tout compte connecté (élève compris) crée des rubriques « Histoire N » publiées sous une rubrique au choix (`?creer=<id>`, `prologue[]` libre). Aucun `autoriser()`, pas de `charger()`. | `squelettes/formulaires/creer_histoire.php:49-59` | `autoriser('creerrubriquedans','rubrique',$rub_parent)` (ou rôle) dans `charger()`/`traiter()` ; vérifier que chaque prologue appartient à `$rub_parent`. |
| F1 ✅ corrigé (`d3de1c4f`) | Faible | fictions | Les règles d'écriture (chapitre courant, période ouverte) ne sont appliquées que par les squelettes : le droit natif `modifier article` laisse un participant éditer son chapitre `prepa`/`prop` hors calendrier (crayons ou `ecrire/`). | `fictions_autoriser.php` (pas de surcharge de `autoriser_article_modifier`) | Surcharger `autoriser_article_modifier` : pour un chapitre d'histoire, exiger `autoriser('ecrirechapitre', …)` sauf admin non restreint. |
| P3 ✅ corrigé (`7da6cab4`) | Faible | petitfablab | CSRF : la publication d'un chapitre (et l'envoi de mails, voire `creer_histoire` au 5e) est déclenchée par un simple GET de `?page=ecriture&id_article=N`, sans jeton. Limité aux brouillons de la victime. | `petitfablab_fonctions.php:22-24` (appel `squelettes/ecriture.html:9-17`) | Passer par un CVT ou une action `securiser_action()` ; rien sur GET. |
| T7 ✅ corrigé (`5745d601`) | Faible | thematique | `thematique_donner_role()` renvoie `admin` pour tout `0minirezo`, restreint compris : publication dans toute rubrique et suppression de tout commentaire. Le SSO ne crée jamais de restreint — seuls des comptes créés à la main sont concernés. | `thematique_fonctions.php:343-346` | Ne renvoyer `admin` que pour un admin non restreint ; sinon `admin_restreint` (même défaut dans `sidebar_profil()` l.262-266). |

Correctifs testés sur les instances ddev (sauf T5 et le bouton « Répondre » de T3, qui demandent une session prof, et la page `lecture-script` de petitfablab, seulement compilée). Les rôles et sessions déjà ouverts gardent l'ancien rôle jusqu'à la prochaine écriture de session (T7).

Aucun candidat confirmé dans `ccn` ni `cadavrexquis`. Aucune injection SQL : toutes les requêtes
relevues passent par `intval`/`sql_quote`/`sql_in`.

Rejeté, à retenir : la traversée de chemin via `mode=../…` sur `page=ajax` est bloquée par
`find_in_path` (`ecrire/inc/utils.php:1689`) — le problème du dispatcher est l'absence de liste
blanche (T2/T3), pas l'accès hors `noisettes/`.

---

## Durcissement restant (sans vulnérabilité établie)

Les durcissements mécaniques ont été appliqués le 2026-10-05 (commits `88791dbb` à `0efda68b`). Restent les points qui demandent une décision :

### thematique
- `formulaires/public_publier_article.php` : l'exception `publierdans` permet à un auteur de republier un article dépublié par un admin (politique de modération à trancher).
- `thematique_autoriser.php` (`autoriser_article_modifier`, #468) : l'exception jalon vaut pour tout intervenant sur tout jalon non publié, sans test d'appartenance au projet.
- `formulaires/forumv2.php:174` : `auteur` = `nom_auteur` libre (masqué à l'affichage par la jointure sur `id_auteur`).
- `inc/thematique_cioidc.php` (T4) : le repli par email reste actif quand l'email (non vide) correspond à un autre compte — à retirer une fois les comptes historiques sans login SSO rapprochés.

### petitfablab
- Adresses `cmonnet@erasme.org`, `petitfablab@gmail.com` et URL `http://petitfablab.laclasse.com/…` en dur — passer par la config, en https.
- `squelettes/formulaires/editer_article.php` : limite de 5 chapitres et `maxlength` seulement côté interface ; `id_parent` choisi par le POST.
- `squelettes/sommaire.html:97-110` : `creer=<id>` affiche le titre de n'importe quelle rubrique à un connecté.
- `#TEXTE`/`#SURTITRE`/`#PS` des élèves sans `safehtml` : acceptable si les comptes élèves sont rédacteurs de confiance, à réévaluer sinon.

### ccn
- `inc/uploads.php` : la limite de 100 Mo ne couvre pas les envois bigup (le contrôle d'extension reste fait).

### Transverse
- **Cookies applicatifs sans `HttpOnly`** (`thematique/squelettes/js/controleurs.js` `setCookie()`, `main.js` `visited`) : posés par JS, préférences d'affichage, `SameSite=Strict; Secure` — risque résiduel acceptable.
- **En-têtes HTTP absents** (`Content-Security-Policy`, `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`) : à poser à l'ingress ou dans `htaccess.txt` — attention, `docker-entrypoint.sh` ne recopie `htaccess.txt` que si `.htaccess` n'existe pas (volumes déjà déployés non mis à jour). Une CSP stricte suppose de sortir les nombreux `<script>`/`onclick` inline.

---

## Couverture

- **ccn**, **cadavrexquis** : intégralité du PHP et des squelettes (`cadavrexquis` = `paquet.xml` seul).
- **fictions** : actions, formulaires, autorisations, `inc/*`, pipelines, fonctions, options en entier ; rentrée/genie/administrations par recherche ciblée ; squelettes publics principaux en entier, les autres par recherche (`#ENV`, `#EDIT`, `#AUTORISER`, `#SESSION`). Non lus : JS, config crayons.
- **petitfablab** : PHP et formulaires en entier ; `ecriture`, `sommaire`, `lecture-script` en entier ; autres squelettes par recherche. Non lus : `lecture.html`, `edition.html`, `auteur_editer.html`, JS.
- **thematique** : `thematique_autoriser.php`, `thematique_pipelines.php`, `thematique_options.php`, `action/*`, `formulaires/*.php` en entier ; toutes les requêtes SQL de `thematique_fonctions.php` ; dispatchers `ajax`/`json` et fragments atteignables ; recherche `#ENV*`, `<?php`, `#ENV` en JS/attribut sur tout `squelettes/`. Non lus : `squelettes/js/*`, `json/*.html` en détail, `noisettes/sidebar/consigne_pour_*`/`reponse_pour_*` au-delà des chemins suivis, `base/`, `prive/`. Une première passe de chasse s'est interrompue (erreur API) ; la seconde a repris le périmètre sans s'appuyer sur sa couverture.
