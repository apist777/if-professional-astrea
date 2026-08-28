#!/usr/bin/env bash
#
# Builds reproducible Release ZIPs for ASTREA Theme and ASTREA Core.
#
# Reads directly from the repo's theme/ and core/ directories (which already
# contain only shippable files — no tests/docs/tooling live inside either),
# copies each into a correctly-named package root (astrea/, astrea-core/),
# and zips them into dist/ (git-ignored, never committed). Never writes
# anything back into theme/ or core/ themselves.
#
# Usage: tools/release/package.sh
#
# Output:
#   dist/astrea-theme-<version>.zip   (root: astrea/)
#   dist/astrea-core-<version>.zip    (root: astrea-core/)
#   dist/SHA256SUMS.txt
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$REPO_ROOT"

DIST_DIR="$REPO_ROOT/dist"
WORK_DIR="$(mktemp -d)"
trap 'rm -rf "$WORK_DIR"' EXIT

# Files that must never end up in a Release ZIP even if they appear inside
# theme/ or core/ in the future (defence in depth — today neither directory
# actually contains any of these).
EXCLUDE_PATTERNS=(
	'.gitkeep'
	'.DS_Store'
	'Thumbs.db'
	'*.log'
	'.git*'
)

THEME_VERSION="$(sed -n 's/^Version: *//p' theme/style.css | head -1 | tr -d '\r')"
CORE_VERSION="$(sed -n "s/^ \* Version: *//p" core/astrea-core.php | head -1 | tr -d '\r')"

if [[ -z "$THEME_VERSION" || -z "$CORE_VERSION" ]]; then
	echo "ERROR: could not determine Theme/Core version from headers." >&2
	exit 1
fi

echo "Theme version: $THEME_VERSION"
echo "Core version:  $CORE_VERSION"

mkdir -p "$DIST_DIR"

copy_clean() {
	local src="$1" dst="$2"
	local rsync_excludes=()
	for pattern in "${EXCLUDE_PATTERNS[@]}"; do
		rsync_excludes+=(--exclude "$pattern")
	done
	rsync -a "${rsync_excludes[@]}" "$src"/ "$dst"/
}

# --- Theme ------------------------------------------------------------
THEME_PKG_DIR="$WORK_DIR/astrea"
mkdir -p "$THEME_PKG_DIR"
copy_clean "$REPO_ROOT/theme" "$THEME_PKG_DIR"

THEME_ZIP="$DIST_DIR/astrea-theme-${THEME_VERSION}.zip"
rm -f "$THEME_ZIP"
( cd "$WORK_DIR" && zip -rq -X "$THEME_ZIP" astrea )
echo "Built: $THEME_ZIP"

# --- Core ---------------------------------------------------------------
CORE_PKG_DIR="$WORK_DIR/astrea-core"
mkdir -p "$CORE_PKG_DIR"
copy_clean "$REPO_ROOT/core" "$CORE_PKG_DIR"

CORE_ZIP="$DIST_DIR/astrea-core-${CORE_VERSION}.zip"
rm -f "$CORE_ZIP"
( cd "$WORK_DIR" && zip -rq -X "$CORE_ZIP" astrea-core )
echo "Built: $CORE_ZIP"

# --- Checksums ------------------------------------------------------------
( cd "$DIST_DIR" && sha256sum "$(basename "$THEME_ZIP")" "$(basename "$CORE_ZIP")" > SHA256SUMS.txt )
echo "Checksums written: $DIST_DIR/SHA256SUMS.txt"
cat "$DIST_DIR/SHA256SUMS.txt"
