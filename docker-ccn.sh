#!/bin/bash
set -e

if [[ ! -e config/connect.php && ${SPIP_AUTO_INSTALL} = 1 ]]; then
	spip plugins:activer saisies -y
	spip plugins:activer yaml -y
	spip plugins:activer cextras -y
	spip plugins:activer crayons -y
	spip plugins:activer facteur -y
	spip plugins:activer jqueryui -y
	spip plugins:activer notation -y
	spip plugins:activer notifications -y
	spip plugins:activer socialtags -y
	spip plugins:activer spip_bonux -y
	spip plugins:activer cicas -y
	spip plugins:activer corbeille -y
	spip plugins:activer import_utilisateurs -y
	spip plugins:activer simplog -y
	if [ ${SPIP_VERSION_SITE} = "fictions" ]; then
		spip plugins:activer vider_rubrique -y
	fi
	spip plugins:activer ${SPIP_VERSION_SITE} -y
	spip plugins:maj:bdd
fi

exec "$@"