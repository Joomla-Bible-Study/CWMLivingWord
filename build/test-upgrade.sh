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
# Which baseline, and why not versions.json
# -----------------------------------------
# It used to default to versions.json `current`, and that made this phase skip
# itself at the one moment it mattered most. cwm-release runs the whole
# test:release gate BEFORE it bumps — deliberately, so a failure leaves nothing
# mutated — so during a release `active_development` and `current` are still
# the same string, and the comparison below said "nothing newer to test" about
# the very build being released. The 5.7.0 gate reported install, accessibility
# and API green with the upgrade phase skipped, and the release carried a
# postflight schema repair that only runs on the update route.
#
# So the baseline is resolved from the releases that exist instead: the newest
# STABLE release older than the build under test, which is the artifact most
# sites are actually on, and which pulls in every migration since. Prereleases
# are used only when no stable one qualifies.
#
# "Older than" needs the version being released, though, and that is the half
# the first fix left stale. cwm-release now exports CWM_RELEASE_VERSION for the
# gate; without it the baseline is resolved relative to `active_development`,
# which during a release is still the previous bump — so releasing 5.7.1 with
# the pointer at 5.7.0-beta4 picks the newest stable below beta4. The phase runs
# and upgrades from the wrong "before" state.
#
# Run via: composer test:upgrade [baseline-version]
#
# @package  Livingword.Build
# @since    __DEPLOY_VERSION__

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

BIN="libraries/vendor/bin"

REPO="$(php -r '$c = json_decode(file_get_contents("cwm-build.config.json"), true); echo $c["github"]["owner"] . "/" . $c["github"]["repo"];')"

NEWVER="$(php -r '$v = json_decode(file_get_contents("build/versions.json"), true); echo $v["active_development"]["version"] ?? "";')"

if [ -z "$NEWVER" ]; then
    echo "ERROR: could not resolve active_development from build/versions.json" >&2
    exit 1
fi

# The version this run is validating an upgrade *to*, which is not always the
# version the artifact is labelled with.
#
# cwm-release exports CWM_RELEASE_VERSION for the test:release gate, because the
# gate runs before the bump and nothing on disk names the release yet. That is
# the version the baseline must be chosen relative to: releasing 5.7.1 while
# active_development still reads 5.7.0-beta4 otherwise resolves the newest
# stable release below *beta4*, which can be a whole minor line too old.
#
# It deliberately does not become NEWVER. `composer build -- --version` only
# substitutes the output *filename*; the manifest inside the zip is whatever is
# on disk, so the label has to keep tracking the manifests or the artifact
# claims a version it does not carry.
#
# Unset outside a release — a nightly, a PR, or a hand run — and then
# active_development is genuinely the build under test.
TARGETVER="${CWM_RELEASE_VERSION:-$NEWVER}"

# Nothing older than this installs at all: every package before 5.6.0-beta2
# assembled its inner extensions into a folder the manifest never pointed at,
# so Joomla refused it (#95). Such a release cannot serve as a "before" state.
MIN_BASELINE="5.6.0-beta2"

# The newest usable release strictly older than $1, preferring stable ones.
# Empty output (and a non-zero status) means there is no such release, or that
# GitHub could not be reached.
previous_release() {
    gh release list --repo "$REPO" --limit 100 --json tagName,isPrerelease \
        --jq '.[] | [.tagName, (.isPrerelease | tostring)] | @tsv' 2>/dev/null \
    | php -r '
        $newer  = $argv[1];
        $floor  = $argv[2];
        $stable = [];
        $any    = [];

        while (($line = fgets(STDIN)) !== false) {
            [$tag, $prerelease] = array_pad(explode("\t", trim($line)), 2, "");

            if ($tag === "") {
                continue;
            }

            $version = ltrim($tag, "v");

            // Strictly older than the build under test, and installable.
            if (version_compare($version, $newer, ">=") || version_compare($version, $floor, "<")) {
                continue;
            }

            $any[] = $version;

            if ($prerelease !== "true") {
                $stable[] = $version;
            }
        }

        $pool = $stable ?: $any;

        if ($pool === []) {
            exit(1);
        }

        usort($pool, "version_compare");

        echo end($pool);
    ' "$1" "$MIN_BASELINE"
}

if [ -n "${1:-}" ]; then
    BASEVER="$1"
else
    BASEVER="$(previous_release "$TARGETVER" || true)"
fi

# Exit 3 means "not applicable", as distinct from "failed" — reachable now only
# when no released artifact qualifies as a baseline at all: the first release of
# a project, or an offline run with nothing cached. Not a defect, and failing
# the gate for it would teach people to ignore the gate.
#
# test-release.sh maps 3 to a loud SKIPPED. It stays loud deliberately: a
# silently skipped phase reads as a pass, which is how an ungated release
# happens.
if [ -z "$BASEVER" ] || [ "$TARGETVER" = "$BASEVER" ]; then
    echo "NOT APPLICABLE: no released package older than ${TARGETVER} is usable as a baseline." >&2
    echo "                Releases before ${MIN_BASELINE} cannot install (#95), and GitHub" >&2
    echo "                must be reachable to resolve one. To name a baseline explicitly:" >&2
    echo "                  composer test:upgrade -- 5.6.0" >&2
    exit 3
fi

BASEZIP="build/dist/pkg_livingword-${BASEVER}.zip"
NEWZIP="build/dist/pkg_livingword-${NEWVER}.zip"

echo "========================================================================"
echo " UPGRADE TEST — ${BASEVER}  ->  ${NEWVER}"

# Say so when the artifact is not labelled with the version being released. The
# tree under test is the one that ships; only the stamp differs, because the
# gate runs before the bump. Unstated, that gap reads as though the release
# version itself was exercised.
if [ "$TARGETVER" != "$NEWVER" ]; then
    echo " Releasing ${TARGETVER} — the build is labelled ${NEWVER} until the bump."
    echo " Baseline chosen relative to ${TARGETVER}."
fi

echo "========================================================================"

echo "-- [1/6] reset test site(s) to a clean slate"
php build/reset-testsite.php

echo "-- [2/6] fetch released baseline ${BASEVER}"
mkdir -p build/dist

if [ ! -f "$BASEZIP" ]; then
    gh release download "v${BASEVER}" \
        --repo "$REPO" \
        --pattern "pkg_livingword-${BASEVER}.zip" \
        --dir build/dist
fi

if [ ! -f "$BASEZIP" ]; then
    echo "ERROR: baseline artifact not found: $BASEZIP" >&2
    echo "       Releases before ${MIN_BASELINE} shipped a package that cannot install" >&2
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
