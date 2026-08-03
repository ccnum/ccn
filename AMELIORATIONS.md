# Améliorations à faire — Plugin SPIP `thematique` (CCN)

**Dernière mise à jour** : 2026-08-03

---

## CSS

### Media queries mobile/tablette

**Fichier** : `css/responsive.css.html`

Seuls `max-width: 1280px` et `max-width: 1050px` existent. Ajouter `768px`/`1024px` nécessite une vraie refonte responsive (quels blocs s'adaptent, comment) — à faire avec un accès navigateur pour vérifier visuellement.

---

## Architecture SPIP

### Logique de `rubrique.html`

**Fichier** : `squelettes/noisettes/rubrique.html`

La condition de rôle a été extraite en PHP (`thematique_afficher_rubrique_utilisateur_prof()`). Le reste du fichier (arbre de navigation, handlers JS inline, classes CSS calculées par `#TYPE_OBJET`) mélange encore présentation et logique — refactor plus large à faire avec accès navigateur (risque de régression visuelle sur la sidebar).

---

## Accessibilité

### Remplacer les `<div>` cliquables par des `<button>`

Toutes les div cliquables ont `role="button"` + `tabindex="0"` (accessibles au clavier, focus visible). Le remplacement par de vrais `<button>` reste à faire, mais impose de refaire les styles de composants (`.bouton_reponse_consigne` etc.) — à prévoir avec une refonte CSS dédiée.
