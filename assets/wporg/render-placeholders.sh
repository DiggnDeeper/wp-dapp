#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")"

# Requires: inkscape or rsvg-convert (install via apt)

render() {
  local svg="$1"; shift
  local out="$1"; shift
  local width="$1"; shift
  local height="$1"; shift
  if command -v inkscape >/dev/null 2>&1; then
    inkscape --export-type=png --export-filename="$out" -w "$width" -h "$height" "$svg"
  elif command -v rsvg-convert >/dev/null 2>&1; then
    rsvg-convert -w "$width" -h "$height" -o "$out" "$svg"
  else
    echo "Please install inkscape or rsvg-convert" >&2
    exit 1
  fi
}

# Icons
render icon.svg icon-256.png 256 256
render icon.svg icon-128.png 128 128

# Banners
render banner.svg banner-1544x500.png 1544 500
render banner.svg banner-772x250.png 772 250

echo "Rendered placeholder assets to PNG."
