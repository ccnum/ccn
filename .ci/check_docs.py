#!/usr/bin/env python3
"""
Vérifie que la documentation (docs/*.html, publiée sur GitHub Pages) ne
cite rien qui n'existe plus dans le code.

La doc est écrite à la main : un renommage de fonction, de constante, de
mot-clé ou de fichier la rend fausse sans que rien ne le signale. Ce check
relit chaque <code> en ligne des pages (les blocs <pre> sont ignorés : ce
sont des exemples et des arborescences, pas des références) et vérifie :

- un chemin (contient '/' ou finit par une extension connue) est versionné,
  à la racine ou relatif à un plugin de plugins/projets/ (ou à son
  squelettes/) ; '*' est traité comme un glob ;
- un identifiant snake_case (fonction, pipeline, mot-clé, champ, plugin)
  ou CONSTANTE_MAJUSCULE apparaît comme mot dans le code : plugins maison,
  docker-entrypoint.sh, .env.example, helm/, .ci/, .github/ ; suivi de
  '()', il doit être défini par 'function nom(' ;
- chaque variable de .env.example est documentée dans deploiement.html.

Ce qui contient un gabarit (<site>, …, xxx, ?, =, espace) et les images
Docker (erasme/spip-ccn:tag) ne sont pas vérifiés.

Seuls les fichiers versionnés (git ls-files) comptent, pour que le résultat
local soit celui de la CI : le cœur SPIP (ecrire/, prive/, plugins-dist/…),
installé par le Dockerfile, est ignoré par git et absent du checkout. Ce qui
vient de lui ou n'est pas versionné est listé dans HORS_DEPOT.

Pas de baseline : une référence introuvable est toujours une doc à
corriger (ou une entrée à ajouter à HORS_DEPOT).

Usage (depuis la racine du dépôt) :
  .ci/check_docs.py
"""

import fnmatch
import html
import re
import subprocess
import sys
from pathlib import Path

REPO_ROOT = Path(__file__).resolve().parent.parent
DOCS = REPO_ROOT / "docs"
PLUGINS = REPO_ROOT / "plugins" / "projets"
EXCLUDED_DIRS = {"vendor", "node_modules"}

# Chemins cités par la doc mais absents du dépôt versionné.
HORS_DEPOT = {
    # cœur SPIP, installé depuis l'archive par le Dockerfile
    "ecrire/", "prive/", "squelettes-dist/", "plugins-dist/",
    # URL de l'espace privé
    "/ecrire",
    # données et config écrites au démarrage du conteneur (docker-entrypoint.sh)
    "IMG/", "config/mes_options.php", "config/_config_cioidc.php",
    # config ddev locale, non versionnée
    ".ddev/config.yaml",
}
# Fonctions du cœur SPIP (ecrire/, absent du dépôt) citées par la doc.
FONCTIONS_SPIP = {"_T"}

CORPUS = [
    ("plugins/projets/", (".php", ".html", ".js", ".xml")),
    ("helm/", (".yaml",)),
    (".ci/", (".py", ".js", ".md")),
    (".github/", (".yml", ".yaml")),
]
CORPUS_FICHIERS = ["docker-entrypoint.sh", ".env.example", "docker-compose.yml", "Dockerfile"]

EXTENSIONS = (".php", ".html", ".js", ".css", ".yml", ".yaml", ".sh", ".md", ".py", ".neon", ".json", ".xml", ".png")
GABARIT = re.compile(r"[<>…?=\s]|xxx|XXX")
SNAKE = re.compile(r"^[a-z][a-z0-9]*(?:_[a-z0-9]+)+$")
CONSTANTE = re.compile(r"^_?[A-Z][A-Z0-9]*(?:_[A-Z0-9]+)+$")
APPEL = re.compile(r"^(_?[A-Za-z][A-Za-z0-9_]*)\(\)$")


def fichiers_versionnes():
    sortie = subprocess.run(
        ["git", "ls-files", "-z"], cwd=REPO_ROOT, check=True, capture_output=True, text=True
    ).stdout
    return [f for f in sortie.split("\0") if f and not EXCLUDED_DIRS.intersection(f.split("/"))]


VERSIONNES = fichiers_versionnes()
DOSSIERS_VERSIONNES = {"/".join(f.split("/")[:i]) for f in VERSIONNES for i in range(1, f.count("/") + 1)}


def fichiers_corpus():
    for f in VERSIONNES:
        if f in CORPUS_FICHIERS or any(f.startswith(d) and f.endswith(ext) for d, ext in CORPUS):
            path = REPO_ROOT / f
            if path.exists():
                yield path


def charger_corpus():
    mots, fonctions = set(), set()
    for path in fichiers_corpus():
        texte = path.read_text(encoding="utf-8", errors="replace")
        mots.update(re.findall(r"\w+", texte))
        if path.suffix == ".php":
            fonctions.update(re.findall(r"function\s+&?(\w+)\s*\(", texte))
    # Noms de plugins (dossiers), cités sans être forcément écrits dans le code
    mots.update(p.name for p in PLUGINS.iterdir() if p.is_dir())
    return mots, fonctions


def codes_en_ligne(texte):
    """(ligne, contenu) de chaque <code> hors <pre>."""
    sans_pre = re.sub(r"<pre\b.*?</pre>", lambda m: "\n" * m.group(0).count("\n"), texte, flags=re.S)
    for m in re.finditer(r"<code>(.*?)</code>", sans_pre, flags=re.S):
        contenu = html.unescape(re.sub(r"<[^>]+>", "", m.group(1))).strip()
        yield sans_pre.count("\n", 0, m.start()) + 1, contenu


def chemin_existe(ref):
    if ref in HORS_DEPOT:
        return True
    ref = ref.removeprefix("./").lstrip("/").rstrip("/")
    bases = [""]
    for plugin in PLUGINS.iterdir():
        if plugin.is_dir():
            rel = plugin.relative_to(REPO_ROOT).as_posix()
            bases += [f"{rel}/", f"{rel}/squelettes/"]
    for base in bases:
        chemin = base + ref
        if "*" in ref:
            if fnmatch.filter(VERSIONNES, chemin):
                return True
        elif chemin in VERSIONNES or chemin in DOSSIERS_VERSIONNES:
            return True
    return False


def est_chemin(ref):
    return "/" in ref or ref.endswith(EXTENSIONS)


def main() -> int:
    mots, fonctions = charger_corpus()
    erreurs = []

    for page in sorted(DOCS.glob("*.html")):
        texte = page.read_text(encoding="utf-8")
        rel = page.relative_to(REPO_ROOT)
        for ligne, ref in codes_en_ligne(texte):
            if not ref or GABARIT.search(ref) or ref.startswith(("http", "#", "{", "[", "erasme/")):
                continue
            if est_chemin(ref):
                if not chemin_existe(ref):
                    erreurs.append(f"  {rel}:{ligne} fichier introuvable : {ref}")
                continue
            appel = APPEL.match(ref)
            if appel:
                if appel.group(1) not in fonctions | FONCTIONS_SPIP:
                    erreurs.append(f"  {rel}:{ligne} fonction non définie : {ref}")
                continue
            if (SNAKE.match(ref) or CONSTANTE.match(ref)) and ref not in mots:
                erreurs.append(f"  {rel}:{ligne} absent du code : {ref}")

    deploiement = (DOCS / "deploiement.html").read_text(encoding="utf-8")
    env = (REPO_ROOT / ".env.example").read_text(encoding="utf-8")
    for var in re.findall(r"^([A-Z][A-Z0-9_]*)=", env, flags=re.M):
        if f"<code>{var}</code>" not in deploiement:
            erreurs.append(f"  docs/deploiement.html : variable de .env.example non documentée : {var}")

    if erreurs:
        print("La documentation (docs/) cite des éléments absents du code :\n")
        print("\n".join(erreurs))
        print(
            "\nMets à jour la page concernée dans le même commit que la modif de code"
            " (renommage, suppression, nouvelle variable), cf .ci/README.md."
        )
        return 1

    print("OK — toutes les références de docs/ existent dans le code.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
