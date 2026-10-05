# Améliorations à faire — plugins maison CCN

**Dernière mise à jour** : 2026-10-05

Points non liés à une vulnérabilité (voir `AUDIT_SECURITE.md` pour la sécurité). Chaque point a été
vérifié dans le code à cette date.

---

## Déploiement

### `.htaccess` jamais mis à jour sur un volume existant

**Fichier** : `docker-entrypoint.sh`

`htaccess.txt` n'est copié en `.htaccess` que s'il n'existe pas : une modification de
`htaccess.txt` n'atteint pas les instances déjà déployées. (Les en-têtes de sécurité ont
été posés dans la configuration Apache de l'image pour cette raison.)

---

## Code

### Logique de `rubrique.html` (thematique)

**Fichier** : `thematique/squelettes/noisettes/rubrique.html`

Condition de rôle et classes CSS extraites en PHP (`thematique_afficher_rubrique_utilisateur_prof()`,
`thematique_classe_bloc_rubrique_menu_externe/interne()`). Il reste 8 blocs `<script>` inline mêlant
présentation et logique — refactor à faire avec accès navigateur (risque de régression sur la
sidebar), et prérequis à une CSP stricte.

### Valeurs en dur dans petitfablab

**Fichier** : `petitfablab/petitfablab_fonctions.php`

Les adresses des mails sont en constantes `_PETITFABLAB_MAIL_*` (2026-10) ; reste
`balise_NOM_AUTEUR_dist`, qui renvoie toujours « Violaine Schwartz », et le lien de blog
tumblr en dur dans les mails.

### Documentation CI périmée

**Fichiers** : `.ci/README.md:102`, `.ci/check_lang_keys.py:17`

Indiquent que `fictions` n'a pas de fichier de langue : faux depuis que `fictions` est l'ex-`fictionsv2`
(`lang/fictions_fr.php`).

---

## CSS

### Media queries mobile/tablette (thematique)

**Fichier** : `thematique/css/responsive.css.html`

Paliers `max-width: 1024px` (sidebar et menu bas fluides) et `768px` (sidebar plein écran, menu bas
empilé) : ajustements défensifs anti-débordement, **jamais vérifiés dans un navigateur**. Le formulaire
de publication a eu sa propre passe responsive (#429), indépendante.

---

## Accessibilité

### `<div role="button">` restants (thematique)

**Fichiers** : `thematique/squelettes/modeles/actu_commentaires.html`, `actu_documents.html`

Gardés en `<div role="button" tabindex="0">` (activés au clavier par `controleurs.js`) car ils
contiennent un `<a>` (lien lightbox), interdit dans un `<button>`. Pour s'en passer, sortir le lien du
bloc cliquable.
