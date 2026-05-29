# Audit de sécurité — Plugin SPIP `thematique` (CCN)

**Date** : 2026-05-29 · **Statut** : ✅ Toutes les vulnérabilités corrigées

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
