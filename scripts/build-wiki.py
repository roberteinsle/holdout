#!/usr/bin/env python3
"""Build the GitHub wiki from handbuch/.

Usage: python3 scripts/build-wiki.py <path-to-wiki-checkout>

- Copies every handbuch/*.md page into the wiki checkout.
- Rewrites links between pages from "Page.md" to "Page" (wiki style).
- Adds a note that the page is generated, and a _Sidebar.md in the order
  of the page list in handbuch/Home.md.
- Removes wiki pages that no longer exist in handbuch/.

The wiki is never edited by hand; change handbuch/ and run this again.
"""

import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
SOURCE = ROOT / "handbuch"
NOTE = "<!-- Generated from handbuch/ by scripts/build-wiki.py. Do not edit in the wiki. -->\n\n"
LINK = re.compile(r"\]\(([A-Za-z0-9_-]+)\.md(#[^)]*)?\)")


def wiki_links(text: str) -> str:
    return LINK.sub(lambda m: f"]({m.group(1)}{m.group(2) or ''})", text)


def title_of(path: Path) -> str:
    for line in path.read_text(encoding="utf-8").splitlines():
        if line.startswith("# "):
            return line[2:].strip()
    return path.stem.replace("-", " ")


def sidebar(pages: list[Path]) -> str:
    home = SOURCE / "Home.md"
    order = re.findall(r"\]\(([A-Za-z0-9_-]+)\.md\)", home.read_text(encoding="utf-8")) if home.exists() else []
    names = ["Home"] + [n for n in order if n != "Home"]
    names += sorted(p.stem for p in pages if p.stem not in names)
    lines = [f"- [{title_of(SOURCE / f'{n}.md')}]({n})" for n in names if (SOURCE / f"{n}.md").exists()]
    return NOTE + "\n".join(lines) + "\n"


def main() -> int:
    if len(sys.argv) != 2:
        print(__doc__)
        return 2

    target = Path(sys.argv[1]).resolve()
    if not (target / ".git").exists():
        print(f"{target} is not a git checkout of the wiki", file=sys.stderr)
        return 1

    pages = sorted(SOURCE.glob("*.md"))
    for page in pages:
        (target / page.name).write_text(NOTE + wiki_links(page.read_text(encoding="utf-8")), encoding="utf-8")

    (target / "_Sidebar.md").write_text(sidebar(pages), encoding="utf-8")

    keep = {p.name for p in pages} | {"_Sidebar.md"}
    for old in target.glob("*.md"):
        if old.name not in keep:
            old.unlink()

    print(f"Wrote {len(pages)} pages and _Sidebar.md to {target}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
