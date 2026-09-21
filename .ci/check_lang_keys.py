#!/usr/bin/env python3
"""
Vérifie que toute clé de langue utilisée dans les plugins thematique,
fictions, petitfablab et ccn (<:module:cle:>, _T('module:cle'),
CCN.lang.cle côté JS pour thematique) existe bien dans le fichier de
langue du module concerné.

Le lint check_lang_hardcoded.py garantit l'absence de texte en dur, mais
pas la validité des clés utilisées : une clé mal orthographiée s'affiche
telle quelle en prod (ex: <:thematique:mauvaise_cle:>) sans faire échouer
ce lint-là. C'est ce que check_lang_keys.py couvre.

Deux familles de vérifications, par plugin :
1. Clés référencées via <:module:cle:> ou _T('module:cle') / _T("module:cle") :
   doivent exister dans le fichier de langue du plugin (lang/<module>_fr.php
   pour thematique, squelettes/lang/<module>_fr.php pour petitfablab — les
   deux emplacements sont cherchés). fictions n'a pas de fichier de langue :
   toute clé <:fictions:...:> y serait donc automatiquement signalée comme
   manquante (aucune n'est utilisée actuellement).
2. Clés référencées via CCN.lang.cle côté JS : doivent exister comme
   propriété de l'objet CCN.lang construit dans
   thematique/squelettes/noisettes/timeline.html — seul pont PHP -> JS de
   ce type dans le dépôt (fictions/petitfablab n'ont pas cette mécanique,
   donc pas de vérification CCN.lang pour eux).

Usage (depuis la racine du dépôt) :
  .ci/check_lang_keys.py
"""

import re
import sys
from pathlib import Path

REPO_ROOT = Path(__file__).resolve().parent.parent
PLUGIN_NAMES = ("thematique", "fictions", "petitfablab", "ccn")

CCN_LANG_USE_RE = re.compile(r"CCN\.lang\.([a-zA-Z0-9_]+)")
CCN_LANG_PROP_RE = re.compile(r"^\s*([a-zA-Z0-9_]+)\s*:", re.MULTILINE)
LANG_ARRAY_KEY_RE = re.compile(r"^\s*'([a-zA-Z0-9_]+)'\s*=>", re.MULTILINE)

SCAN_EXTS = {".html", ".php", ".js"}


def find_lang_file(plugin_root: Path, name: str) -> Path | None:
    for candidate in (
        plugin_root / "lang" / f"{name}_fr.php",
        plugin_root / "squelettes" / "lang" / f"{name}_fr.php",
    ):
        if candidate.exists():
            return candidate
    return None


def load_lang_keys(lang_file: Path | None) -> set:
    if lang_file is None:
        return set()
    text = lang_file.read_text(encoding="utf-8")
    return set(LANG_ARRAY_KEY_RE.findall(text))


def load_ccn_lang_props(js_bridge_file: Path) -> set:
    if not js_bridge_file.exists():
        return set()
    text = js_bridge_file.read_text(encoding="utf-8")
    m = re.search(r"CCN\.lang\s*=\s*\{(.*?)\n\t\};", text, re.DOTALL)
    if not m:
        print(f"AVERTISSEMENT : bloc 'CCN.lang = {{...}}' introuvable dans {js_bridge_file}", file=sys.stderr)
        return set()
    return set(CCN_LANG_PROP_RE.findall(m.group(1)))


def iter_files(plugin_root: Path, exclude_dirs: set):
    for path in sorted(plugin_root.rglob("*")):
        if not path.is_file() or path.suffix not in SCAN_EXTS:
            continue
        if any(str(path).startswith(str(ex)) for ex in exclude_dirs):
            continue
        yield path


def check_plugin(name: str):
    """Renvoie (missing, lang_key_count, ccn_lang_prop_count) pour un plugin."""
    plugin_root = REPO_ROOT / "plugins" / "projets" / name
    lang_file = find_lang_file(plugin_root, name)
    lang_keys = load_lang_keys(lang_file)

    # CCN.lang n'existe que côté thematique (pont construit dans
    # squelettes/noisettes/timeline.html) — absent ailleurs, donc
    # ccn_lang_props reste vide et aucun CCN.lang.xxx n'y est trouvé.
    js_bridge_file = plugin_root / "squelettes" / "noisettes" / "timeline.html"
    ccn_lang_props = load_ccn_lang_props(js_bridge_file)

    exclude_dirs = {plugin_root / "vendor"}
    tag_key_re = re.compile(rf"<:{name}:([a-zA-Z0-9_]+)(?:\{{[^:]*?\}})?:>")
    t_call_re = re.compile(rf"""_T\(\s*['"]{name}:([a-zA-Z0-9_]+)['"]""")

    missing = []  # (fichier relatif au dépôt, ligne, cle, cible)
    for path in iter_files(plugin_root, exclude_dirs):
        rel = path.relative_to(REPO_ROOT)
        text = path.read_text(encoding="utf-8", errors="replace")
        lang_target = str((lang_file or plugin_root / "lang" / f"{name}_fr.php").relative_to(REPO_ROOT))
        for lineno, line in enumerate(text.splitlines(), start=1):
            for m in tag_key_re.finditer(line):
                key = m.group(1)
                if key not in lang_keys:
                    missing.append((rel, lineno, key, lang_target))
            for m in t_call_re.finditer(line):
                key = m.group(1)
                if key not in lang_keys:
                    missing.append((rel, lineno, key, lang_target))
            for m in CCN_LANG_USE_RE.finditer(line):
                key = m.group(1)
                if key not in ccn_lang_props:
                    missing.append((rel, lineno, key, f"CCN.lang ({js_bridge_file.relative_to(REPO_ROOT)})"))

    return missing, len(lang_keys), len(ccn_lang_props)


def main():
    all_missing = []
    summary = []
    for name in PLUGIN_NAMES:
        missing, lang_key_count, ccn_lang_prop_count = check_plugin(name)
        all_missing.extend(missing)
        summary.append(f"{name}: {lang_key_count} clé(s), {ccn_lang_prop_count} propriété(s) CCN.lang")

    if all_missing:
        print("Clé(s) de langue introuvable(s) :\n")
        for rel, lineno, key, target in all_missing:
            print(f"  {rel}:{lineno}: '{key}' absente de {target}")
        return 1

    print("OK — toutes les clés de langue référencées existent (" + "; ".join(summary) + ").")
    return 0


if __name__ == "__main__":
    sys.exit(main())
