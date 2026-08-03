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

Toutes les div cliquables ont `role="button"` + `tabindex="0"` (accessibles au clavier, focus visible). Le remplacement par de vrais `<button>` reste à faire, mais impose de refaire les styles de composants (`.bouton_reponse_consigne` etc.) — à prévoir avec une refonte CSS dédiée.
