# CCN thematique — image de la stack cible (SvelteKit 3 / Svelte 5 + Drizzle + SQLite)
#
# Cibles : `runtime` = dernière étape, donc **cible par défaut** de `docker build .`
# (le site) ; l’image de l’opérateur s’obtient avec `--target outilleur`.
#
#   docker build -t ccn-sveltekit:local .                      # le site
#   docker build -t ccn-sveltekit:outilleur --target outilleur .   # scripts opérateur
#
# `npm ci --ignore-scripts` : better-sqlite3 13 distribue des prébinaires, la
# compilation node-gyp n’est pas nécessaire (et elle n’a pas d’accès réseau ici).

FROM node:24-bookworm-slim AS deps
WORKDIR /app
COPY package.json package-lock.json .npmrc ./
RUN npm ci --ignore-scripts

FROM deps AS build
WORKDIR /app
COPY . .
RUN npm run build

FROM node:24-bookworm-slim AS runtime-deps
WORKDIR /app
COPY package.json package-lock.json .npmrc ./
RUN npm ci --omit=dev --ignore-scripts && npm cache clean --force

# Service de l'opérateur : migrations, ETL SPIP → SQLite, tâches de fond (rentrée,
# poubelle, noms d'auteurs du forum, classes vides), écrans de vérification. Rien
# n'est envoyé en production par défaut : le transport e-mail est `mock`.
FROM deps AS outilleur
WORKDIR /app
COPY . .
# `svelte-kit sync` : le `tsconfig.json` du projet étend `$app/tsconfig` (généré dans
# `.svelte-kit/`). Sans ce fichier, tsx ne charge pas la config et son résolveur
# refuse les imports de dossier (`src/lib/server/db`) → `ERR_UNSUPPORTED_DIR_IMPORT`.
RUN npm run prepare
ENV CCN_DB_PATH=data/ccn.sqlite
ENV CCN_MIGRATIONS_FOLDER=drizzle
ENV CCN_MEDIA_RACINE=data/media
CMD ["npm", "run", "taches:cron"]

FROM node:24-bookworm-slim AS runtime
LABEL org.opencontainers.image.title="CCN (SvelteKit)"
LABEL org.opencontainers.image.description="CCN thematique — SvelteKit 3 / Svelte 5 + Drizzle + SQLite"
LABEL org.opencontainers.image.source="https://github.com/ccnum/ccn"
LABEL org.opencontainers.image.licenses="GPL-3.0-only"
ENV NODE_ENV=production
WORKDIR /app
COPY --from=runtime-deps /app/node_modules ./node_modules
COPY --from=build /app/build ./build
COPY --from=build /app/package.json ./package.json
# Les migrations sont appliquées à l'ouverture de la base (src/lib/server/db/index.ts) :
# le dossier doit être présent dans l'image, sinon une base neuve ne se structure pas.
COPY --from=build /app/drizzle ./drizzle
# `data/` = point de montage du volume (base + media + outbox + notifications.log).
RUN mkdir -p data/media && chown -R node:node /app/data
USER node
ENV HOST=0.0.0.0
ENV PORT=3000
ENV CCN_DB_PATH=data/ccn.sqlite
ENV CCN_MIGRATIONS_FOLDER=drizzle
ENV CCN_MEDIA_RACINE=data/media
EXPOSE 3000
# /api/etat lit SQLite : il prouve à la fois que le serveur répond et que la base s'est ouverte.
HEALTHCHECK --interval=30s --timeout=10s --start-period=20s --retries=5 \
	CMD node -e "fetch('http://localhost:3000/api/etat').then(r=>process.exit(r.ok?0:1)).catch(()=>process.exit(1))"
CMD ["node", "build"]
