"""Analyze which Twig templates are used by the application.

This script is meant to help you clean up the repository by keeping only
Twig templates that are reachable from controllers via render(), and then
following static Twig dependencies via:
  - {% extends '...' %}
  - {% include '...' %}
  - include('...')

It is best-effort: dynamic template names (variables) cannot be resolved.

Outputs:
  - var/used_templates.txt
  - var/unused_templates.txt

Run (PowerShell/CMD):
  python bin/analyze_used_templates.py
"""

from __future__ import annotations

import re
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
TPL_DIR = ROOT / "templates"
OUT_DIR = ROOT / "var"

RENDER_RE = re.compile(r"render\(\s*['\"]([^'\"]+\.html\.twig)['\"]")
TWIG_EXTENDS_INCLUDE_RE = re.compile(
    r"(?:extends|include)\s+['\"]([^'\"]+\.html\.twig)['\"]"
)
TWIG_INCLUDE_FUNC_RE = re.compile(r"include\s*\(\s*['\"]([^'\"]+\.html\.twig)['\"]")


def main() -> int:
    if not TPL_DIR.exists():
        print(f"templates/ not found at {TPL_DIR}")
        return 2

    all_templates: dict[str, Path] = {
        p.relative_to(TPL_DIR).as_posix(): p
        for p in TPL_DIR.rglob("*.twig")
        if p.is_file()
    }

    # Seed queue from controllers render('...')
    queue: list[str] = []
    for controller in (ROOT / "src" / "Controller").rglob("*.php"):
        txt = controller.read_text(encoding="utf-8", errors="ignore")
        queue.extend(m.group(1) for m in RENDER_RE.finditer(txt))

    used: set[str] = set()

    while queue:
        tpl = queue.pop()
        if tpl in used:
            continue
        used.add(tpl)

        path = all_templates.get(tpl)
        if not path:
            continue

        txt = path.read_text(encoding="utf-8", errors="ignore")

        queue.extend(m.group(1) for m in TWIG_EXTENDS_INCLUDE_RE.finditer(txt))
        queue.extend(m.group(1) for m in TWIG_INCLUDE_FUNC_RE.finditer(txt))

    OUT_DIR.mkdir(parents=True, exist_ok=True)

    used_sorted = sorted(used)
    unused_sorted = sorted(set(all_templates.keys()) - used)

    (OUT_DIR / "used_templates.txt").write_text("\n".join(used_sorted), encoding="utf-8")
    (OUT_DIR / "unused_templates.txt").write_text(
        "\n".join(unused_sorted), encoding="utf-8"
    )

    print(f"Used templates: {len(used_sorted)}")
    print(f"Unused templates: {len(unused_sorted)}")
    print("Wrote:")
    print(" - var/used_templates.txt")
    print(" - var/unused_templates.txt")

    return 0


if __name__ == "__main__":
    raise SystemExit(main())

