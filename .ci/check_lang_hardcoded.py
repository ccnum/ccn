#!/usr/bin/env python3
"""
Détecte le texte français codé en dur dans le plugin thematique, pour
forcer le passage par des items de langue (lang/thematique_fr.php +
CCN.lang côté JS).

Fonctionnement :
- Scanne tout le plugin plugins/thematique (hors lang/ et vendor/).
- Repère les chaînes contenant des caractères accentués français en dehors
  des tags de langue <:module:cle:>, des appels _T(...)/CCN.lang.xxx, et de
  quelques zones à ignorer (commentaires, attributs techniques).
- Compare le résultat à un fichier de baseline : toute occurrence absente de
  la baseline fait échouer le script (nouveau texte en dur introduit).
  Toute entrée de la baseline qui n'est plus détectée est simplement ignorée
  (la baseline peut donc être régénérée pour rétrécir au fil des corrections).

Usage (depuis la racine du dépôt) :
  .ci/check_lang_hardcoded.py [--baseline PATH] [--write-baseline]
"""

import argparse
import re
import sys
from pathlib import Path

REPO_ROOT = Path(__file__).resolve().parent.parent
PLUGIN_ROOT = REPO_ROOT / "plugins" / "thematique"
SCAN_DIRS = [PLUGIN_ROOT]
EXCLUDE_DIRS = {PLUGIN_ROOT / "lang", PLUGIN_ROOT / "vendor"}

ACCENTED = "àâäéèêëïîôöùûüçœÀÂÄÉÈÊËÏÎÔÖÙÛÜÇŒ"
ACCENTED_RE = re.compile(f"[{ACCENTED}]")

# Zones à retirer avant analyse (commentaires, tags de langue déjà propres,
# appels _T()/CCN.lang déjà conformes).
STRIP_PATTERNS = [
    re.compile(r"<!--.*?-->", re.DOTALL),             # <!-- ... -->
    re.compile(r"/\*.*?\*/", re.DOTALL),               # commentaires CSS/JS (ex: *.css.html)
    re.compile(r"<:[a-zA-Z0-9_]+:[a-zA-Z0-9_]+(\{[^:]*?\})?:>"),  # <:module:cle{...}:>
    re.compile(r"_T\(\s*['\"][^'\"]*['\"]\s*(,.*?\))?\s*\)", re.DOTALL),  # _T('module:cle', [...])
    re.compile(r"CCN\.lang\.[a-zA-Z0-9_]+"),          # CCN.lang.cle
    re.compile(r"spip_log\(.*?\)\s*;", re.DOTALL),    # spip_log(...) : messages techniques, jamais affichés
]

REM_START_RE = re.compile(r"\[\(#REM\)")


def strip_rem_blocks(text: str) -> str:
    """Retire les [(#REM) ... ] en équilibrant les crochets imbriqués
    (un commentaire REM peut contenir d'autres blocs conditionnels [...])."""
    out = []
    pos = 0
    for m in REM_START_RE.finditer(text):
        if m.start() < pos:
            continue
        out.append(text[pos:m.start()])
        depth = 1
        i = m.end()
        while i < len(text) and depth > 0:
            if text[i] == "[":
                depth += 1
            elif text[i] == "]":
                depth -= 1
            i += 1
        pos = i
    out.append(text[pos:])
    return " ".join(out)

# Commentaires JS/PHP.
JS_PHP_COMMENT_PATTERNS = [
    re.compile(r"/\*.*?\*/", re.DOTALL),
    re.compile(r"//[^\n]*"),
]

HTML_EXTS = {".html"}
JS_EXTS = {".js"}
PHP_EXTS = {".php"}

# Chaînes entre balises ou dans des attributs visibles.
HTML_TEXT_NODE_RE = re.compile(r">([^<>{}]*[%s][^<>{}]*)<" % ACCENTED)
HTML_ATTR_RE = re.compile(
    r'\b(?:title|alt|aria-label|placeholder|data-tip|value)=[\'"]([^\'"]*)[\'"]'
)
STRING_LITERAL_RE = re.compile(r"""(['"])((?:(?!\1)[^\\]|\\.)*)\1""", re.DOTALL)


def strip_common(text: str) -> str:
    text = strip_rem_blocks(text)
    for pat in STRIP_PATTERNS:
        text = pat.sub(" ", text)
    return text


def strip_js_php_comments(text: str) -> str:
    for pat in JS_PHP_COMMENT_PATTERNS:
        text = pat.sub(" ", text)
    return text


SCRIPT_BLOCK_RE = re.compile(r"<script\b[^>]*>(.*?)</script>", re.DOTALL | re.IGNORECASE)


def scan_html(path: Path):
    findings = []
    raw = path.read_text(encoding="utf-8", errors="replace")
    cleaned = strip_common(raw)

    # Les commentaires JS (// et /* */) ne sont pertinents qu'à l'intérieur
    # des blocs <script> : les retirer globalement casserait les http://.
    def strip_script_comments(m):
        return strip_js_php_comments(m.group(0))

    cleaned = SCRIPT_BLOCK_RE.sub(strip_script_comments, cleaned)

    for lineno, line in enumerate(cleaned.splitlines(), start=1):
        for m in HTML_TEXT_NODE_RE.finditer(f">{line}<"):
            snippet = m.group(1).strip()
            if snippet and ACCENTED_RE.search(snippet):
                findings.append((lineno, snippet[:80]))
        for m in HTML_ATTR_RE.finditer(line):
            snippet = m.group(1).strip()
            if snippet and ACCENTED_RE.search(snippet):
                findings.append((lineno, snippet[:80]))
    return findings


def scan_js(path: Path):
    findings = []
    raw = path.read_text(encoding="utf-8", errors="replace")
    cleaned = strip_js_php_comments(strip_common(raw))
    for lineno, line in enumerate(cleaned.splitlines(), start=1):
        for m in STRING_LITERAL_RE.finditer(line):
            snippet = m.group(2).strip()
            if snippet and ACCENTED_RE.search(snippet):
                findings.append((lineno, snippet[:80]))
    return findings


def scan_php(path: Path):
    findings = []
    raw = path.read_text(encoding="utf-8", errors="replace")
    cleaned = strip_js_php_comments(strip_common(raw))
    for lineno, line in enumerate(cleaned.splitlines(), start=1):
        for m in STRING_LITERAL_RE.finditer(line):
            snippet = m.group(2).strip()
            if snippet and ACCENTED_RE.search(snippet):
                findings.append((lineno, snippet[:80]))
    return findings


def iter_files():
    for base in SCAN_DIRS:
        if not base.exists():
            continue
        for path in sorted(base.rglob("*")):
            if not path.is_file():
                continue
            if any(str(path).startswith(str(ex)) for ex in EXCLUDE_DIRS):
                continue
            yield path


def run_scan():
    results = {}
    for path in iter_files():
        rel = path.relative_to(PLUGIN_ROOT)
        if path.suffix in HTML_EXTS:
            findings = scan_html(path)
        elif path.suffix in JS_EXTS:
            findings = scan_js(path)
        elif path.suffix in PHP_EXTS:
            findings = scan_php(path)
        else:
            continue
        for lineno, snippet in findings:
            key = f"{rel}:{lineno}: {snippet}"
            results[key] = True
    return sorted(results.keys())


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument(
        "--baseline",
        default=str(REPO_ROOT / ".ci" / "lang-check-baseline.txt"),
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
        print("Texte en dur détecté (absent de la baseline) :\n")
        for line in new_findings:
            print(f"  {line}")
        print(
            "\nCorrige-le en passant par un item de langue "
            "(<:thematique:cle:> côté squelette, _T('thematique:cle') côté "
            "PHP, CCN.lang.cle côté JS), ou si c'est un faux positif "
            "volontaire, régénère la baseline avec --write-baseline."
        )
        return 1

    print(f"OK — aucun nouveau texte en dur ({len(current)} entrée(s) déjà connue(s) en baseline).")
    return 0


if __name__ == "__main__":
    sys.exit(main())
