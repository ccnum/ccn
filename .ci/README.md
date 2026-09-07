# check_lang_hardcoded.py

Détecte le texte français codé en dur dans `plugins/thematique`,
`plugins/fictions`, `plugins/petitfablab` et `plugins/ccn` (hors `lang/`,
`squelettes/lang/` et `vendor/` de chacun), pour forcer le passage par
un item de langue (`<:module:cle:>`/`_T('module:cle')`, `CCN.lang` côté
JS — ce dernier pont n'existe que pour thematique, cf `check_lang_keys.py`
ci-dessous).

`thematique`, `fictions` (`lang/fictions_fr.php`, 2026-09) et
`petitfablab` (`squelettes/lang/petitfablab_fr.php`) ont maintenant tous
les trois une vraie convention i18n. La baseline ne contient plus que 5
exceptions légitimes (cf ci-dessous) : le check n'impose pas de
migration rétroactive pour un plugin qui n'aurait pas encore d'items de
langue, mais empêche d'en rajouter sans en passer un.

Le scan couvre tout le plugin (et pas seulement `squelettes/`+`formulaires/`) :
les fichiers `.html` à la racine du plugin (ex: `cioidc_erreur_archive.html`
pour thematique) sont de vrais squelettes SPIP rendus au visiteur et
doivent être traités comme tels.

Usage local :

```
python3 .ci/check_lang_hardcoded.py
```

Le script échoue (exit 1) si du texte en dur absent de
`lang-check-baseline.txt` est détecté. Le CI (`.github/workflows/lint-lang.yml`)
exécute ce même check sur toute PR touchant `plugins/thematique/**`,
`plugins/fictions/**`, `plugins/petitfablab/**` ou `plugins/ccn/**`.

## Exceptions dans la baseline

- `squelettes/js/controleurs.js:794` (`Réponse vide !`) : `console.warn` de
  debug, jamais affiché à l'utilisateur.
- `genie/thematique_rentree_annee.php` (`Cap sur l'année`),
  `thematique_administrations.php` (`Blog pédagogique`, `Contenu éditorial`),
  `thematique_fonctions.php` (`Élève`, `Blog pédagogique`) : chaînes de
  contenu persistées en base (titre d'article/rubrique créé à l'installation
  ou à la rentrée, libellé de rôle concaténé dans le nom de l'auteur) — ce
  ne sont pas des textes de template rendus à chaque affichage, donc pas de
  candidats à un item de langue (la BDD ne se traduit pas au chargement de
  la page). Ajoutées à la baseline lors de l'élargissement du scan à tout
  le plugin (2026-08).
- `plugins/ccn/ccn_pipelines.php` (`Compression vidéo document #`) :
  libellé de job passé à `queue_add_job()`, visible seulement dans le
  moniteur de tâches de fond de l'espace privé (admin), pas dans un
  squelette public rendu au visiteur. Ajoutée lors de l'extension du
  scan à `plugins/ccn` (2026-09).
- `plugins/fictions/fictions_pipelines.php` (`%Blog Pédagogique%`) :
  motif SQL `LIKE` comparé au titre d'une rubrique en base
  (`sql_getfetsel(..., 'titre LIKE ' . sql_quote('%Blog Pédagogique%'))`),
  jamais affiché — le traduire casserait la requête plutôt que
  d'afficher du texte. Ajoutée lors de la migration i18n de fictions
  (2026-09, cf `lang/fictions_fr.php`).
- `thematique_pipelines.php:189,193` (`de l'email`, `de l'avatar`) :
  libellé passé à `thematique_cioidc_maj_champ()` uniquement pour composer
  un message `spip_log(...)` de debug (mise à jour d'un champ auteur via le
  SSO) — jamais affiché à l'utilisateur. Le `STRIP_PATTERN` sur les appels
  `spip_log(...)` ne le voit pas car la chaîne est construite dans un
  paramètre séparé, pas littéralement à l'intérieur de l'appel. Ajoutées
  lors du renforcement du filtre (voir note ci-dessous).

Le filtre a longtemps ignoré tout texte en dur **sans caractère accentué**
(ex: `data-tip="J'aime"`) : il ne reposait que sur `ACCENTED_RE`. Depuis
2026-08, `looks_french()` détecte aussi les élisions françaises (`j'/n'/l'/
d'/c'/m'/s'/t'/qu'`), qui n'ont quasiment aucun faux positif en dehors du
français — sauf schéma `'X'` à une lettre (filtre SPIP, ternaire JS), d'où
le lookahead qui exige une lettre juste après l'apostrophe. La regex
d'attribut (`HTML_ATTR_RE`) a aussi été corrigée : elle traitait `'` et `"`
comme un seul et même délimiteur, donc `data-tip="J'aime"` se faisait
tronquer à l'apostrophe elle-même avant même d'atteindre le filtre.

## Regénérer la baseline

Si un nouveau cas légitime doit être toléré (texte technique, contenu
historique figé, etc.) :

```
python3 .ci/check_lang_hardcoded.py --write-baseline
```

Vérifier ensuite que le diff de `lang-check-baseline.txt` ne contient QUE
l'exception attendue, pas d'autres régressions.

# check_lang_keys.py

Complète `check_lang_hardcoded.py` : ce dernier garantit l'absence de texte
en dur, mais pas la validité des clés utilisées. Une clé mal orthographiée
(`<:thematique:mauvaize_cle:>`) s'affiche telle quelle en prod sans faire
échouer le lint anti-texte-en-dur.

`check_lang_keys.py` couvre `plugins/thematique`, `plugins/fictions`,
`plugins/petitfablab` et `plugins/ccn` : pour chacun, toute clé
référencée doit exister :
- `<:module:cle:>` et `_T('module:cle')` (module = nom du plugin) →
  doivent exister dans son fichier de langue, cherché à la fois en
  `lang/<module>_fr.php` (thematique, ccn) et
  `squelettes/lang/<module>_fr.php` (petitfablab — autre emplacement).
  `fictions` n'a pas de fichier de langue du tout : la moindre clé
  `<:fictions:...:>` y ferait donc immédiatement échouer le check
  (aucune actuellement) ;
- `CCN.lang.cle` côté JS → doit exister comme propriété de l'objet
  `CCN.lang` construit dans `plugins/thematique/squelettes/noisettes/timeline.html`.
  Vérifié uniquement pour thematique : c'est le seul plugin à avoir ce
  pont PHP → JS, les trois autres ne l'utilisent pas.

Usage local :

```
python3 .ci/check_lang_keys.py
```

Pas de mécanisme de baseline ici : une clé manquante est toujours un bug
(faute de frappe ou clé jamais ajoutée), donc le script échoue directement
sans exception tolérée.

# check_hardcoded_paths.py

Détecte deux types de liens en dur dans les squelettes `.html` de
`plugins/thematique`, `plugins/fictions`, `plugins/petitfablab` et `plugins/ccn` :

1. Ressources du plugin (`img/`, `css/`, `js/`, `pdf/`) qui n'utilisent pas
   `#CHEMIN{...}` (ou `#ENV{chemin}`/`#DOSSIER_SQUELETTE`). Un chemin
   relatif en dur (`src="img/foo.png"`, `url(../img/foo.png)`) casse si le
   plugin est déplacé/renommé ou si le squelette est appelé depuis un
   contexte différent, contrairement à `#CHEMIN{img/foo.png}` résolu par
   SPIP.
2. Liens internes `spip.php?page=...` écrits en dur (dans un attribut ou
   une chaîne JS de `<script>`) au lieu de `#URL_PAGE{...}`. Les URLs
   absolues vers un autre domaine (agrégation RSS cross-site, etc.) ne sont
   pas concernées — seuls les liens relatifs vers `spip.php` de l'install
   courante le sont.

Seuls les fichiers `.html` (compilés par SPIP) sont scannés : un `.css` brut
ou un `.js` ne passent pas par le compilateur SPIP, donc `#CHEMIN`/`#URL_PAGE`
n'y ont pas de sens — les chemins relatifs classiques (`../img/...`),
`CCN.urlRoot` ou `spip.php?page=...` y restent la seule option (ex :
`squelettes/js/controleurs.js`, qui construit ses URLs AJAX avec des ids
connus seulement à l'exécution, donc hors de portée de `#URL_PAGE`).

Usage local :

```
python3 .ci/check_hardcoded_paths.py
```

Même mécanisme de baseline que `check_lang_hardcoded.py` (`--write-baseline`
pour régénérer après un faux positif volontaire).

# check_html_duplication.js

Détecte le HTML/squelette SPIP dupliqué (copier-coller) dans les plugins
maison (`plugins/petitfablab`, `plugins/fictions`, `plugins/thematique`,
`plugins/ccn`), via [jscpd](https://github.com/kucherenko/jscpd)
(`node_modules/.bin/jscpd`, dépendance dev npm — seul script `.ci/` en
Node, les autres sont en PHP/Python).

Limité à ces quatre plugins (pas tout `plugins/`) : ce sont les seuls
développés/maintenus ici, les autres sont des plugins tiers vendorisés
(contrib SPIP) qu'on ne cherche pas à refactorer.

Usage local (nécessite `npm ci` au préalable) :

```
npm ci
node .ci/check_html_duplication.js [--baseline=PATH] [--write-baseline]
                                    [--min-lines=N] [--min-tokens=N]
```

Comme pour les checks Python, seule une nouvelle duplication (absente de
`html-duplication-baseline.txt`) fait échouer le script — le volume déjà
présent (8 clones pour thematique seul lors de la mise en place, 2026-08 ;
44 depuis l'élargissement à fictions/petitfablab/ccn) est toléré tel quel ;
`--write-baseline` régénère le fichier après vérification du diff.

# check_php_duplication.js

Même principe que `check_html_duplication.js` (jscpd), appliqué au PHP de
`plugins/thematique`, `plugins/fictions`, `plugins/petitfablab` et `plugins/ccn`
(pattern `**/*.php` au lieu de `**/*.html`).

Usage local (nécessite `npm ci` au préalable) :

```
npm ci
node .ci/check_php_duplication.js [--baseline=PATH] [--write-baseline]
                                   [--min-lines=N] [--min-tokens=N]
```

Baseline dans `.ci/php-duplication-baseline.txt` : vide pour thematique
seul depuis la factorisation des 8 clones détectés à la mise en place
(2026-08, cf ci-dessous) ; 1 entrée depuis l'élargissement à
fictions/petitfablab/ccn (`petitfablab/squelettes/formulaires/editer_article.php`
vs `thematique/formulaires/public_editer_article.php`, non traitée —
deux plugins différents, pas de fonction commune évidente sans dépendance
croisée). ccn n'ajoute aucune nouvelle entrée. Même mécanisme que les
autres checks : `--write-baseline` après vérification du diff pour
accepter une nouvelle duplication.

## Duplications déjà traitées (2026-08)

- `base/th_cextras.php` et `base/th_install.php` : reliquats morts de
  l'ancien préfixe `th_` (renommé en `thematique_` dans #289), plus jamais
  chargés par SPIP (seul `base/<prefix>_*.php` avec le préfixe déclaré dans
  `paquet.xml`, ici `thematique`, est auto-inclus) — supprimés.
- `formulaires/joindre_document_mission.php` et `formulaires/joindre_video.php` :
  logique de recherche des fichiers bigup, vérification d'autorisation, et
  ajout des documents + construction de la réponse CVT, factorisées dans
  `inc/thematique_joindre.php`.
- `base/thematique_cextras.php` (2 clones de 11 lignes, champs
  `url_id_doc`/`id_rubrique_lien` et `x`/`y`) : boilerplate de déclaration
  de champs extras factorisé dans `thematique_champ_extra_simple()` (nom,
  clé de langue, type SQL et statuts autorisés à modifier en paramètres) —
  sortie vérifiée strictement identique à l'ancien tableau en dur avant
  factorisation.
