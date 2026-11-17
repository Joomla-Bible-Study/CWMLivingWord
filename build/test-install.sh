#!/usr/bin/env bash
#
# Clean-install release test.
#
# Resets every role=test install, builds the package at the version its
# manifests carry, installs it into a LivingWord-free site (a true fresh
# install — the install() scriptfile path plus the full install.sql), then
# confirms every extension in the package actually registered.
#
# Under cwm-release the version being released (CWM_RELEASE_VERSION) is what
# results are reported against and what the schema is verified for. The build
# keeps the manifests' label until the bump; see gate-versions.sh.
#
# Run via: composer test:install
#
# Steps 4+ are where verification grows. The next additions are a migration
# verifier (every table/column/index from admin/sql actually landed) and an
# upgrade test (install the previous stable, upgrade to this, re-verify), which
# is the path that matters for schema changes like the 5.6.0 tagging work.
#
# @package  Livingword.Build
# @since    __DEPLOY_VERSION__

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

BIN="libraries/vendor/bin"

# shellcheck source=build/gate-versions.sh
. "$ROOT/build/gate-versions.sh"
gate_resolve_versions || exit 1

ZIP="build/dist/pkg_livingword-${ARTIFACT_VERSION}.zip"

echo "========================================================================"
echo " CLEAN-INSTALL TEST — pkg_livingword ${RELEASE_VERSION}${LABEL_NOTE}"
echo "========================================================================"

echo "-- [1/5] reset test site(s) to a clean slate"
"$BIN/cwm-reset-testsite"

echo "-- [2/5] build package ${ARTIFACT_VERSION}"
composer build -- --version "$ARTIFACT_VERSION"

if [ ! -f "$ZIP" ]; then
    echo "ERROR: expected build artifact not found: $ZIP" >&2
    exit 1
fi

echo "-- [3/5] install ${ZIP} (fresh)"
"$BIN/cwm-install-zip" --zip "$ZIP"

# Verification steps record their result and carry on. Aborting at the first
# failure hides every later check behind whichever one happened to be first —
# a known registration drift would mask an unrelated schema regression.
FAILURES=()

echo "-- [4/5] verify extension registration"
"$BIN/cwm-verify" --target test || FAILURES+=("extension registration (cwm-verify)")

echo "-- [5/5] verify the schema landed"
# The release version, not the label: the code under test is the release's, so
# its migrations are the ones that must have landed.
php build/verify-migrations.php "$RELEASE_VERSION" || FAILURES+=("schema (verify-migrations)")

echo
if [ ${#FAILURES[@]} -gt 0 ]; then
    echo "CLEAN-INSTALL TEST FAILED for ${RELEASE_VERSION}${LABEL_NOTE}:"
    for f in "${FAILURES[@]}"; do
        echo "  - ${f}"
    done
    exit 1
fi

echo "CLEAN-INSTALL TEST PASSED for ${RELEASE_VERSION}${LABEL_NOTE}."
