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
| SPIP_VERSION_SITE | Défault thematique, possibilite : fictions, petitfablab        |


### Start the project
1. Run `docker-compose up` to start the containers in detached mode.



## 🔩 CI config

The CI is configured to run on every push and pull request on the `dev` branch. It will:
1. Build the Docker images and push them to Docker Hub if the build is successful.
2. Redeploy the workflows on the `dev` branch if the build is successful.

### Setup workloads
Github secrets are used to store the Docker Hub credentials and the Github token. These secrets should be added in your repository settings under `Secrets` with the following names:
| Name             | Description                                                                                                                                                            |
| ---------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| DOCKER_USERNAME  | Docker Hub username                                                                                                                                                    |
| DOCKER_PASSWORD  | Docker Hub password                                                                                                                                                    |
| WORKLOADS        | the name of the workloads you want to redeploy seperated by a comma  ex: workload1,workload2                                                                           |
| WORKLOADS_PROD   | the name of the workloads you want to redeploy on production env seperated by a comma  ex: workload1,workload2                                                         |
| RANCHER_DEV_URL  | the url  to your rancher api url with the name of your namespace and cluster ex: https://mycluster.com/v3/project/mycluster:myproject/workloads/deployment:mynamespace |
| RANCHER_PROD_URL | same as above but to redeploy workloads when main branch ci is triggered                                                                                               |
|                  |                                                                                                                                                                        |

### ❤️ Humans.txt
- pierretux
- pipazoul
- Anthony Angelot
- Christophe Monnet
- Juliette Monaco
- Patrick Vincent
- Thomas Graveleine
- Thomas Neuveu