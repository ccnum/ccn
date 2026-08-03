# check_lang_hardcoded.py

Détecte le texte français codé en dur dans `plugins/thematique/{squelettes,formulaires}`
(hors `lang/`), pour forcer le passage par des items de langue
(`lang/thematique_fr.php` côté PHP/squelette, `CCN.lang` côté JS).

Pour l'instant limité au plugin thematique (seul plugin i18n-isé à ce
jour) — à généraliser (argument `--scan`) si d'autres plugins adoptent
la même convention.

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
- `squelettes/noisettes/inc/article-design.laclasse.com.html` : questions
  d'une consigne historique spécifique (laclasse.com, "zerogaspi"/"design"),
  liées à des `#ID_CONSIGNE` précis — pas du texte de chrome générique du
  plugin. Décision documentée lors de l'externalisation i18n de 2026-08.

## Regénérer la baseline

Si un nouveau cas légitime doit être toléré (texte technique, contenu
historique figé, etc.) :

```
python3 .ci/check_lang_hardcoded.py --write-baseline
```

Vérifier ensuite que le diff de `lang-check-baseline.txt` ne contient QUE
l'exception attendue, pas d'autres régressions.
