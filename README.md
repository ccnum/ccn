# CCN

![](plugins/thematique/th.png)


This monorepo contains all the plugins for SPIP CMS :
- Thematique
- Fictions


## 🚀 Quickstart

### Requirements
- Docker

### Setup env
1. Copy `.env.example` to `.env` and fill in the necessary values.


| Variable           | Description                                                    |
| -----------------  | -------------------------------------------------------------- |
| SPIP_AUTO_INSTALL  | Auto install Spip if not installed                             |
| SPIP_DB_SERVER     | Database server init and the one used by Spip configuration    |
| SPIP_DB_LOGIN      | Database username  init and the one used by Spip configuration |
| SPIP_DB_PASS       | Database password init and the one used by Spip configuration  |
| SPIP_DB_NAME       | Database name  init and the one used by Spip configuration     |
| SPIP_SITE_ADDRESS  | Site URL                                                       |
| SPIP_ADMIN_EMAIL   | Default init Admin email                                       |
| SPIP_ADMIN_PASS    | Default init Admin password                                    |
| SPIP_VERSION_SITE  | Défault thematique, possibilite : fictions, erasme             |
| SPIP_PLUGINS_CICAS | Mettre true si il faut le plugins CICAS                        |
| PROJET             | Défault laclasse                                               |

## Gestion des versions
Afin de proposer des images docker versionnées, une ci a été mise en place.
A chaque pull request fermée sur la branche `main`, un tag est créé sur le répo github et une image docker est construite et pushée sur le dockerhub.

### Incrémentation de la version
Les version sont déclarés de la manière suivante : `X.Y.Z` où X est le numéro de version majeur, Y le numéro de version mineur et Z le numéro de version de patch.

Par défaut a chaque fermerture de pull request, le numéro de version de patch est incrémenté. Si la pull request est taggée avec un label `#major` ou `#minor`, le numéro de version majeur ou mineur est incrémenté.

### Exemples

nom de pull request : `Maj de code / rajout de fonction sur les CCN`

Si la pull request est fermée sans label, la version sera incrémentée de la manière suivante : `1.0.1`

nom de pull request : `#minor - MAJ de version Y de SPIP`

Si la pull request est fermée avec le label `#minor`, la version sera incrémentée de la manière suivante : `1.1.0`

nom de pull request : `#major - MAJ de version X de SPIP`

Si la pull request est fermée avec le label `#major`, la version sera incrémentée de la manière suivante : `2.0.0`

### Start the project
1. Run `docker-compose up` to start the containers in detached mode.

### ❤️ Humans.txt
- pierretux
- pipazoul
- Anthony Angelot
- Christophe Monnet
- Juliette Monaco
- Patrick Vincent
- Thomas Graveleine
- Thomas Neuveu