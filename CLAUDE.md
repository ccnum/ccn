# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

Monorepo SPIP (CMS PHP) hébergeant plusieurs sites multisite pour le Conseil de la Culture à
Nantes (CCN) / laclasse.com. C'est un **cœur SPIP** (`ecrire/`, `prive/`, `squelettes-dist/`,
`plugins-dist/`) + un jeu de **plugins maison** dans `plugins/` (le code sur lequel on travaille
réellement — les autres répertoires sont le framework SPIP, ne pas les modifier sauf besoin
explicite).

Deux plugins "site" principaux, tous deux dépendant du plugin transverse **`ccn`** (rôle,
année scolaire, options communes) :
- **`thematique`** — plugin actif, le plus développé (voir ci-dessous)
- **`fictions`** — variante sœur

`thematique_edifice` dépend de `thematique` (variante pour la plateforme Edifice).

Le site réel est **multisite** : chaque instance (`sites/<nom>.ddev.site/`) partage le même code
mais a sa propre base de données/config. En local (ddev), les instances disponibles sont
`ccn-fictions`, `ccn-ontourne`, `ccn-petitfablab`, `edifice-digitalyceen`.

## Commandes

### Environnement local (ddev)
```
ddev start                    # démarre les containers (web, db, phpmyadmin)
ddev launch                   # ouvre https://ccn.ddev.site
```
Le multisite se pilote par nom d'hôte (`https://ccn-ontourne.ddev.site`, etc., déclarés dans
`.ddev/config.yaml`). Après une modif de squelette, vider le cache SPIP du site concerné si le
comportement semble périmé :
```
rm -rf sites/<site>.ddev.site/tmp/cache/*
```

### Qualité de code (par plugin, ex. `plugins/thematique/`)
```
composer check-cs             # vendor/bin/ecs check --ansi (Easy Coding Standard, style)
composer fix-cs               # ecs --fix : corrige automatiquement le style
composer rector                # vendor/bin/rector process (migrations/refactors auto)
composer rector-dry-run        # rector en mode simulation
vendor/bin/phpstan analyse     # analyse statique (niveau défini dans phpstan.neon.dist)
```
Un run automatisé de `ecs`/`rector` peut modifier des fichiers en tâche de fond pendant une
session (corrections triviales : style, compat PHP8) — vérifier `git status`/`git diff` avant de
committer, ces changements peuvent se mélanger aux tiens.

### Lint CI (plugin `thematique` uniquement, exécuté sur toute PR touchant `plugins/thematique/**`)
```
python3 .ci/check_lang_hardcoded.py    # texte FR en dur hors lang/ (baseline: .ci/lang-check-baseline.txt)
python3 .ci/check_lang_keys.py         # clés <:thematique:xxx:> / CCN.lang.xxx invalides
python3 .ci/check_hardcoded_paths.py   # chemins img/css/js/pdf ou spip.php?page= en dur (baseline: .ci/hardcoded-paths-baseline.txt)
```
`--write-baseline` sur les deux premiers pour accepter une exception légitime (vérifier le diff
de la baseline ne contient QUE le cas attendu). Détail des règles/exceptions : `.ci/README.md`.

### Tests
Pas de suite de tests automatisés (pas de PHPUnit). La vérification se fait par lint statique
(ci-dessus) + test manuel sur une instance ddev réelle (voir le piège ci-dessous sur pourquoi
c'est indispensable).

## Architecture — plugin `thematique`

### Squelettes = frontend SPA-like en popups ajax
Le site n'est pas un enchaînement de pages classiques : `squelettes/layout.html` (canevas,
`#CACHE{0}`) charge une page "sommaire" et le contenu (article, rubrique, formulaire) s'ouvre en
**popup chargée en ajax** (`mode=ajax-detail`) via les contrôleurs JS
(`squelettes/js/controleurs.js`, `main.js`). Les fragments réutilisables vivent dans
`squelettes/noisettes/` (blocs inclus, souvent `#CACHE{0}` car dépendants de session/cookie —
documenté en tête de chaque fichier concerné) et `squelettes/modeles/` (mini-squelettes appelés
via `#MODELE{}`, ex. résolution d'un logo/icône).

Deux implémentations de forum coexistent : l'ancienne (`noisettes/ajax/article-forum-detail.html`,
`noisettes/inc/forum.html`) et la nouvelle **forumv2** (`noisettes/inc/forumv2/*`, câblée depuis
`noisettes/sidebar/onglet_commentaires.html`, elle-même incluse par les fonds
`consigne_pour_*`/`reponse_pour_*`/`blog.html`) — c'est forumv2 qui est réellement live sur les
pages article. Vérifier laquelle est exercée avant de supposer qu'une modif de l'ancien chemin a
un effet visible.

### Convention : PHP caché plutôt que boucles SPIP non cachées
Beaucoup de résolutions (type de contenu d'une rubrique/article/auteur, couleur/numéro de classe,
rôle de l'auteur en session, rubrique par mot-clé) sont des fonctions PHP dans
`thematique_fonctions.php` avec un cache mémoire statique par requête (`static $cache = []`),
appelées comme filtre SPIP (`#GET{x}|thematique_xxx`) plutôt que via un `#MODELE{}` — plus rapide
(une requête par valeur distincte et par requête HTTP, pas par appel) et réutilisable entre
squelettes. Exemples : `thematique_type_objet_rubrique/_article/_auteur`, `classe_numero`,
`classe_icone`, `classe_id_rubrique_forum`, `thematique_donner_role`,
`thematique_id_rubrique_a_mot`. Si tu ajoutes une résolution répétée dans un `#MODELE{}` ou une
boucle SPIP imbriquée non cachée, c'est probablement à migrer sur ce modèle plutôt qu'à optimiser
sur place.

### Rôle de session
`thematique_donner_role($id_auteur)` (prof/intervenant/admin/eleve/null) est calculé **une fois
par requête**, dans le pipeline `auth_init_droits` (`thematique_pipelines.php ->
thematique_preparer_visiteur_session`), et exposé via `#SESSION{role}` — ne pas le
recalculer ailleurs. `fond_consigne_pour_role()`/`fond_reponse_pour_role()` choisissent le fond
sidebar à inclure selon ce rôle.

### Année scolaire
`_ANNEE_SCOLAIRE` (constante PHP, définie dans `plugins/ccn/ccn_options.php`) pilote quasi toute
la structure de contenu : les rubriques racines sont nommées/organisées par année, et
`thematique_pre_boucle()` (pipeline `pre_boucle`) filtre automatiquement les boucles
`ARTICLES`/`SYNDIC_ARTICLES` sur la période de l'année scolaire active — sauf modificateur
`{tout}` ou requête depuis `/ecrire`. `thematique_annee_scolaire()` (valeur sélectionnée,
cookie/GET) diffère de `thematique_annee_scolaire_reelle()` (année calendaire réelle,
indépendante de la sélection). Si des classes/missions/pictos n'apparaissent pas sur un
environnement, vérifier en premier la présence et le titrage de la rubrique racine de l'année
active — c'est un problème de données plus souvent qu'un bug de squelette.

### Intégration SSO (CIOIDC / ENT laclasse.com)
`inc/thematique_cioidc.php` + `thematique_pipelines.php:thematique_cioidc_userinfo()` : à la
connexion via l'ENT, résout/creé l'auteur SPIP, son statut (prof/élève/webmestre) et ses liens
vers les rubriques de classe, à partir des attributs ENT (`ENTClassesGroupes`, `ENTPersonProfils`,
`ENTGroupesLibres`, etc.). Activé conditionnellement (`SPIP_PLUGINS_CIOIDC`, voir `README.md`).

### i18n
Tout texte visible doit passer par un item de langue : `<:thematique:cle:>` /
`_T('thematique:cle')` côté PHP/squelette, déclarés dans `lang/thematique_fr.php` ; côté JS, objet
`CCN.lang` construit dans `squelettes/noisettes/timeline.html` (seul pont PHP → JS pour les
traductions). Appliqué par les lints CI ci-dessus.

### Tâches de fond
`genie/thematique_rentree_annee.php` (crée la structure de la nouvelle année scolaire) et
`genie/thematique_rentree_poubelle.php`, enregistrées via le pipeline `taches_generales_cron`
(toutes les 24h) plutôt que la balise `<genie>` de `paquet.xml`.

## Piège SPIP : `#SET{var, [(...)]}` casse la sortie

`[( )]` est la syntaxe du bloc **conditionnel** SPIP (`[(condition) texte si vrai]`), pas un
simple emballage de valeur. Utilisé comme valeur d'un `#SET` en dehors de tout test, le
compilateur SPIP n'affecte rien à la variable : il recrache le résultat brut dans la sortie de la
page, avec une syntaxe résiduelle (ex: une accolade en trop) — corrompant du JSON ou du HTML
généré ailleurs sur la page, sans erreur PHP visible.

```
# Faux — casse la sortie
#SET{id_rubrique, [(#VAL{ressources}|thematique_id_rubrique_a_mot)]}

# Correct — #SET nu, pas de crochets
#SET{id_rubrique, #VAL{ressources}|thematique_id_rubrique_a_mot}
```

Les crochets ne sont utiles que s'il y a un test et/ou du texte conditionnel après :
`[(#GET{x}|=={y}|oui) texte]`. Pour une simple chaîne de filtres assignée à une variable, `#SET{var,
expr}` suffit toujours. Ce bug est passé dans un commit avant d'être repéré en testant en local
(`?page=json&mode=projet` retournait du JSON invalide) — **toujours tester une page/endpoint
réellement affecté après un `#SET{}` avec transformation de valeur**, pas seulement vérifier que
le squelette compile sans erreur.

## Convention de commit

`type(scope): message` en français, sans ligne `Co-Authored-By`. Types observés :
`feat`, `fix`, `perf`, `refactor`, `chore`. Scope = nom du plugin concerné (`thematique`, etc.).
