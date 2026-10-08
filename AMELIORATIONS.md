# Améliorations à faire — plugins maison CCN

**Dernière mise à jour** : 2026-10-05

Points non liés à une vulnérabilité (voir `AUDIT_SECURITE.md` pour la sécurité). Chaque point a été
vérifié dans le code ou dans un navigateur (Chromium, instances ddev) à cette date.

---

## CSS

### Pas de mise en page mobile (thematique)

**Fichier** : `thematique/css/responsive.css.html`

Menu haut corrigé (palier ≤ 480 px dans `responsive.css.html`) : icônes seules, plus de débordement
à 390/320 px en visiteur anonyme (vérifié dans Chromium). Reste :
- vérifier un compte connecté (avatar seul) et un admin (bloc `#menu_haut_rubrique` en plus) ;
- garder la ligne de temps : si elle dépasse, la faire défiler horizontalement dans son propre
  conteneur (`#timeline_responsive`) plutôt que de la compresser.
