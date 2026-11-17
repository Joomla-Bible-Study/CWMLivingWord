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
# Which baseline
# --------------
# cwm-baseline (cwm-build-tools, #103) resolves and fetches it. It picks the
# newest release strictly older than the version being released, at or above
# `baseline.minimum` in cwm-build.config.json, preferring a stable release. That
# is the artifact most sites are actually on, and it pulls in every migration
# since. LivingWord used to carry its own copy of this lookup. cwm-baseline was
# written to replace it and Proclaim's, which had drifted apart.
#
# The target is RELEASE_VERSION from gate-versions.sh, passed as --target. It's
# CWM_RELEASE_VERSION during a release (the gate runs before the bump) and the
# manifest version otherwise. That's the order cwm-baseline itself uses, so the
# version a pass reports and the version the baseline was chosen for are the
# same by construction. Reading versions.json instead is how the 5.7.0 gate
# skipped its upgrade phase, and how 5.8.0-rc1's reported "5.7.2 -> 5.7.2".
#
# Run via: composer test:upgrade [baseline-version]
#
# @package  Livingword.Build
# @since    __DEPLOY_VERSION__

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

BIN="libraries/vendor/bin"

# ARTIFACT_VERSION is what the zip carries (the manifests on disk) and names the
# build. RELEASE_VERSION is what this run validates an upgrade *to*. That's
# CWM_RELEASE_VERSION under cwm-release, whose gate runs before the bump, and
# otherwise the same as ARTIFACT_VERSION. See gate-versions.sh.
# shellcheck source=build/gate-versions.sh
. "$ROOT/build/gate-versions.sh"
gate_resolve_versions || exit 1

# Exit 3 means "not applicable", as distinct from "failed": no released artifact
# qualifies as a baseline, as on a project's first release or when every
# candidate is below baseline.minimum. It's not a defect, and failing the gate
# for it would teach people to ignore the gate. test-release.sh maps 3 to a loud
# SKIPPED, deliberately: a silently skipped phase reads as a pass, which is how
# an ungated release happens.
not_applicable() {
    echo "NOT APPLICABLE: no released package older than ${RELEASE_VERSION} is usable as a baseline." >&2
    echo "                Releases below baseline.minimum in cwm-build.config.json cannot install" >&2
    echo "                (#95). To name a baseline explicitly:  composer test:upgrade -- 5.7.2" >&2
    exit 3
}

if [ -n "${1:-}" ]; then
    BASEVER="$1"
else
    # --print writes a human line and then the bare version; the version is last.
    set +e
    BASELINE_OUT="$("$BIN/cwm-baseline" --print --target "$RELEASE_VERSION")"
    BASELINE_STATUS=$?
    set -e

    if [ "$BASELINE_STATUS" -eq 3 ]; then
        # cwm-baseline has already said why; add only how to override it here.
        echo "                For this test:  composer test:upgrade -- <version>" >&2
        exit 3
    elif [ "$BASELINE_STATUS" -ne 0 ]; then
        echo "ERROR: cwm-baseline could not resolve a baseline (exit ${BASELINE_STATUS})." >&2
        printf '%s\n' "$BASELINE_OUT" >&2
        exit 1
    fi

    BASEVER="$(printf '%s\n' "$BASELINE_OUT" | tail -n 1)"
fi

if [ -z "$BASEVER" ] || [ "$RELEASE_VERSION" = "$BASEVER" ]; then
    not_applicable
fi

BASEZIP="build/dist/pkg_livingword-${BASEVER}.zip"
NEWZIP="build/dist/pkg_livingword-${ARTIFACT_VERSION}.zip"

echo "========================================================================"
echo " UPGRADE TEST — ${BASEVER}  ->  ${RELEASE_VERSION}${LABEL_NOTE}"

# The tree under test is the one that ships; only the stamp lags, because the
# gate runs before the bump. LABEL_NOTE says so wherever the result is printed.
if [ -n "$LABEL_NOTE" ]; then
    echo " Baseline chosen relative to ${RELEASE_VERSION}, the version being released."
fi

echo "========================================================================"

echo "-- [1/6] reset test site(s) to a clean slate"
"$BIN/cwm-reset-testsite"

echo "-- [2/6] fetch released baseline ${BASEVER}"
"$BIN/cwm-baseline" --version "$BASEVER"

if [ ! -f "$BASEZIP" ]; then
    echo "ERROR: cwm-baseline reported success but ${BASEZIP} is not there." >&2
    exit 1
fi

echo "-- [3/6] install baseline ${BASEVER} (the 'before' state)"
"$BIN/cwm-install-zip" --zip "$BASEZIP"

echo "-- [4/6] build new package ${ARTIFACT_VERSION}"
composer build -- --version "$ARTIFACT_VERSION"

if [ ! -f "$NEWZIP" ]; then
    echo "ERROR: expected build artifact not found: $NEWZIP" >&2
    exit 1
fi

echo "-- [5/6] install ${ARTIFACT_VERSION} over ${BASEVER} (triggers update() + migrations)"
"$BIN/cwm-install-zip" --zip "$NEWZIP"

# As in test-install.sh: record and carry on, so one known failure does not
# hide the rest.
FAILURES=()

echo "-- [6/6] verify registration + schema after upgrade"
"$BIN/cwm-verify" --target test || FAILURES+=("extension registration (cwm-verify)")
# The release version, not the label: the code under test is the release's.
php build/verify-migrations.php "$RELEASE_VERSION" || FAILURES+=("schema (verify-migrations)")

echo
if [ ${#FAILURES[@]} -gt 0 ]; then
    echo "UPGRADE TEST FAILED — ${BASEVER} -> ${RELEASE_VERSION}${LABEL_NOTE}:"
    for f in "${FAILURES[@]}"; do
        echo "  - ${f}"
    done
    exit 1
fi

echo "UPGRADE TEST PASSED — ${BASEVER} -> ${RELEASE_VERSION}${LABEL_NOTE}."
