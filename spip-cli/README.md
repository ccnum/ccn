# Outil spip-cli

spip-cli est un outil pour commander SPIP depuis la ligne de commandes.

## Documentation

https://contrib.spip.net/SPIP-Cli

## Installation

Cet utilitaire nécessite PHP >= 7.4 ; il requiert [Composer](https://getcomposer.org/).

Récupérer un archive ou télécharger le dépot, et lancer l’installation Composer

```sh
git clone https://git.spip.net/spip-contrib-outils/spip-cli.git
cd spip-cli
composer install
```

La commande `spip` se trouve dans le répertoire `bin`. Il sera plus simple de rendre la commande utilisable partout en la liant par exemple dans `/usr/local/bin`

```sh
ln -s bin/spip /usr/local/bin
```

## Utiliser spip-cli

Pour connaître les commandes disponibles, lancer `spip` dans un shell

```text
$ spip

SPIP Cli 2.0.0

Usage:
  command [options] [arguments]

[...]

 auteurs
  auteurs:changer:mdp         Changer le mot de passe d'un auteur
  auteurs:changer:statut      Changer le statut d'un auteur
  auteurs:creer               Créer ou modifier un auteur (identifié selon id, sinon login, sinon email)
  auteurs:envoyer:lien:oubli  Envoyer un mail d'oubli de mot de passe à l'auteur, avec un lien pour le réinitialiser.
  auteurs:lister              Liste les auteurs d'un site
  auteurs:superadmin          [root:me] Ajoute / supprime un webmestre observateur (id_auteur = -1).
 cache
  cache:desactiver            Désactive le cache de spip pendant 24h.
  cache:reactiver             Réactive le cache de spip.
  cache:vider                 Vider le cache.
 config
  config:ecrire               Ecrire une option de configuration dans spip_meta
  config:lire                 Lire une option de configuration depuis spip_meta
[...]
```

## Installer SPIP avec SPIP-cli

Les 3 étapes obligatoires sont : `core:telecharger`, `core:preparer`, `core:installer`.

Voici un exemple (pour le détail des arguments, voir la doc) :

```bash
spip dl
spip core:preparer -d 2770 --auto
spip install --db-server mysql --db-host localhost --db-login mysqluser --db-database mysqldb --db-pass XXXX
```

## synchro SPIP

> Synchroniser un spip distant sur un spip local, bdd / fichiers / modif des metas
>
> ATTENTION, pour l'instant ne fonctionne que sur une bdd en mysql

3 actions possibles :

* `spip synchro:init` creation d'un fichier json : synchroSPIP.json à la racine du SPIP, il restera un peu de configuration à faire.
* `spip synchro:bdd` pour lancer la synchro de la bdd et la modif des metas
* `spip synchro:fichiers` pour lancer la synchro des fichiers via rsync

`spip synchro:bdd` : Il y a 3 args facultatifs

* -v : verbeux
* -b ou --backup: forcer le backup local de la bdd
* -r ou --rsync: lancer à la fin les commandes rsync du script synchro:fichiers

`spip synchro:fichiers` : Il y a 1 arg facultatif

* -v : verbeux

Fichier de configuration synchroSPIP.json

* Il y a 2 façons pour ouvrir une connexion ssh :
  * via : user / hostName / port ex : `ssh toto@spip.net -p 1234`, si chemin_cle est defini, on pourra choisir une cle ssh dans un dossier autre que .ssh
  * via: host (il faut l'avoir défini dans .ssh/config) ex: `ssh mon_host_spip` dans ce cas, pas besoin de renseigner les autres champs dans config_ssh
  * Il faut avoir une cle ssh
* Configuration pour le rsync : Chaque ligne représente : chemin local => chemin distant:
  * le chemin local peut-être en relatif
  * le chemin distant doit etre en absolue et se terminer par '/'
* Configuration pwd mysql distant: si dans le fichier de configuration, on indique pas le pwd mysql, il sera demandé en console

exemple :

```json
"rsync": {
  "IMG": "chemin abs vers IMG/",
  "plugins": "chemin abs vers plugins/"
}
```
