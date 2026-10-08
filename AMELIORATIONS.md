# Améliorations à faire — plugins maison CCN

**Dernière mise à jour** : 2026-10-05

Points non liés à une vulnérabilité (voir `AUDIT_SECURITE.md` pour la sécurité). Chaque point a été
vérifié dans le code ou dans un navigateur (Chromium, instances ddev) à cette date.

---

## CSS

### Pas de mise en page mobile (thematique)

**Fichier** : `thematique/css/responsive.css.html`

Vérifié dans Chromium : aucun débordement à 1024 et 768 px (paliers existants), mais à 390 px la page
déborde de 368 px en largeur (menu haut trop large, pas de palier sous 768 px).
