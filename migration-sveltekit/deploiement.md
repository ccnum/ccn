# Déploiement & exploitation — CCN (SvelteKit + SQLite)

Phase 9 (VOCO-67). Ce document remplace `docs/deploiement.html` du dépôt SPIP pour la stack cible.

## 1. Images

Le `Dockerfile` est multi-étapes, **deux cibles** :

| Cible | Commande | Contenu |
| --- | --- | --- |
| `runtime` (défaut) | `docker build -t ccn-sveltekit:local .` | `node build` (adapter-node), dépendances de service seules, `drizzle/` (migrations), ~400 Mo |
| `outilleur` | `docker build -t ccn-sveltekit:outilleur --target outilleur .` | même contenu + devDependencies : CLI `npm run db:*`, `taches:cron`, écrans de vérification |

`npm ci --ignore-scripts` : better-sqlite3 13 fournit des prébinaires, pas de compilation
node-gyp (et le build n’a pas besoin du réseau).

## 2. Stack `docker compose`

```
docker compose up -d --build            # http://192.168.100.50:8890
docker compose logs -f app
docker compose --profile taches run --rm taches npm run db:etl -- --source ../baseline/2026-08-11.sqlite
```

- **`app`** : le site. Écoute `0.0.0.0:3000` dans le conteneur, port publié
  `${CCN_PUBLISHED_PORT:-8890}`. `restart: always`, healthcheck sur `/api/etat`
  (lit SQLite : prouve serveur **et** base ouverte).
- **`taches`** (profil `taches`, hors du stack par défaut) : l’image outilleur en
  one-shot — migrations, ETL, tâches de l’année, écrans de vérification.
- **`data/` est un *bind mount*** (et non un volume nommé) : c’est là que vivent
  `ccn.sqlite`, `media/`, `outbox/`, `notifications.log`. L’utilisateur `node`
  (uid 1000) du conteneur doit pouvoir y écrire :

  ```
  mkdir -p data/media && chown -R 1000:1000 data
  ```

**Migrations au démarrage** : `src/lib/server/db/index.ts` applique les migrations
Drizzle du dossier `drizzle/` (présent dans l’image) à l’ouverture de la base. Un
volume `data/` vide se structure donc tout seul au premier `docker compose up` — pas de
job d’installation, pas de commande humaine.

**Instance « parité »** (données réelles du dump SPIP, port 8891) :

```
CCN_PUBLISHED_PORT=8891 docker compose -f docker-compose.yml -f docker-compose.parite.yml --profile taches up -d
npm run verif:json  -- --base http://192.168.100.50:8891
npm run parite:grille -- --base http://192.168.100.50:8890
```

## 3. Variables d’environnement

Voir `.env.example` (toutes les variables, avec valeurs par défaut). Les essentielles :

| Variable | Défaut | Rôle |
| --- | --- | --- |
| `CCN_DB_PATH` | `data/ccn.sqlite` | fichier SQLite servi |
| `CCN_MIGRATIONS_FOLDER` | `drizzle` | dossier des migrations Drizzle |
| `CCN_SECRET` | secret éphémère | signature du cookie de session (à fixer en production : sessions survivent au redémarrage) |
| `CCN_COOKIE_SECURE` | (protocole de la requête) | `vrai` derrière un frontal TLS, `faux`/vide en HTTP direct — un cookie `Secure` sur un site en HTTP clair ne remonte plus, les sessions ne montent pas |
| `CCN_SITE_URL` | — | URL publique, utilisée par les e-mails et les flux |
| `CCN_NOM_SITE` | `ccn.laclasse.com` | titre des flux RSS — doit valoir le `nom_site` de l’instance de référence pour la parité (`ontourne`) |
| `CCN_MEDIA_RACINE` | `data/media` | racine des documents servis (`?exec=document`) |
| `CCN_TRUSTED_ORIGINS` | — | origines fiables du contrôle CSRF SvelteKit |
| `CCN_MAIL_TRANSPORT` | `mock` | `mock` = `data/outbox/*.eml`, `smtp` = `CCN_SMTP_*`, `aucun` |
| `CCN_ANNEE_SCOLAIRE` | règle calendaire | année forcée (instances de parité) |
| `HOST` / `PORT` | `0.0.0.0` / `3000` | écoute du serveur node |
| `CCN_PUBLISHED_PORT` | `8890` | port publié par compose |

Derrière un reverse proxy TLS, déclarer les en-têtes à adapter-node :
`PROTOCOL_HEADER=x-forwarded-proto`, `HOST_HEADER=x-forwarded-host`.

## 4. Sauvegarde / reprise

```
sqlite3 data/ccn.sqlite ".backup 'data/sauvegarde-$(date +%F).sqlite'"
tar czf media-$(date +%F).tgz data/media
```

Reprise = restaurer les deux fichiers dans `data/`, redémarrer le conteneur
(la base s’ouvre, les migrations s’appliquent, le site ressert les mêmes documents).

## 5. Kubernetes — Helm

`helm/` : Deployment (une réplica, image `runtime`), Service (ClusterIP 8890 → 3000),
Ingress (TLS), PersistentVolumeClaim monté sur **`/app/data`** — le même contrat que le
bind mount Docker : base SQLite + documents + outbox.

## 6. Procédure de reprise (développement)

```bash
cd _shared/ccn-svelte5/app
npm install                       # (node_modules absent)
npm run prepare                   # svelte-kit sync (génère .svelte-kit/tsconfig.json)
npm run db:apply                  # migrations sur data/ccn.sqlite
npm run db:seed && npm run db:comptes-demo   # jeu de démonstration + 6 comptes
npm run dev -- --port 5174 --host 0.0.0.0
# http://192.168.100.50:5174
npm run check && npm run build    # contrôles avant toute livraison
```

Comptes de démo : `demo.webmestre / demo-webmestre`, `demo.admin-restreint /
demo-admin-restreint`, `demo.prof / demo-prof`, `demo.prof-sso / demo-prof-sso`,
`demo.intervenant / demo-intervenant`, `demo.eleve / demo-eleve`.
Les mots de passe du dump réel sont hachés en bcrypt côté SPIP : 35 312 comptes sur
35 323 n’ont pas de mot de passe local (SSO) — la connexion locale ne peut se
vérifier que sur les comptes de démo.

## 7. Contrôle CSRF en prévisualisation HTTP (écart connu, mesuré)

SvelteKit compare l’`Origin` des requêtes de formulaire à son **self-origin**. En build
adapter-node, sans en-tête de protocole, self-origin est calculé en `https://…` ; une
instance publiée en HTTP direct sur l’IP LAN refuse donc les POST de formulaire
(`403 Cross-site POST form submissions are forbidden`), ce qui casse la connexion, le
forum, la publication.

Mesure sur l’instance conteneur `:8890` :

| Requête | Résultat |
| --- | --- |
| `POST /connexion` avec `Origin: http://192.168.100.50:8890` | **403** |
| `POST /connexion` avec `Origin: https://192.168.100.50:8890` | **200** |
| même formulaire sur le serveur de dev (`vite dev`, `:5174`) | **200** (session montée, cookie sans `Secure`) |

Conduite à tenir : servir l’instance derrière un frontal TLS (comme la production
`https://ccn.laclasse.com`) avec `PROTOCOL_HEADER=x-forwarded-proto` et
`HOST_HEADER=x-forwarded-host`. La prévisualisation en HTTP clair sert à la parité des
pages, des contrats JSON et des listes (lecture), **pas** aux parcours connectés.
