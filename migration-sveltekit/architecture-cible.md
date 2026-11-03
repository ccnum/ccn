# Architecture cible — CCN sur SvelteKit 3 / Svelte 5 (remplace `docs/architecture.html`)

Document de référence de la **stack cible** de la migration SPIP → SvelteKit (VOCO-67, phase 9).
Il remplace `docs/architecture.html` du dépôt SPIP (`ccnum/ccn`) pour tout ce qui concerne
l’application ; le SPIP reste la source des données et l’interface d’écriture historique.

## 1. Vue d’ensemble

```
SPIP (source historique)                      Stack cible (ce dépôt)
┌─────────────────────────┐   ETL   ┌──────────────────────────────────────────┐
│ MariaDB  spip_*         │ ──────► │ SQLite  data/ccn.sqlite  (+ data/media/) │
│ squelettes *.html (136) │  SQL    │ SvelteKit 3 / Svelte 5 (runes)           │
│ plugin thematique       │         │ adapter-node  →  node build  :3000       │
└─────────────────────────┘         │ routes + contrats JSON + RSS + media      │
                                    └──────────────────────────────────────────┘
```

- **Pas de PHP, pas de MariaDB** : un seul binaire Node, un seul fichier de base, un volume de documents.
- Le SPIP de référence (`baseline/`, port 8880) reste disponible pour la **comparaison** : c’est
  l’oracle de parité, pas un service de la stack cible.

## 2. Couches

| Couche | Where | Rôle |
| --- | --- | --- |
| Schéma | `src/lib/server/db/schema.ts` + `drizzle/` | tables recopiées du dump SPIP (`articles`, `rubriques`, `mots`, `forums`, `signatures`, …), colonnes SPIP conservées, vues SQL (`vues_sql`) recréées à l’ouverture |
| Ouverture base | `src/lib/server/db/index.ts` | opening, migrations Drizzle automatiques, journal WAL, vues |
| Squelettes → modules | `src/lib/server/{role,annee,texte,json-parite,media}.ts` | parité des pipeline SPIP : rôle, année scolaire, texte, contrats JSON, documents |
| Contrats JSON | `src/lib/server/json-route.ts` | `spip.php?page=json&mode=…`, `rss_maj`, `rss_forum` (mêmes corps, mêmes en-têtes) |
| Pages | `src/routes/**` | squelettes HTML → routes SvelteKit (page+`+page.server.ts`) |
| Formulaires & API | `src/routes/**/+page.server.ts`, `src/routes/api/**/+server.ts` | actions, CSRF SvelteKit, sessions signées HMAC |
| Tâches de fond | `scripts/{cron,tache,notifier}.ts` | rentrée, poubelle, noms du forum, classes vides, notifications e-mail |

## 3. Décisions structurantes

1. **Un seul dépôt, une base commune** : les sites jumeaux (`fictions`, `petitfablab`,
   `thematique_edifice`) branchent sur la même application, avec un `CCN_SITE_URL` /
   `CCN_NOM_SITE` / jeu de tables propre (voir `docs/bilan-sites-jumeaux.md`).
2. **La parité se prouve, elle ne s’affirme pas** : captures du SPIP réel
   (`baseline/json`, `baseline/PARITE.md`) comparées par `npm run verif:json`,
   `npm run verif:listes`, `npm run parite:grille`.
3. **Les contrats JSON sont une API publique** : le thème du site (JS côté client) les
   consomme ; ils gardent les URL, les clés et les en-têtes d’origine.
4. **La session est un cookie signé (HMAC-SHA256), pas un jeton serveur** (parité du cookie
   SPIP, 12 h, `SameSite=Lax`, `Secure` piloté par `CCN_COOKIE_SECURE`).
5. **SQLite en fichier** : sauvegarde = copier `data/ccn.sqlite` + `data/media/` ; aucun
   serveur de base, aucun utilisateur, aucun port 3306.

## 4. Flux d’une requête

```
client → (reverse proxy TLS, x-forwarded-proto/host)
       → adapter-node handler
       → hooks.server.ts : session (cookie signé), année scolaire (cookie/GET/calendrier), contexte de rôle
       → +page.server.ts (load) → requêtes Drizzle sur SQLite
       → rendu Svelte 5 (runes) → HTML
```

## 5. Exploitation

`docs/deploiement.md` : Dockerfile multi-cibles, `docker-compose.yml` (+ surcharge
`parite`), variables `CCN_*`, migrations au démarrage, persistance `data/`,
`data/media`, Helm chart (`helm/`), procédure de reprise du serveur de dev.

## 6. Ce qui n’est pas migré (état du chantier)

| Domaine | État |
| --- | --- |
| Schéma + ETL du dump réel | **fait** (phases 1-2) |
| Authentification, rôles, périmètres | **fait** (phase 3) |
| Canevas/`inclure/*` (mise en page complète) | partiel (phase 4, en cours) |
| Contrats JSON, RSS, media | **fait** (phases 5-6), écarts listés dans `baseline/PARITE.md` |
| Formulaires d’écriture (publier, forumv2, vidéo, etc.) | partiel (phase 7, en cours) |
| E-mails, notifications, tâches de fond | **fait** (phase 8) |
| Exploitation (Docker, Helm, docs, bilan) | **fait** (phase 9, ce document) |
