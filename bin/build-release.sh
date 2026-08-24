#!/usr/bin/env bash
#
# Build a WordPress-ready release zip for the awb-starter plugin.
#
# Usage:  bin/build-release.sh [version]
#   version is optional; defaults to the "Version:" header in awb-starter.php.
#
# Output: dist/awb-starter-<version>.zip
# The zip's top-level folder is "awb-starter/" so WordPress can install it.
#
# After building, publish a GitHub release for tag v<version> and attach
# this zip as a release asset (see README "Releasing a new version").

set -euo pipefail

PLUGIN_SLUG="awb-starter"
PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
MAIN_FILE="${PLUGIN_DIR}/${PLUGIN_SLUG}.php"
DIST_DIR="${PLUGIN_DIR}/dist"

if ! command -v zip >/dev/null 2>&1; then
	echo "Error: 'zip' command not found. Install zip and retry." >&2
	exit 1
fi

VERSION="${1:-}"
if [ -z "$VERSION" ]; then
	VERSION=$(php -r '
		$c = file_get_contents($argv[1]);
		preg_match("/^\s*\*\s*Version:\s*(\S+)/m", $c, $m);
		echo isset($m[1]) ? trim($m[1]) : "";
	' "$MAIN_FILE")
fi

if [ -z "$VERSION" ]; then
	echo "Error: could not determine version. Pass one explicitly: bin/build-release.sh 1.2.3" >&2
	exit 1
fi

HEADER_VERSION=$(php -r '
	$c = file_get_contents($argv[1]);
	preg_match("/^\s*\*\s*Version:\s*(\S+)/m", $c, $m);
	echo isset($m[1]) ? trim($m[1]) : "";
' "$MAIN_FILE")

CONST_VERSION=$(php -r '
	$c = file_get_contents($argv[1]);
	preg_match("/define\(\x27AWB_VERSION\x27,\s*\x27([^\x27]+)\x27\)/", $c, $m);
	echo isset($m[1]) ? $m[1] : "";
' "$MAIN_FILE")

if [ -n "$HEADER_VERSION" ] && [ -n "$CONST_VERSION" ] && [ "$HEADER_VERSION" != "$CONST_VERSION" ]; then
	echo "Error: version mismatch in ${PLUGIN_SLUG}.php — header: ${HEADER_VERSION}, AWB_VERSION: ${CONST_VERSION}." >&2
	echo "Sync both before building." >&2
	exit 1
fi

STAGE_ROOT=$(mktemp -d)
STAGE="${STAGE_ROOT}/${PLUGIN_SLUG}"
trap 'rm -rf "$STAGE_ROOT"' EXIT

cp -R "${PLUGIN_DIR}" "${STAGE}"

# Strip development/build artifacts from the package.
rm -rf \
	"${STAGE}/.git" \
	"${STAGE}/.gitignore" \
	"${STAGE}/bin" \
	"${STAGE}/dist" \
	"${STAGE}/node_modules" \
	"${STAGE}/features-report.md" \
	"${STAGE}/next-step.md" \
	"${STAGE}/awb-starter.md"

find "${STAGE}" -name '*.log' -delete
find "${STAGE}" -name '*.cache' -delete
find "${STAGE}" -name '.DS_Store' -delete

mkdir -p "${DIST_DIR}"
ZIP_PATH="${DIST_DIR}/${PLUGIN_SLUG}-${VERSION}.zip"
rm -f "${ZIP_PATH}"

cd "${STAGE_ROOT}"
zip -rq9 "${ZIP_PATH}" "${PLUGIN_SLUG}"

echo "Built ${ZIP_PATH}"
echo ""
echo "Next steps:"
echo "  1. git tag v${VERSION} && git push origin v${VERSION}"
echo "  2. Create a GitHub release for tag v${VERSION}"
echo "  3. Attach ${ZIP_PATH} as a release asset (name: ${PLUGIN_SLUG}-${VERSION}.zip)"
