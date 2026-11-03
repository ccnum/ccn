# Budget de migration des sites jumeaux (base commune `ccn`)

À faire valider par **yassin** avant lancement. Phase 9 (VOCO-67), livraison du bilan.

## 1. Périmètre

Ce budget couvre les **sites jumeaux du dépôt `ccnum/ccn`** qui branchent sur la base
commune migrée en SvelteKit (phases 1 → 9 de `MIGRATION_PLAN.md`) :

| Site | Plugin SPIP | Squelettes | PHP | Ce que c’est |
| --- | --- | --- | --- | --- |
| `ccn` (On tourne) | `thematique` | 136 `.html` | 27 `.php` | **pilote**, migré en phases 1-8 |
| `fictions` | `fictions` + socle `cadavrexquis` | 45 `.html` | 18 `.php` | cadavre exquis : classes + écrivain, chapitres attribués par rotation, calendrier d’écriture |
| `petitfablab` | `petitfablab` + socle `cadavrexquis` | 24 `.html` | 5 `.php` | ateliers en médiathèque : écriture libre, 5 chapitres par histoire, ni rôles ni plan ni calendrier |
| `thematique_edifice` | variante de `thematique` | 0 `.html` | 2 `.php` | variante éditoriale : mêmes squelettes, options et habillage différents |

Fait reference : décompte dans le dépôt SPIP (`plugins/projets/*`), descriptions
`docs/fictions.html`, `docs/petitfablab.html`, `docs/thematique.html`.

## 2. Ce que la base commune apporte déjà

Acquis du pilote `ccn`, réutilisable sans reprise :

- schéma Drizzle + migrations + ouvertures/vues (`src/lib/server/db`) ;
- ETL SPIP → SQLite (`scripts/etl-spip-to-sqlite.ts`) sur le modèle dump réel ;
- authentification ENT/SSO + mots de passe locaux, rôles, périmètres (phase 3) ;
- pipeline texte/année/rôle (parité `ccn_options.php`, `thematique_*`) ;
- contrats JSON + RSS + service de documents (`?exec=document`) ;
- transport e-mail (`mock` / `smtp` / `aucun`) + journal des notifications ;
- tâches de fond (`cron`/`tache`), écran `/api/etat`, `/admin/taches` ;
- exploitation : `Dockerfile` (2 cibles), `docker-compose.yml` (+ surcharge parité),
  chart Helm, variables `CCN_*` (`docs/deploiement.md`).

**Écarts du pilote à solder avant de brancher un jumeau** (ils se retrouveraient
à l’identique sur les trois sites) :

| Écart | Preuve | Impact sur les jumeaux |
| --- | --- | --- |
| 44/83 contrats JSON non conformes | `npm run verif:json` sur l’instance parité (`:8891`) | titres `&nbsp;`, consignes visibles à l’anonymous, clés absentes côté SPIP, `url_popup_classes` |
| CSRF en HTTP clair (POST 403) | `docs/deploiement.md` §7 | bloque tout parcours connecté en prévisualisation |
| Canevas `inclure/*` incomplet (phase 4) | `baseline/PARITE-demo.md` : 6 pages sans marqueur DOM | les jumeaux partagent l’en-tête commun (`head-commun.html`, `tete-html.html`) |
| Formulaires d’écriture (phase 7) | idem | `fictions` et `petitfablab` sont **entièrement** formulaires (chapitres) |

## 3. Budget par site

Les charges sont données en **phases du plan** (jauge : la durée des phases du pilote,
à ajuster par yassin). Les phases 1-3 et 9 sont **mutualisées** (schéma, ETL, auth,
exploitation) : un jumeau ne reprend que le calque métier.

### `fictions`

| Phase | Contenu | Charge |
| --- | --- | --- |
| 2 (suite) | tables `cadavrexquis` : histoires, chapitres, prologues, attribution par rotation | 1 demi-phase |
| 5-6 | squelettes : accueil par atelier, histoire en cours, chapitre caché/visible, calendrier | 1 phase |
| 7 | formulaires : écrire un chapitre, attribuer, clore une histoire ; **vue « fin du chapitre précédent » uniquement** (règle du cadavre exquis) | 1,5 phase |
| 8 | e-mails de rotation (auteur du chapitre suivant), fin d’histoire | ½ phase |
| **Total** | | **≈ 3,5 phases** |

Point de vigilance : la règle « ne voir que la fin du chapitre précédent » est une
**règle d’accès**, à porter dans les filtres serveur (parité `fictions_*`), pas dans le JS.

### `petitfablab`

| Phase | Contenu | Charge |
| --- | --- | --- |
| 2 (suite) | réutilise les tables `cadavrexquis` ; rubriques `jeu`/`Atelier`/`Histoire` | ¼ phase |
| 5-6 | accueil = atelier le plus récent (ou `rub_jeu`), grille histoires en cours/finies, 5 chapitres max | ½ phase |
| 7 | écriture libre d’un chapitre (compte animateur), pas de rôles | ½ phase |
| 8 | aucun (pas de notifications propres) | — |
| **Total** | | **≈ 1 à 1,25 phase** |

### `thematique_edifice`

| Phase | Contenu | Charge |
| --- | --- | --- |
| 2 (suite) | aucune table nouvelle | — |
| 5-7 | differences d’options (`thematique_edifice_options.php`) + habillage ; les squelettes sont ceux de `thematique` | ½ phase |
| **Total** | | **≈ 0,5 phase** |

### Total jumeaux : **≈ 5 phases** (≈ 15 % du chantier du pilote), dont **1,5 phase de
corrections du pilote** à mener d’abord (contrats JSON + canevas + formulaires).

## 4. Ordre proposé

1. Souder les écarts du pilote (contrats JSON, canevas, formulaires, CSRF en frontal TLS).
2. `petitfablab` d’abord : le plus petit, il valide le socle `cadavrexquis`.
3. `fictions` : même socle, + règles d’accès et calendrier.
4. `thematique_edifice` en dernier : calque d’options sur un `thematique` déjà conforme.

## 5. Critères d’acceptation communs

- `npm run check` et `npm run build` sans erreur ;
- `docker compose up` sert le site sur l’IP LAN, healthcheck vert ;
- `npm run verif:json` et `npm run parite:grille` sur instance parité : **aucun écart
  non listé** (tout écart restant est nommé, chiffré, et accepté ou corrigé) ;
- pour les deux sites d’écriture : parcours complet « prologue → chapitres → histoire
  finie » vérifié en session, avec la règle de confidentialité du cadavre exquis.

## 6. À valider par yassin

- [ ] périmètre des trois sites (le tableau §1) ;
- [ ] jauge : les phases du pilote sont-elles la bonne unité de budget ;
- [ ] ordre §4 (et arbitrages si un site doit passer avant la soudure du pilote) ;
- [ ] production des dumps : un dump MariaDB par site jumeau pour alimenter l’ETL et la
      parité (mêmes conditions que `baseline/2026-08-11.sqlite` pour `ccn`) ;
- [ ] nom de site / URL par instance (`CCN_NOM_SITE`, `CCN_SITE_URL`) et frontal TLS
      (condition du fonctionnement des formulaires, `docs/deploiement.md` §7).
