# CCN 



## Gestion des versions
Afin de proposer des images docker versionnées, une ci a été mise en place. 
A chaque pull request fermée sur la branche `main`, un tag est créé sur le répo github et une image docker est construite et pushée sur le dockerhub.

### Incrémentation de la version
Les version sont déclarés de la manière suivante : `X.Y.Z` où X est le numéro de version majeur, Y le numéro de version mineur et Z le numéro de version de patch.

Par défaut a chaque fermerture de pull request, le numéro de version de patch est incrémenté. Si la pull request est taggée avec un label `#major` ou `#minor`, le numéro de version majeur ou mineur est incrémenté.

### Exemples

nom de pull request : `#1 - Ajout de la gestion des versions`

Si la pull request est fermée sans label, la version sera incrémentée de la manière suivante : `1.0.0`

nom de pull request : `#2 - Ajout de la gestion des versions #minor`

Si la pull request est fermée avec le label `#minor`, la version sera incrémentée de la manière suivante : `1.1.0`

nom de pull request : `#3 - Ajout de la gestion des versions #major`

Si la pull request est fermée avec le label `#major`, la version sera incrémentée de la manière suivante : `2.0.0`

nom de pull request : `#4 - Ajout de la gestion des versions`

Si la pull request est fermée sans label, la version sera incrémentée de la manière suivante : `2.0.1`

