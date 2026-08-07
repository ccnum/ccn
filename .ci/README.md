# check_lang_hardcoded.py

Détecte le texte français codé en dur dans tout le plugin `plugins/thematique`
(hors `lang/` et `vendor/`), pour forcer le passage par des items de langue
(`lang/thematique_fr.php` côté PHP/squelette, `CCN.lang` côté JS).

Pour l'instant limité au plugin thematique (seul plugin i18n-isé à ce
jour) — à généraliser (argument `--scan`) si d'autres plugins adoptent
la même convention.

Le scan couvre désormais tout le plugin (et pas seulement
`squelettes/`+`formulaires/`) : les fichiers `.html` à la racine du plugin
(ex: `cioidc_erreur_archive.html`) sont de vrais squelettes SPIP rendus au
visiteur et doivent être traités comme tels.

Usage local :

```
python3 .ci/check_lang_hardcoded.py
```

Le script échoue (exit 1) si du texte en dur absent de
`lang-check-baseline.txt` est détecté. Le CI (`.github/workflows/lint-lang-thematique.yml`)
exécute ce même check sur toute PR touchant `plugins/thematique/**`.

## Exceptions dans la baseline

- `squelettes/js/controleurs.js:828` (`Réponse vide !`) : `console.warn` de
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
- `thematique_pipelines.php:191,195` (`de l'email`, `de l'avatar`) :
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

`check_lang_keys.py` vérifie que toute clé référencée dans le plugin existe
bien :
- `<:thematique:cle:>` et `_T('thematique:cle')` → doivent exister dans
  `lang/thematique_fr.php` ;
- `CCN.lang.cle` côté JS → doit exister comme propriété de l'objet
  `CCN.lang` construit dans `squelettes/noisettes/timeline.html` (seul pont
  PHP → JS du plugin).

Usage local :

```
python3 .ci/check_lang_keys.py
```

Pas de mécanisme de baseline ici : une clé manquante est toujours un bug
(faute de frappe ou clé jamais ajoutée), donc le script échoue directement
sans exception tolérée.

# check_hardcoded_paths.py

Détecte deux types de liens en dur dans les squelettes `.html` du plugin :

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
