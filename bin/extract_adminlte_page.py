from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
ADMINLTE_PAGES = ROOT / 'templates' / 'admin' / 'AdminLTE' / 'AdminLTE-3.1.0' / 'pages'
OUT = ROOT / 'templates' / 'admin' / 'adminlte_pages'

def extract(html: str) -> str:
    """Extract the central page content from an AdminLTE full HTML page.

    We keep the whole <div class="content-wrapper"> ... </div> inner markup,
    but remove the outer document/head/body/wrapper/sidebar/footer which are already
    provided by our Symfony layout.

    This is a heuristic string-based extractor (no heavy HTML parsing dependency).
    """

    # content-wrapper can be: <div class="content-wrapper"> or <div class="content-wrapper kanban"> etc.
    start = html.find('<div class="content-wrapper')
    if start == -1:
        raise ValueError('content-wrapper not found')

    # Prefer to stop before the main footer, which is included by our Twig layout.
    stop = html.find('<footer class="main-footer">', start)
    if stop == -1:
        # fallback: stop before closing wrapper
        stop = html.find('</div>\n</div>', start)
    if stop == -1:
        stop = len(html)

    content = html[start:stop]

    # Strip the outer content-wrapper div itself; we only want its inner markup.
    open_tag_end = content.find('>')
    inner = content[open_tag_end + 1:]
    return inner.strip()


def main():
    import argparse

    parser = argparse.ArgumentParser()
    parser.add_argument('page', help='Relative page path under AdminLTE pages, e.g. widgets.html or tables/simple.html')
    args = parser.parse_args()

    src = ADMINLTE_PAGES / args.page
    if not src.exists():
        raise SystemExit(f'Not found: {src}')

    html = src.read_text(encoding='utf-8', errors='ignore')
    extracted = extract(html)

    OUT.mkdir(parents=True, exist_ok=True)
    out = OUT / (args.page.replace('/', '__').replace('\\', '__') + '.twig')
    out.write_text(extracted, encoding='utf-8')
    print(out)


if __name__ == '__main__':
    main()

