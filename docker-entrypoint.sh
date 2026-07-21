#!/bin/bash
set -e

run_as() {
	if [ "$(id -u)" = 0 ]; then
		su -p www-data -s /bin/sh -c "$1"
	else
		sh -c "$1"
	fi
}

# version_greater A B returns whether A > B
version_greater() {
    [ "$(printf '%s\n' "$@" | sort -t '.' -n -k1,1 -k2,2 -k3,3 | head -n 1)" != "$1" ]
}

wait_for_db() {
	until nc -z -v -w60 "${SPIP_DB_HOST}" "3306"; do
		echo "Waiting for database ready..."
		sleep 5
	done
}

installed_version="0.0.0"
image_version="0.0.1"

if [ -f "/var/www/html/ecrire/inc_version.php" ]; then
	installed_version=$(grep -i /var/www/html/ecrire/inc_version.php  -e '\$spip_version_branche =' | cut -d '=' -f 2 | cut -d ';' -f 1 | cut -d "'" -f 2 | cut -d '"' -f 2)
	image_version=$(grep -i /usr/src/spip/ecrire/inc_version.php  -e '\$spip_version_branche =' | cut -d '=' -f 2 | cut -d ';' -f 1 | cut -d "'" -f 2 | cut -d '"' -f 2)
fi

echo $installed_version
echo $image_version

if version_greater "$installed_version" "$image_version"; then
	echo "Can't start SPIP because the version of the data ($installed_version) is higher than the docker image version ($image_version) and downgrading is not supported. Are you sure you have pulled the newest image version?"
	exit 1
fi

# Reconfigure php.ini
# set PHP.ini settings for SPIP
( \
echo 'display_errors=Off'; \
echo 'error_log=/var/log/apache2/php.log'; \
echo 'max_execution_time=${PHP_MAX_EXECUTION_TIME}'; \
echo 'memory_limit=${PHP_MEMORY_LIMIT}'; \
echo 'post_max_size=${PHP_POST_MAX_SIZE}'; \
echo 'upload_max_filesize=${PHP_UPLOAD_MAX_FILESIZE}'; \
echo 'date.timezone=${PHP_TIMEZONE}'; \
) > /usr/local/etc/php/conf.d/spip.ini

# Configure Apache port
if [ "${APACHE_PORT}" != "80" ]; then
    echo "Listen ${APACHE_PORT}" > /etc/apache2/ports.conf
    sed -i "s/:80>/:${APACHE_PORT}>/g" /etc/apache2/sites-available/000-default.conf
fi


if version_greater "$image_version" "$installed_version"; then
	echo >&2 "SPIP upgrade in $PWD - copying now..."
	if [ "$(ls -A)" ]; then
		echo >&2 "WARNING: $PWD is not empty"
	fi
	tar cf - --one-file-system -C /usr/src/spip . | tar xf -
	echo >&2 "Complete! SPIP has been successfully copied to $PWD"

	echo >&2 "Create plugins, libraries and template directories"
	mkdir -p plugins/auto
	mkdir -p lib
	mkdir -p squelettes
	mkdir -p tmp/{dump,log,upload}
	chown -R www-data:www-data plugins lib squelettes tmp

	if [ ! -e .htaccess ]; then
		cp -p htaccess.txt .htaccess
		chown www-data:www-data .htaccess
	fi

	if [ ${SPIP_DB_SERVER} = "mysql" ]; then
		wait_for_db
	fi

	# Upgrade SPIP
	if [ -f config/connect.php ]; then
		spip core:maj:bdd
		spip plugins:maj:bdd
	fi
fi

# Install SPIP
if [ ${SPIP_DB_SERVER} = "mysql" ]; then
	wait_for_db
fi
if [[ ! -e config/connect.php && ${SPIP_AUTO_INSTALL} = 1 ]]; then
	# Wait for mysql before install
	# cf. https://docs.docker.com/compose/startup-order/
	run_as "spip install \
		--db-server ${SPIP_DB_SERVER} \
		--db-host ${SPIP_DB_HOST} \
		--db-login ${SPIP_DB_LOGIN} \
		--db-pass ${SPIP_DB_PASS} \
		--db-database ${SPIP_DB_NAME} \
		--db-prefix ${SPIP_DB_PREFIX} \
		--adresse-site ${SPIP_SITE_ADDRESS} \
		--admin-nom ${SPIP_ADMIN_NAME} \
		--admin-login ${SPIP_ADMIN_LOGIN} \
		--admin-email ${SPIP_ADMIN_EMAIL} \
		--admin-pass ${SPIP_ADMIN_PASS}" || true

    # Try to depote the repository
    #if ! spip plugins:svp:depoter https://plugins.spip.net/depots/principal.xml; then
    #    echo "Warning: Unable to depote repository https://plugins.spip.net/depots/principal.xml"
        # Optionally handle this error differently, or just continue
    #fi
fi

spip plugins:activer cextras -y
spip plugins:activer crayons -y
spip plugins:activer corbeille -y
spip plugins:activer facteur -y
spip plugins:activer jqueryui -y
spip plugins:activer notation -y
spip plugins:activer notifications -y
spip plugins:activer oembed -y
spip plugins:activer saisies -y
spip plugins:activer socialtags -y
spip plugins:activer spip_bonux -y
spip plugins:activer verifier -y
spip plugins:activer yaml -y
spip plugins:activer autorite -y
spip plugins:activer simplog -y
spip plugins:activer mesfavoris -y
spip plugins:activer mesfavoris_ccn -y
spip plugins:activer ccn -y

spip plugins:desactiver imports_utilisateurs -y

if [ ${SPIP_PLUGINS_CIOIDC} == true ]; then
	spip plugins:activer cioidc -y
	else
	spip plugins:desactiver cioidc -y
fi
if [ ${SPIP_VERSION_SITE} != "thematique" ]; then
	spip plugins:activer vider_rubrique -y
fi
spip plugins:activer ${SPIP_VERSION_SITE} -y
if [ ${PROJET} != "laclasse" ]; then
	spip plugins:activer thematique_${PROJET} -y
fi
spip plugins:maj:bdd

spip config:ecrire -p autorite auteur_mod_email:0
spip config:ecrire -p autorite auteur_mod_article:1
spip config:ecrire -p autorite auteur_modere_forum:0
spip config:ecrire -p autorite editer_forums:1
spip config:ecrire -p autorite publierdans:15
spip config:ecrire -p bigup charger_public:1
spip config:ecrire -p bigup max_file_size:100
# ne jamais redimensionner automatiquement les images trop grandes à l'upload :
# ce chemin décode l'image entière en GD/Imagick, ce que _IMG_MAX_WIDTH/_IMG_MAX_HEIGHT
# (mes_options.php) sont censés empêcher en rejetant l'upload à la place
spip config:ecrire creer_preview:non
# Utiliser Imagick plutôt que GD pour générer les vignettes : GD charge l'image
# entière en mémoire PHP (memory_limit) selon sa résolution, tandis qu'Imagick
# gère sa propre mémoire (policy.xml) et peut basculer sur disque, ce qui évite
# de faire planter le process PHP sur de grosses images.
spip config:ecrire image_process:imagick
spip config:ecrire -p mediabox active:oui
spip config:ecrire -p notation acces:ide
spip config:ecrire -p notation change_note:oui
spip config:ecrire -p notifications forum_article:0
spip config:ecrire -p notifications thread_forum:0
spip config:ecrire formats_documents_forum:".pdf,.jpg,.jpeg,.png,.gif,.mp4"

# Default mes_options
rm -rf config/mes_options.php
if [ ! -e config/mes_options.php ]; then
	/bin/cat << MAINEOF > config/mes_options.php
<?php
if (!defined("_ECRIRE_INC_VERSION")) return;
\$GLOBALS['taille_des_logs'] = 500;
define('_MAX_LOG', 500000);
define('_LOG_FILELINE', true);
define('_LOG_FILTRE_GRAVITE', 8);
define('_DEBUG_SLOW_QUERIES', true);
define('_BOUCLE_PROFILER', 5000);
define('_AUTORISER_TELECHARGER_PLUGINS', false);
define('_TITRER_DOCUMENTS', true);
// limiter la résolution des images acceptées : le serveur n'a que 300 Mo de
// RAM, un décodage GD/Imagick d'image trop grande fait planter le process
// (~15 Mpx = marge de sécurité avec le reste des process du serveur)
define('_IMG_MAX_WIDTH', 4500);
define('_IMG_MAX_HEIGHT', 3500);
// désactiver les notifications de mise à jour
define('_MAJ_NOTIF_EMAILS', '');
// des personalisations par projet
define('_PROJET', '${PROJET}');
?>
MAINEOF
fi

# TODO(#ISSUE): plugin cicas supprimé, ce fichier ne devrait plus jamais être
# généré nulle part mais peut subsister sur les déploiements existants -
# à retirer une fois qu'on est sûr qu'aucun environnement n'en a plus besoin
rm -f config/_config_cas.php

# Default _config_cioidc.php
rm -f config/_config_cioidc.php
if [ ${SPIP_PLUGINS_CIOIDC} = true ]; then
	/bin/cat << MAINEOF > config/_config_cioidc.php
<?php
if (!defined("_ECRIRE_INC_VERSION")) return;
define('_CIOIDC_MODE_AUTH', 'hybride');
define('_CIOIDC_NOM_SERVEUR', 'LaClasse');
define('_CIOIDC_URL_SERVEUR', 'https://www.laclasse.com');
define('_CIOIDC_CLIENT_NOM', '${SPIP_CIOIDC_CLIENT_NOM}');
define('_CIOIDC_CLIENT_SECRET', '${SPIP_CIOIDC_CLIENT_SECRET}');
define('_CIOIDC_UID_CHAMP_SPIP', 'login');
define('_CIOIDC_UID_CLAIM', 'uid');
define('_CIOIDC_CREER_AUTEUR', '6forum');
define('_CIOIDC_AUTHORIZATION_ENDPOINT', 'https://www.laclasse.com/sso/authorize');
define('_CIOIDC_TOKEN_ENDPOINT', 'https://www.laclasse.com/sso/token');
define('_CIOIDC_USERINFO_ENDPOINT', 'https://www.laclasse.com/sso/userinfo');
define('_CIOIDC_END_SESSION_ENDPOINT', 'https://www.laclasse.com/sso/logout');
define('_CIOIDC_JWKS_URI', 'https://www.laclasse.com/sso/jwks');
define('_CIOIDC_TOKEN_ENDPOINT_AUTH_METHODS_SUPPORTED', '["client_secret_post"]');
?>
MAINEOF
fi

# Check if DUMP_MEDIA and DUMP_DB are set, else echo no db/media to restore
#if [ -z "${DUMP_MEDIA}" ] && [ -z "${DUMP_DB}" ]; then
#    echo "No media and database to restore"
#elif [ -z "${DUMP_MEDIA}" ]; then
#    echo "No media to restore"
#else
#    # Restore media
#    echo "Restoring media"
#
#    # Check if ./IMG has files and if /tmp/dump/dump.sqlite exists
#    if [ "$(ls -A ./IMG)" ] && [ -f /tmp/dump/dump.sqlite ]; then
#        echo "Media and database are already restored, skipping restore step."
#    else
#        # Create /tmp/dump folder if it doesn't exist
#        mkdir -p tmp/dump
#
#        # Check if DUMP_MEDIA is a file or a directory
#        if [ -f "${DUMP_MEDIA}" ]; then
#            echo "DUMP_MEDIA is a file."
#        elif [ -d "${DUMP_MEDIA}" ]; then
#            echo "DUMP_MEDIA is a directory."
#        fi
#
#        # Download media using curl
#        echo "Downloading media archive..."
#        curl -o IMG.tar.gz -L "${DUMP_MEDIA}"
#
#        # Download database dump using curl
#        echo "Downloading database dump..."
#        curl -o tmp/dump/dump.sqlite -L "${DUMP_DB}"
#
#        # Extract the media archive
#        echo "Extracting media archive..."
#        tar -xzvf IMG.tar.gz
#
#        # Restore the database using SPIP CLI
#        echo "Restoring the database..."
#        spip sql:dump:restore --name dump
#
#        # Clean up the media archive
#        echo "Cleaning up downloaded archive..."
#        rm IMG.tar.gz
#    fi
#fi

exec "$@"