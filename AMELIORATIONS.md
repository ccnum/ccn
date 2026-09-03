# Améliorations à faire — Plugin SPIP `thematique` (CCN)

**Dernière mise à jour** : 2026-08-03

---

## CSS

### Media queries mobile/tablette

**Fichier** : `css/responsive.css.html`

Ajouté `max-width: 1024px` (sidebar/colonnes du menu bas en tailles fluides) et `max-width: 768px` (sidebar plein écran, menu bas empilé). Ajustements défensifs anti-débordement, pas une vraie refonte visuelle — **à vérifier dans un vrai navigateur**, non testé visuellement.

---

## Architecture SPIP

### Logique de `rubrique.html`

**Fichier** : `squelettes/noisettes/rubrique.html`

La condition de rôle (`thematique_afficher_rubrique_utilisateur_prof()`) et le calcul des classes CSS par type de rubrique (`thematique_classe_bloc_rubrique_menu_externe/interne()`) sont extraits en PHP, vérifiés via `recuperer_fond()` en CLI. Le reste du fichier (arbre de navigation, handlers JS inline) mélange encore présentation et logique — refactor plus large à faire avec accès navigateur (risque de régression visuelle sur la sidebar).

---

## Accessibilité

### Remplacer les `<div>` cliquables par des `<button>`

Fait : toutes les div cliquables (consigne.js, reponse.js, article.js, modèles `actu_*`, `rubrique_detail.html`, `ressources_detail.html`, `classes_detail.html`, `reponse_binome_head.html`, icônes de sidebar, badges timeline) sont converties en `<button type="button" class="... btn-reset">`. `.btn-reset` (dans `tokens.css.html`, chargé avant les CSS de composants) fait un `all: unset` + `display: block` neutre, que les règles de composants (chargées après) re-spécialisent normalement (fond, bordure, `display: flex`, etc.) sans rien casser.

Exception : `actu_commentaires.html` et `actu_documents.html` gardent une `<div role="button">` — ils contiennent un `<a>` cliquable imbriqué (lien image lightbox), invalide dans un `<button>` (contenu interactif imbriqué interdit en HTML5, risque de casser le clic sur l'image).

Vérifié via `recuperer_fond()` en CLI sur plusieurs fonds (`sommaire`, `rubrique`, `ressources_detail`, `classes_detail`, `actus_timeline`, modèles `actu_*`) — rendu correct, balises bien fermées, aucune erreur. Non vérifié dans un vrai navigateur.
