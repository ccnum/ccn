# Audit de sécurité — Plugin SPIP `thematique` (CCN)

**Date** : 2026-06-25 · **Statut** : ✅ Toutes les vulnérabilités corrigées

## Résolutions récentes

### ✅ #286 — Durcissement des migrations PHP (2026-06-24)

**Fichier** : `thematique_administrations.php`

- `$maj['2.3.4']` et `$maj['3.0.7']` définis avant utilisation (supprime les avertissements sur clés indéfinies)
- `sql_update` sans effet supprimés dans `$maj['2.3.5']`
- `sql_alter` sur `spip_syndic` retiré (table optionnelle, plugin tiers)
- `thematique_vider_tables()` complété : nettoie les groupes de mots et mots à la désinstallation

---

## Points en suspens

### ⚠️ M6 — Cookies applicatifs sans `HttpOnly`

**Fichier** : `squelettes/js/controleurs.js`

Les cookies (`laclasse_annee_scolaire`, etc.) sont définis depuis JavaScript pour stocker des préférences d'affichage. `HttpOnly` est incompatible avec des cookies gérés par JS. Ils ne contiennent pas de données de session. `SameSite=Strict` et `Secure` sont présents. Risque résiduel acceptable.

---

### ⚠️ F3 — Absence d'en-têtes de sécurité HTTP

Hors périmètre du plugin — à configurer au niveau nginx/apache :
- `Content-Security-Policy`
- `X-Frame-Options`
- `X-Content-Type-Options`
- `Referrer-Policy`
