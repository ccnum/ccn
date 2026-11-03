# `migration-sveltekit/` — stack cible SvelteKit de CCN

Ce dossier regroupe les **livrables d’exploitation et de bilan** de la migration
SPIP → SvelteKit (Svelte 5 runes, Drizzle, SQLite), conduite phase par phase dans
`MIGRATION_PLAN.md` (dépôt de l’application migrée, `app/`).

| Fichier | Contenu |
| --- | --- |
| `architecture-cible.md` | architecture de la stack cible (remplace, pour l’application, `docs/architecture.html`) |
| `deploiement.md` | Docker (2 cibles), `docker-compose.yml` + surcharge « parité », variables `CCN_*`, migrations au démarrage, persistance `data/`, Helm, reprise de dev, contrôle CSRF |
| `helm/` | chart Kubernetes de la stack cible (Deployment, Service, Ingress, PVC monté sur `/app/data`) |
| `PARITE.md` | grille de parité pages × rôles × années, contrats JSON, formulaires : écarts nommés et dispositions |
| `bilan-sites-jumeaux.md` | budget de migration des sites jumeaux sur la base commune (`fictions`, `petitfablab`, `thematique_edifice`) |
| `Dockerfile`, `docker-compose*.yml`, `.env.example`, `.dockerignore` | la stack, telle que construite et vérifiée |

L’application elle-même vit à côté de ce dépôt SPIP (source des données et oracle de
parité) : `app/` — SvelteKit 3 / Svelte 5, 22 pages, contrats JSON, RSS, ETL du dump réel,
tâches de fond, e-mails.

## Vérifications faites

```
npm run check && npm run build          # 0 erreur
docker compose up -d --build            # http://192.168.100.50:8890  (santé : /api/etat)
instance parité (données réelles)       # http://192.168.100.50:8891
npm run verif:json   -- --base http://192.168.100.50:8891   # 39 captures conformes / 83
npm run parite:grille -- --base http://192.168.100.50:5174  # 22 pages × 6 rôles × 3 années
helm lint ./helm                        # 0 échec
```
