# CCN Monorepo

![](plugins/thematique/th.png)


This monorepo contains all the plugins for SPIP CMS :
- Thematique
- Fictions


## 🚀 Quickstart

### Requirements
- Docker

### Setup env
1. Copy `.env.example` to `.env` and fill in the necessary values.


| Variable          | Description                                                    |
| ----------------- | -------------------------------------------------------------- |
| SPIP_AUTO_INSTALL | Auto install Spip if not installed                             |
| SPIP_DB_SERVER    | Database server init and the one used by Spip configuration    |
| SPIP_DB_LOGIN     | Database username  init and the one used by Spip configuration |
| SPIP_DB_PASS      | Database password init and the one used by Spip configuration  |
| SPIP_DB_NAME      | Database name  init and the one used by Spip configuration     |
| SPIP_SITE_ADDRESS | Site URL                                                       |
| SPIP_ADMIN_EMAIL  | Default init Admin email                                       |
| SPIP_ADMIN_PASS   | Default init  Admin password                                   |
| SPIP_VERSION_SITE | Défault thematique, possibilite : fictions                     |


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