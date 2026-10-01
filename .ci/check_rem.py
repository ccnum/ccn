#!/usr/bin/env python3
"""
Vérifie qu'aucun commentaire SPIP [(#REM) ...] des plugins maison ne
contient de syntaxe de squelette.

Un [(#REM) texte] n'est pas un commentaire inerte : c'est une balise
conditionnelle dont le texte, jusqu'au ']' fermant, est compilé comme du
squelette (dans une branche jamais exécutée, puisque #REM est vide). D'où
trois pièges :
- un ']' dans le texte ferme le commentaire trop tôt, et la suite s'affiche
  dans la page ;
- une balise citée (#SET{, #CACHE{...}, <BOUCLE...>, <:module:cle:>...) est
  compilée : mal formée, elle casse la compilation du squelette entier ;
- du code "désactivé" en le mettant dans un (#REM) reste compilé, avec ses
  boucles et ses filtres.

Règle : dans le texte d'un (#REM), citer les balises sans '#' (CACHE{0},
SET{x}), les boucles sans '<' (BOUCLE_x), les items de langue sans '<: :>'
(thematique:cle), et pas de crochets. Le code désactivé se supprime (il
reste dans l'historique git).

Le texte d'un (#REM) est délimité comme le fait le phraseur SPIP : jusqu'au
']' fermant, en tenant compte des [ ... (#X ...) ... ] imbriqués.

Usage (depuis la racine du dépôt) :
  .ci/check_rem.py
"""

import re
import sys
from pathlib import Path

REPO_ROOT = Path(__file__).resolve().parent.parent
SCAN_ROOT = REPO_ROOT / "plugins" / "projets"
EXCLUDED_DIRS = {"vendor", "node_modules"}
OPEN = "[(#REM)"

RISQUES = [
    (re.compile(r"#[A-Z_]{2,}"), "balise #XXX"),
    (re.compile(r"</?(BOUCLE|B_|INCLURE)|<//B"), "boucle/INCLURE"),
    (re.compile(r"<:"), "item de langue <:...:>"),
    (re.compile(r"[\[\]]"), "crochet"),
]


def rem_spans(text: str):
    """(début, corps) de chaque [(#REM) ...]."""
    i = 0
    while True:
        j = text.find(OPEN, i)
        if j < 0:
            return
        k = j + len(OPEN)
        depth = 0
        while k < len(text):
            c = text[k]
            if c == "[":
                bornes = [x for x in (text.find("]", k + 1), text.find("[", k + 1)) if x >= 0]
                limite = min(bornes) if bornes else len(text)
                if "(#" in text[k + 1:limite] or text.startswith("(", k + 1):
                    depth += 1
            elif c == "]":
                if depth == 0:
                    break
                depth -= 1
            k += 1
        yield j, text[j + len(OPEN):k]
        i = k + 1


def main() -> int:
    erreurs = []
    for path in sorted(SCAN_ROOT.rglob("*.html")):
        if EXCLUDED_DIRS.intersection(path.parts):
            continue
        text = path.read_text(encoding="utf-8", errors="replace")
        for debut, corps in rem_spans(text):
            motifs = [libelle for regex, libelle in RISQUES if regex.search(corps)]
            if motifs:
                ligne = text.count("\n", 0, debut) + 1
                rel = path.relative_to(REPO_ROOT)
                extrait = " ".join(corps.split())[:80]
                erreurs.append(f"  {rel}:{ligne} ({', '.join(motifs)}) : {extrait}")

    if erreurs:
        print("Syntaxe de squelette dans un commentaire [(#REM) ...] :\n")
        print("\n".join(erreurs))
        print(
            "\nCite les balises sans '#' (CACHE{0}), les boucles sans '<' (BOUCLE_x),"
            " les items de langue sans '<: :>', sans crochets ; supprime le code"
            " désactivé plutôt que de le mettre en commentaire (cf .ci/README.md)."
        )
        return 1

    print("OK — aucun commentaire (#REM) ne contient de syntaxe de squelette.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
