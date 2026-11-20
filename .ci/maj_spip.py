#!/usr/bin/env python3
"""
Applique par pull request les mises à jour correctives de SPIP.

Le core SPIP n'est pas versionné : l'image Docker l'installe au build depuis
l'archive officielle, à la version et au sha256 fixés par le Dockerfile
(`ENV SPIP_PACKAGE`, `ENV SPIP_PACKAGE_SHA256`). Pour chaque branche `dev`
et `main` :
1. lit `SPIP_PACKAGE` dans le Dockerfile de la branche ;
2. cherche dans l'API spip_loader (https://www.spip.net/spip_loader.api/3)
   la version la plus récente de la même branche SPIP (4.4.x pour 4.4.30) :
   une montée de version majeure ou mineure (4.4 -> 5.0) reste manuelle ;
3. si elle est plus récente et qu'aucune PR n'a déjà été ouverte pour elle
   (une PR fermée sans fusion n'est pas reproposée), télécharge l'archive et
   vérifie son sha256, crée la branche `maj-spip-<version>`
   (`maj-spip-<version>-main` pour main) ne changeant que ces lignes du
   Dockerfile, ouvre une PR vers la branche et la fusionne, ce qui déclenche
   le build et la publication de l'image comme toute fusion.

`main` reçoit la mise à jour directement, sans le reste de `dev`. Le même
contenu étant écrit des deux côtés, la fusion `dev` -> `main` suivante ne fait
pas de conflit.

Variables d'environnement :
- GH_TOKEN (obligatoire) : jeton utilisé par `gh` ; un jeton personnel et pas
  le GITHUB_TOKEN du workflow, dont les fusions ne déclenchent pas les
  workflows de build ;
- MAJ_SPIP_DOCKERFILE : chemin du Dockerfile (défaut : Dockerfile) ;
- MAJ_SPIP_SCOPE : scope du message de commit (défaut : spip) ;
- MAJ_SPIP_MAIN_HEURES : plage « début-fin » (heure de Paris) hors de
  laquelle `main` n'est pas mise à jour, ex. 18-8 ; vide : à toute heure.

Usage : maj_spip.py [--essai]   (--essai : ne crée ni branche ni PR)
"""

import argparse
import datetime
import hashlib
import json
import os
import re
import subprocess
import sys
import urllib.request
import zoneinfo

API_SPIP = "https://www.spip.net/spip_loader.api/3"
ARCHIVE = "https://files.spip.net/spip/archives/spip-v{}.zip"
BRANCHES = ("dev", "main")
VERSION_RE = re.compile(r"^\d+\.\d+\.\d+$")
SHA256_RE = re.compile(r"^[0-9a-f]{64}$")
LIGNES = {
    "version": re.compile(r"^(ENV SPIP_PACKAGE=)(\S+)$", re.M),
    "sha256": re.compile(r"^(ENV SPIP_PACKAGE_SHA256=)(\S+)$", re.M),
    "label": re.compile(r'^(LABEL org\.opencontainers\.image\.version=")([^"]*)(")$', re.M),
}


def version_tuple(v: str) -> tuple:
    return tuple(int(x) for x in v.split("."))


def run(*cmd: str) -> str:
    return subprocess.run(cmd, check=True, capture_output=True, text=True).stdout


def versions_spip() -> dict:
    with urllib.request.urlopen(API_SPIP, timeout=30) as rep:
        api = json.load(rep)
    if api.get("api") != 3:
        raise RuntimeError(f"API spip_loader inattendue : {api.get('api')}")
    return {
        v: infos
        for v, infos in api.get("versions", {}).items()
        if VERSION_RE.match(v) and SHA256_RE.match(str(infos.get("sha256", "")))
    }


def derniere_de_la_branche(versions: dict, actuelle: str):
    branche = version_tuple(actuelle)[:2]
    candidates = [v for v in versions if version_tuple(v)[:2] == branche]
    if not candidates:
        return None
    meilleure = max(candidates, key=version_tuple)
    return meilleure if version_tuple(meilleure) > version_tuple(actuelle) else None


def maj_dockerfile(texte: str, version: str, sha256: str) -> str:
    for cle, motif in LIGNES.items():
        if len(motif.findall(texte)) != 1:
            raise RuntimeError(f"ligne {cle} absente ou en double dans le Dockerfile")
    texte = LIGNES["version"].sub(lambda m: m[1] + version, texte)
    texte = LIGNES["sha256"].sub(lambda m: m[1] + sha256, texte)
    return LIGNES["label"].sub(lambda m: m[1] + version + m[3], texte)


archives_verifiees = set()


def verifier_archive(version: str, sha256: str) -> None:
    """Le build échouerait sur un sha256 faux : autant ne rien proposer."""
    if version in archives_verifiees:
        return
    h = hashlib.sha256()
    with urllib.request.urlopen(ARCHIVE.format(version), timeout=120) as rep:
        while bloc := rep.read(1 << 20):
            h.update(bloc)
    if h.hexdigest() != sha256:
        raise RuntimeError(f"sha256 de spip-v{version}.zip différent de celui de l'API spip_loader")
    archives_verifiees.add(version)


def hors_plage(plage: str) -> bool:
    if not plage:
        return False
    debut, fin = (int(x) for x in plage.split("-"))
    heure = datetime.datetime.now(zoneinfo.ZoneInfo("Europe/Paris")).hour
    dans = debut <= heure < fin if debut < fin else heure >= debut or heure < fin
    return not dans


def fusionner(url: str, cible: str) -> None:
    run("gh", "pr", "merge", url, "--squash", "--delete-branch")
    print(f"    fusionnée dans {cible}")


def traiter_branche(cible: str, versions: dict, dockerfile: str, scope: str, essai: bool) -> None:
    try:
        texte = run("git", "show", f"origin/{cible}:{dockerfile}")
    except subprocess.CalledProcessError:
        print(f"  {cible} : pas de {dockerfile}, ignorée")
        return
    m = LIGNES["version"].search(texte)
    actuelle = m[2] if m else ""
    if not VERSION_RE.match(actuelle):
        print(f"  {cible} : SPIP_PACKAGE absent ou invalide, ignorée")
        return
    nouvelle = derniere_de_la_branche(versions, actuelle)
    if nouvelle is None:
        print(f"  {cible} : SPIP {actuelle}, à jour")
        return
    print(f"  {cible} : SPIP {actuelle}, {nouvelle} disponible")
    if cible == "main" and hors_plage(os.environ.get("MAJ_SPIP_MAIN_HEURES", "")):
        print("    hors de la plage horaire de mise à jour de main, remis au passage suivant")
        return

    branche = f"maj-spip-{nouvelle}" if cible == "dev" else f"maj-spip-{nouvelle}-{cible}"
    deja = json.loads(run(
        "gh", "pr", "list", "--head", branche, "--base", cible,
        "--state", "all", "--json", "url,state",
    ))
    if deja:
        pr = deja[0]
        print(f"    PR déjà proposée : {pr['url']} ({pr['state']})")
        if pr["state"] == "OPEN" and not essai:
            fusionner(pr["url"], cible)
        return

    sha256 = versions[nouvelle]["sha256"]
    verifier_archive(nouvelle, sha256)
    message = f"chore({scope}): SPIP {nouvelle}"
    if essai:
        print(f"    [essai] PR « {message} » vers {cible} non créée")
        return
    run("git", "checkout", "-q", "-B", branche, f"origin/{cible}")
    with open(dockerfile, "w", encoding="utf-8") as f:
        f.write(maj_dockerfile(texte, nouvelle, sha256))
    run("git", "commit", "-q", "-am", message)
    run("git", "push", "-q", "-f", "origin", branche)
    corps = (
        f"Mise à jour corrective de SPIP {actuelle} → {nouvelle} sur `{cible}`, "
        "proposée et fusionnée par le workflow `maj-spip`.\n\n"
        f"- Archive : {ARCHIVE.format(nouvelle)}\n"
        f"- sha256 : `{sha256}` (vérifié par le workflow, puis au build de l'image)\n"
        "- Annonces : https://blog.spip.net/\n\n"
        f"Seules les lignes `SPIP_PACKAGE` du `{dockerfile}` changent."
    )
    url = run(
        "gh", "pr", "create", "--base", cible, "--head", branche,
        "--title", message, "--body", corps,
    ).strip()
    print(f"    PR ouverte : {url}")
    fusionner(url, cible)


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__.split("\n\n")[0])
    parser.add_argument("--essai", action="store_true", help="ne crée ni branche ni PR")
    args = parser.parse_args()

    dockerfile = os.environ.get("MAJ_SPIP_DOCKERFILE", "Dockerfile")
    scope = os.environ.get("MAJ_SPIP_SCOPE", "spip")
    versions = versions_spip()
    run("git", "fetch", "-q", "origin", *BRANCHES)
    erreurs = 0
    for cible in BRANCHES:
        try:
            traiter_branche(cible, versions, dockerfile, scope, args.essai)
        except (subprocess.CalledProcessError, RuntimeError, OSError) as e:
            erreurs += 1
            detail = getattr(e, "stderr", "") or ""
            print(f"  ERREUR ({cible}) : {e} {detail}".rstrip(), file=sys.stderr)
    return 1 if erreurs else 0


if __name__ == "__main__":
    sys.exit(main())
