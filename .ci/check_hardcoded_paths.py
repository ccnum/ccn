#!/usr/bin/env python3
"""
Détecte deux types de liens en dur dans le plugin thematique :

1. Ressources du plugin (img/, css/, js/, pdf/) référencées sans passer
   par #CHEMIN{...} (ou #ENV{chemin}/#DOSSIER_SQUELETTE), qui cassent si
   le plugin est déplacé/renommé ou servi depuis un autre chemin.
2. Liens internes vers spip.php?page=... écrits en dur au lieu de
   #URL_PAGE{...}, qui ne respectent alors ni la config d'URL du site
   ni un déplacement de l'installation SPIP.

Fonctionnement :
- Scanne les squelettes .html du plugin plugins/thematique (hors lang/
  et vendor/) : ce sont les seuls fichiers compilés par SPIP, donc les
  seuls où #CHEMIN/#URL_PAGE ont un sens (un .css brut ou un .js ne sont
  pas compilés — chemins relatifs classiques et spip.php?page=... y
  restent la seule option, ils sont donc hors scope de ce check).
- Repère les attributs src=/href=/data-*= et les url(...) CSS qui
  référencent un segment img/, css/, js/ ou pdf/ sans passer par
  #CHEMIN{...}, #ENV{chemin} ou #DOSSIER_SQUELETTE, et qui ne sont pas
  des URLs externes (http(s)://, //, data:, mailto:, #ancre).
- Repère toute occurrence littérale de "spip.php?page=" (attribut ou
  chaîne JS dans un <script>), hors commentaires HTML/CSS/JS.
- Compare le résultat à une baseline : toute occurrence absente de la
  baseline fait échouer le script (nouveau lien en dur introduit).

Usage (depuis la racine du dépôt) :
  .ci/check_hardcoded_paths.py [--baseline PATH] [--write-baseline]
"""

import argparse
import re
import sys
from pathlib import Path

REPO_ROOT = Path(__file__).resolve().parent.parent
PLUGIN_ROOT = REPO_ROOT / "plugins" / "thematique"
EXCLUDE_DIRS = {PLUGIN_ROOT / "lang", PLUGIN_ROOT / "vendor"}

RESOURCE_DIRS = ("img", "css", "js", "pdf")

SAFE_PREFIXES = ("#", "http://", "https://", "//", "data:", "mailto:", "javascript:", "tel:")
SAFE_MARKERS = ("#CHEMIN", "#ENV{chemin}", "#DOSSIER_SQUELETTE", "#GET{chemin}")

RESOURCE_PATH_RE = re.compile(
    r"(?:^|/)(?:%s)/[A-Za-z0-9_.\-]" % "|".join(RESOURCE_DIRS)
)

ATTR_RE = re.compile(
    r'\b(?:src|href|data-src|data-img|data-icon)\s*=\s*["\']([^"\']+)["\']',
    re.IGNORECASE,
)
CSS_URL_RE = re.compile(r"url\(\s*(['\"]?)([^)'\"]+)\1\s*\)")
SPIP_LINK_RE = re.compile(
    r"[\"'](?!https?://|//)[^\"']*spip\.php\?page=[^\"']*[\"']", re.IGNORECASE
)

STRIP_PATTERNS = [
    re.compile(r"<!--.*?-->", re.DOTALL),
    re.compile(r"/\*.*?\*/", re.DOTALL),
]

SCRIPT_BLOCK_RE = re.compile(r"<script\b[^>]*>(.*?)</script>", re.DOTALL | re.IGNORECASE)
JS_LINE_COMMENT_RE = re.compile(r"//[^\n]*")


def is_hardcoded(value: str) -> bool:
    value = value.strip()
    if not value or value.startswith(SAFE_PREFIXES):
        return False
    if any(marker in value for marker in SAFE_MARKERS):
        return False
    return bool(RESOURCE_PATH_RE.search(value))


def strip_comments(text: str) -> str:
    for pat in STRIP_PATTERNS:
        text = pat.sub(" ", text)
    # Les commentaires JS (//) ne sont pertinents qu'à l'intérieur des
    # blocs <script> : les retirer globalement casserait les http://.
    text = SCRIPT_BLOCK_RE.sub(lambda m: JS_LINE_COMMENT_RE.sub(" ", m.group(0)), text)
    return text


def scan_html(path: Path):
    findings = []
    raw = path.read_text(encoding="utf-8", errors="replace")
    cleaned = strip_comments(raw)
    for lineno, line in enumerate(cleaned.splitlines(), start=1):
        for m in ATTR_RE.finditer(line):
            value = m.group(1)
            if is_hardcoded(value):
                findings.append((lineno, value[:120]))
        for m in CSS_URL_RE.finditer(line):
            value = m.group(2)
            if is_hardcoded(value):
                findings.append((lineno, value[:120]))
        for m in SPIP_LINK_RE.finditer(line):
            findings.append((lineno, m.group(0)[:120]))
    return findings


def iter_files():
    for path in sorted(PLUGIN_ROOT.rglob("*.html")):
        if not path.is_file():
            continue
        if any(str(path).startswith(str(ex)) for ex in EXCLUDE_DIRS):
            continue
        yield path


def run_scan():
    results = {}
    for path in iter_files():
        rel = path.relative_to(PLUGIN_ROOT)
        for lineno, snippet in scan_html(path):
            key = f"{rel}:{lineno}: {snippet}"
            results[key] = True
    return sorted(results.keys())


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument(
        "--baseline",
        default=str(REPO_ROOT / ".ci" / "hardcoded-paths-baseline.txt"),
    )
    parser.add_argument("--write-baseline", action="store_true")
    args = parser.parse_args()

    current = run_scan()
    baseline_path = Path(args.baseline)

    if args.write_baseline:
        baseline_path.write_text("\n".join(current) + ("\n" if current else ""))
        print(f"Baseline écrite : {baseline_path} ({len(current)} entrée(s))")
        return 0

    baseline = set()
    if baseline_path.exists():
        baseline = {
            line for line in baseline_path.read_text().splitlines() if line.strip()
        }

    new_findings = [line for line in current if line not in baseline]

    if new_findings:
        print("Lien en dur détecté (absent de la baseline) :\n")
        for line in new_findings:
            print(f"  {line}")
        print(
            "\nPasse par #CHEMIN{img/...} (ressource) ou #URL_PAGE{...} (lien "
            "spip.php?page=...) plutôt qu'un chemin en dur, ou si c'est un faux "
            "positif volontaire, régénère la baseline avec --write-baseline."
        )
        return 1

    print(f"OK — aucun nouveau lien en dur ({len(current)} entrée(s) déjà connue(s) en baseline).")
    return 0


if __name__ == "__main__":
    sys.exit(main())
