#!/bin/bash
# Packs only what the live site needs into andnow-site.zip, ready for Hostinger.
# Run from anywhere: ./deploy.sh   (see DEPLOY.md for the upload step)
set -euo pipefail

cd "$(dirname "$0")"
OUT="$PWD/andnow-site.zip"
rm -f "$OUT"

zip -rq "$OUT" .htaccess index.php contact.php config.php llms.txt robots.txt sitemap.xml lib assets \
  -x "*.DS_Store" "*/.impeccable/*" "*.md"

# Every PHP file must parse, or the live site shows a blank page.
for f in index.php contact.php config.php lib/*.php; do php -l "$f" >/dev/null; done

echo "Built: $OUT ($(du -h "$OUT" | cut -f1), $(unzip -l "$OUT" | tail -1 | awk '{print $2}') files)"
