#!/usr/bin/env python3
"""
Vérifie que toute clé de langue utilisée dans le plugin thematique
(<:thematique:cle:>, _T('thematique:cle'), CCN.lang.cle côté JS) existe
bien dans lang/thematique_fr.php.

Le lint check_lang_hardcoded.py garantit l'absence de texte en dur, mais
pas la validité des clés utilisées : une clé mal orthographiée s'affiche
telle quelle en prod (ex: <:thematique:mauvaise_cle:>) sans faire échouer
ce lint-là. C'est ce que check_lang_keys.py couvre.

Deux familles de vérifications :
1. Clés référencées via <:thematique:cle:> ou _T('thematique:cle') /
   _T("thematique:cle") : doivent exister dans lang/thematique_fr.php.
2. Clés référencées via CCN.lang.cle côté JS : doivent exister comme
   propriété de l'objet CCN.lang construit dans
   squelettes/noisettes/timeline.html (seul pont PHP -> JS du plugin).

Usage (depuis la racine du dépôt) :
  .ci/check_lang_keys.py
"""

import re
import sys
from pathlib import Path

REPO_ROOT = Path(__file__).resolve().parent.parent
PLUGIN_ROOT = REPO_ROOT / "plugins" / "thematique"
LANG_FILE = PLUGIN_ROOT / "lang" / "thematique_fr.php"
JS_BRIDGE_FILE = PLUGIN_ROOT / "squelettes" / "noisettes" / "timeline.html"
EXCLUDE_DIRS = {PLUGIN_ROOT / "vendor"}

SCAN_EXTS = {".html", ".php", ".js"}

TAG_KEY_RE = re.compile(r"<:thematique:([a-zA-Z0-9_]+)(?:\{[^:]*?\})?:>")
T_CALL_RE = re.compile(r"""_T\(\s*['"]thematique:([a-zA-Z0-9_]+)['"]""")
CCN_LANG_USE_RE = re.compile(r"CCN\.lang\.([a-zA-Z0-9_]+)")
CCN_LANG_PROP_RE = re.compile(r"^\s*([a-zA-Z0-9_]+)\s*:", re.MULTILINE)
LANG_ARRAY_KEY_RE = re.compile(r"^\s*'([a-zA-Z0-9_]+)'\s*=>", re.MULTILINE)


def load_lang_keys() -> set:
    text = LANG_FILE.read_text(encoding="utf-8")
    return set(LANG_ARRAY_KEY_RE.findall(text))


def load_ccn_lang_props() -> set:
    text = JS_BRIDGE_FILE.read_text(encoding="utf-8")
    m = re.search(r"CCN\.lang\s*=\s*\{(.*?)\n\t\};", text, re.DOTALL)
    if not m:
        print(f"AVERTISSEMENT : bloc 'CCN.lang = {{...}}' introuvable dans {JS_BRIDGE_FILE}", file=sys.stderr)
        return set()
    return set(CCN_LANG_PROP_RE.findall(m.group(1)))


def iter_files():
    for path in sorted(PLUGIN_ROOT.rglob("*")):
        if not path.is_file() or path.suffix not in SCAN_EXTS:
            continue
        if any(str(path).startswith(str(ex)) for ex in EXCLUDE_DIRS):
            continue
        yield path


def main():
    lang_keys = load_lang_keys()
    ccn_lang_props = load_ccn_lang_props()

    missing = []  # (fichier, ligne, cle, cible)

    for path in iter_files():
        rel = path.relative_to(PLUGIN_ROOT)
        text = path.read_text(encoding="utf-8", errors="replace")
        for lineno, line in enumerate(text.splitlines(), start=1):
            for m in TAG_KEY_RE.finditer(line):
                key = m.group(1)
                if key not in lang_keys:
                    missing.append((rel, lineno, key, "lang/thematique_fr.php"))
            for m in T_CALL_RE.finditer(line):
                key = m.group(1)
                if key not in lang_keys:
                    missing.append((rel, lineno, key, "lang/thematique_fr.php"))
            for m in CCN_LANG_USE_RE.finditer(line):
                key = m.group(1)
                if key not in ccn_lang_props:
                    missing.append((rel, lineno, key, "CCN.lang (squelettes/noisettes/timeline.html)"))

    if missing:
        print("Clé(s) de langue introuvable(s) :\n")
        for rel, lineno, key, target in missing:
            print(f"  {rel}:{lineno}: '{key}' absente de {target}")
        return 1

    print(f"OK — toutes les clés de langue référencées existent ({len(lang_keys)} clé(s) dans lang/thematique_fr.php, {len(ccn_lang_props)} propriété(s) CCN.lang).")
    return 0


if __name__ == "__main__":
    sys.exit(main())
