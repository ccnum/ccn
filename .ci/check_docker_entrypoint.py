#!/usr/bin/env python3
"""
Vérifie que docker-entrypoint.sh contient toujours les blocs de configuration
spécifiques CCN (activation des plugins, réglages spip config:ecrire,
contenu de mes_options.php, génération de _config_cioidc.php).

Contexte : le commit 7a16c258 ("aligné sur ipeos-and-co/docker-spip") a
remplacé le script par une version durcie mais a fait disparaître ces blocs
en même temps, sans que ce soit voulu. Ce check évite qu'un futur
alignement sur le template upstream refasse la même chose en silence.

Si un renommage/refactor légitime fait disparaître un marqueur, mettre à
jour REQUIRED_MARKERS en conséquence dans le même commit.
"""

import sys
from pathlib import Path

REPO_ROOT = Path(__file__).resolve().parent.parent
ENTRYPOINT = REPO_ROOT / "docker-entrypoint.sh"

# (marqueur, explication affichée si absent)
REQUIRED_MARKERS = [
    ("spip plugins:activer ccn", "activation du plugin ccn"),
    ("SPIP_PLUGINS_CIOIDC", "activation conditionnelle du plugin cioidc"),
    ("spip config:ecrire formats_documents_forum", "réglage des formats de forum autorisés"),
    ("spip config:ecrire image_process:imagick", "bascule GD -> Imagick pour les vignettes"),
    ("config/mes_options.php", "génération de config/mes_options.php"),
    ("_IMG_MAX_WIDTH", "limite de résolution image dans mes_options.php"),
    ("_VIMEO_ACCESS_TOKEN", "config api_vimeo dans mes_options.php"),
    ("_CCN_PROJET_ACTIVE", "flag rentrée automatique dans mes_options.php"),
    ("config/_config_cioidc.php", "génération de config/_config_cioidc.php"),
    ("_CIOIDC_CLIENT_SECRET", "secret client SSO dans _config_cioidc.php"),
]


def main() -> int:
    if not ENTRYPOINT.exists():
        print(f"ERREUR: {ENTRYPOINT} introuvable", file=sys.stderr)
        return 1

    content = ENTRYPOINT.read_text(encoding="utf-8")

    missing = [(marker, why) for marker, why in REQUIRED_MARKERS if marker not in content]

    if not missing:
        print("OK: docker-entrypoint.sh contient tous les marqueurs attendus.")
        return 0

    print("ERREUR: docker-entrypoint.sh a perdu des blocs de configuration CCN :", file=sys.stderr)
    for marker, why in missing:
        print(f"  - marqueur absent: {marker!r} ({why})", file=sys.stderr)
    print(
        "\nSi c'est intentionnel, mets à jour REQUIRED_MARKERS dans "
        ".ci/check_docker_entrypoint.py dans le même commit.",
        file=sys.stderr,
    )
    return 1


if __name__ == "__main__":
    sys.exit(main())
