#!/usr/bin/env bash
#
# Upgrade + migration release test.
#
# Installs the last released package — the real artifact users are on — into a
# clean test site, then installs the freshly-built package over it. Joomla routes
# the second install to update(), running the install scriptfile's update() path
# and every admin/sql/updates migration newer than the recorded #__schemas
# version. Then confirms the new version registered and the schema landed.
#
# This is the half that matters. A clean install only ever exercises
# install.sql; every real user arrives through the update path, which is where
# 5.5.0's category migration and 5.6.0's content-type seeding actually live.
#
# The baseline is the ACTUAL released artifact pulled from its GitHub release,
# not a rebuild of an old tree — that guarantees the old install.sql, so the
# upgrade genuinely exercises the ADD COLUMN / CREATE TABLE update SQL.
#
# Run via: composer test:upgrade [baseline-version]
#
# @package  Livingword.Build
# @since    __DEPLOY_VERSION__

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

BIN="libraries/vendor/bin"

NEWVER="$(php -r '$v = json_decode(file_get_contents("build/versions.json"), true); echo $v["active_development"]["version"] ?? "";')"
BASEVER="${1:-$(php -r '$v = json_decode(file_get_contents("build/versions.json"), true); echo $v["current"]["version"] ?? "";')}"

if [ -z "$NEWVER" ] || [ -z "$BASEVER" ]; then
    echo "ERROR: could not resolve versions (active_development / current) from build/versions.json" >&2
    exit 1
fi

if [ "$NEWVER" = "$BASEVER" ]; then
    echo "ERROR: active-development version ($NEWVER) equals the baseline ($BASEVER)." >&2
    echo "       The upgrade path only fires when the new build is newer. Bump the" >&2
    echo "       active-development version first, or pass an older baseline:" >&2
    echo "         composer test:upgrade -- 5.6.0-beta2" >&2
    exit 1
fi

BASEZIP="build/dist/pkg_livingword-${BASEVER}.zip"
NEWZIP="build/dist/pkg_livingword-${NEWVER}.zip"

echo "========================================================================"
echo " UPGRADE TEST — ${BASEVER}  ->  ${NEWVER}"
echo "========================================================================"

echo "-- [1/6] reset test site(s) to a clean slate"
php build/reset-testsite.php

echo "-- [2/6] fetch released baseline ${BASEVER}"
mkdir -p build/dist

if [ ! -f "$BASEZIP" ]; then
    gh release download "v${BASEVER}" \
        --repo "$(php -r '$c = json_decode(file_get_contents("cwm-build.config.json"), true); echo $c["github"]["owner"] . "/" . $c["github"]["repo"];')" \
        --pattern "pkg_livingword-${BASEVER}.zip" \
        --dir build/dist
fi

if [ ! -f "$BASEZIP" ]; then
    echo "ERROR: baseline artifact not found: $BASEZIP" >&2
    echo "       Releases before 5.6.0-beta2 shipped a package that cannot install" >&2
    echo "       (see #95), so they are not usable as an upgrade baseline." >&2
    exit 1
fi

echo "-- [3/6] install baseline ${BASEVER} (the 'before' state)"
"$BIN/cwm-install-zip" --zip "$BASEZIP"

echo "-- [4/6] build new package ${NEWVER}"
composer build -- --version "$NEWVER"

if [ ! -f "$NEWZIP" ]; then
    echo "ERROR: expected build artifact not found: $NEWZIP" >&2
    exit 1
fi

echo "-- [5/6] install ${NEWVER} over ${BASEVER} (triggers update() + migrations)"
"$BIN/cwm-install-zip" --zip "$NEWZIP"

# As in test-install.sh: record and carry on, so one known failure does not
# hide the rest.
FAILURES=()

echo "-- [6/6] verify registration + schema after upgrade"
"$BIN/cwm-verify" --target test || FAILURES+=("extension registration (cwm-verify)")
php build/verify-migrations.php "$NEWVER" || FAILURES+=("schema (verify-migrations)")

echo
if [ ${#FAILURES[@]} -gt 0 ]; then
    echo "UPGRADE TEST FAILED — ${BASEVER} -> ${NEWVER}:"
    for f in "${FAILURES[@]}"; do
        echo "  - ${f}"
    done
    exit 1
fi

echo "UPGRADE TEST PASSED — ${BASEVER} -> ${NEWVER}."
