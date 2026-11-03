# Parité SPIP → SvelteKit — grille, écarts, dispositions (phase 9, VOCO-67)

## Méthode et instances

| Instance | URL | Base | Sert à |
| --- | --- | --- | --- |
| SPIP de référence | `http://192.168.100.50:8880` | MariaDB du dump `2026-08-11` (474 articles, 111 rubriques, 35 328 auteurs, 1 027 forums) | **oracle** : captures `baseline/json/*.spip.*` |
| Cible — démo | `http://192.168.100.50:8890` (Docker) · `:5174` (dev) | `data/ccn.sqlite` (jeu de démonstration + 6 comptes) | grille pages × rôles × années, formulaires |
| Cible — parité | `http://192.168.100.50:8891` (Docker) | `data/ccn-reel.sqlite` (ETL du dump réel) | contrats JSON, listes, RSS |

Outils : `npm run verif:json` (83 captures), `npm run verif:listes`,
`npm run parite:grille` (22 pages × 6 rôles × 3 années + 10 formulaires),
`npm run test:auth` (sessions, rôles, périmètres).

Contrats particuliers intégrés à l’outil : une capture **vide** (SPIP renvoie `"\n"`,
cas de `json/jalons.html`) et une capture **404** (SPIP sans squelette, cas de
`?page=rss`) sont des contrats — l’app doit renvoyer corps vide / 404 pareil.

## 1. Contrats JSON et flux — 39 conformes / 44 non conformes (base réelle, anonyme)

| # | Écart | Cause | Disposition |
| --- | --- | --- | --- |
| 1 | Titres/noms : SPIP `Mme&nbsp;BALMINO`, `MT1&nbsp;: …` ; app `Mme BALMINO` | l’ETL décode les entités SPIP à l’import ; le contrat JSON, lui, doit rendre le texte **brut** (SPIP rend l’entité) | **à corriger (phase 6)** — 18 captures (`classes`, `consignes`, `demarrage`, `articles-blogs`) |
| 2 | `consignes: []` côté SPIP pour un visiteur, liste complète côté app | le contrat `consignes`/`demarrage` n’applique pas le filtre d’accès anonyme de `json/consignes.html` | **à corriger (phase 6)** — exposition de données non publiques, 18 captures |
| 3 | Clés `commentaires`, `date`, `id`, `image`, `image_est_url`, `intervenant_id` présentes côté app, absentes côté SPIP | SPIP omet les clés vides (`#SET{}), l’app les émet toujours | **accepté** (sur-ensemble, consommable par le thème) — à tracer, corrigé en phase 6 si un consommateur s’en plaint |
| 4 | `projet.url_popup_classes` : SPIP `id_rubrique=1` (racine `travail_en_cours`), app rubrique de l’année | `idRubriqueProjet()` résout l’année, pas le type d’objet demandé | **à corriger (phase 6)** — 9 captures |
| 5 | `rss_forum` : `link`/`guid` vides côté SPIP, `dc:creator`/`description` composés différemment ; titre du flux dépend du `nom_site` | squelette `json/rss_forum.html` (champs vides en SPIP) ; nom d’instance | **nom site : corrigé** (`CCN_NOM_SITE=ontourne` sur l’instance de parité, +25 captures conformes) ; **items : à corriger (phase 6)** — 8 captures |
| 6 | `?page=rss` → 404 des deux côtés, `jalons` → corps vide des deux côtés | SPIP n’a pas de squelette `rss.html`, `jalons` n’existe qu’en inclusion de `demarrage` | **conformes** (contrats vides/404 reconnus par l’outil) |

**Effet mesuré des corrections de méthode** : 14 → 39 captures conformes sans toucher au
code applicatif (outillage des contrats vides/404 + nom de site de l’instance).

## 2. Pages × rôles × années (396 observations)

Grille complète : `baseline/PARITE-dev.md` (serveur de dev, sessions montées) et
`baseline/PARITE-demo.md` (instance conteneur).

Totaux (dev) : **126 en 200, 168 redirections, 66 erreurs, 36 marqueurs DOM manquants,
0 non testé**.

| Constat | Détail | Disposition |
| --- | --- | --- |
| `/` et `/timeline` servies sans les marqueurs `id="global"` / `id="frise"` | 36 cellules sur 396 | **à faire (phase 4)** : le canevas `inclure/*` n’a pas repris les `id` du squelette SPIP |
| `/blog`, `/evenements`, `/livrables`, `/connexion` : 200 pour les six rôles, les trois années | — | **conforme** |
| `/admin`, `/admin/comptes`, `/admin/taches`, `/admin/classes-vides`, `/profil`, `/classes`, `/salle-des-profs`, `/espace-eleve` : 302 pour tous les rôles | le garde renvoie vers `/connexion` : la grille ne porte que le cookie de session, pas le cookie d’année que le garde exige ; vérifié à part : `npm run test:auth` → webmestre connecté, `/admin` **200** | **outil à corriger** (grille), parcours **conforme** sous `test:auth` |
| `/article/20`, `/ressources/detail/20`, `/syndic-article/20` : 404 sur la base de démo | ces ids n’existent pas dans le jeu de démo ; la grille prend les ids dans les contrats de l’instance | **non significatif** sur démo ; à rejouer sur l’instance parité (ids réels) |
| `/classes/20` : 404 en 2023-2024, 200 en 2025 | les classes de démo n’existent que sur l’année courante | **non significatif** |
| 330 cellules non testées sur l’instance conteneur | sessions impossibles en HTTP clair (CSRF, §4) | **à corriger (exploitation, frontal TLS)** |

## 3. Formulaires

Instance conteneur (HTTP clair) : **les 10 sondages en 403** → voir §4.
Instance de dev (sessions montées) :

| Formulaire | SPIP | App (dev) | Disposition |
| --- | --- | --- | --- |
| `/connexion` (mauvais mot de passe) | page rechargée avec erreur | **401** | **accepté** (API JSON ; le formulaire HTML rend l’erreur, vérifié par `test:auth`) |
| `/api/auth/connexion` | — | **400** sur corps vide | **conforme** (refus d’un corps vide) |
| `/classes?/choisir` | POST → 302 + classe active | **401** (sonde sans corps) | **conforme** sous `test:auth` (choix de classe vérifié, `classe_active` mise à jour) |
| `/profil?/enregistrer` | POST → profil enregistré | **200** | **conforme** |
| `/admin/comptes?/importer` | import CSV | **200** | **conforme** (phase 3) |
| `/forum` (forumv2) | poster un commentaire | **404** | **à faire (phase 7)** |
| `/publier` | publier un article | **404** | **à faire (phase 7)** |
| `/joindre-video` | joindre une vidéo Vimeo | **404** | **à faire (phase 7)** |
| `/mot-de-passe-video` | définir le mot de passe d’une vidéo | **404** | **à faire (phase 7)** |
| `/titrer-document` | titrer un document joint | **404** | **à faire (phase 7)** |

## 4. Écart d’exploitation mesuré (sessions en HTTP clair)

En build `adapter-node`, le self-origin de SvelteKit se calcule en `https://…` quand
aucun en-tête de protocole n’est fourni : les POST de formulaire venant d’une origine
`http://` sont refusés (`403 Cross-site POST form submissions are forbidden`), alors
que les mêmes parcours réussissent sur le serveur de dev.

| Sonde | Résultat |
| --- | --- |
| `POST /connexion` avec `Origin: http://192.168.100.50:8890` | 403 |
| `POST /connexion` avec `Origin: https://192.168.100.50:8890` | 200 |
| `POST /connexion` sans en-tête `Origin` | 403 |

**Disposition : conduite d’exploitation** — servir derrière un frontal TLS avec
`PROTOCOL_HEADER=x-forwarded-proto`, `HOST_HEADER=x-forwarded-host`
(`docs/deploiement.md` §7). La prévisualisation LAN en HTTP clair reste valable pour la
parité de lecture (pages, contrats JSON, listes, documents).

## 5. Ce que la parité ne peut pas couvrir aujourd’hui

- Rôles sur données réelles : 35 312 comptes du dump sur 35 323 n’ont pas de mot de passe
  local (hachage SPIP côté ENT/SSO) — la parité des parcours connectés se vérifie sur les
  six comptes de démo, pas sur le dump.
- Comparaison **visuelle** du rendu (canevas, CSS) : hors de portée des outils de contrat ;
  le contrôle des marqueurs DOM (`id`/classes) n’est qu’un indicateur.
