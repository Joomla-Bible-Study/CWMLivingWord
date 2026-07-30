#!/usr/bin/env bash
#
# Clean-install release test.
#
# Resets every role=test install, builds the package at the active-development
# version, installs it into a LivingWord-free site (a true fresh install — the
# install() scriptfile path plus the full install.sql), then confirms every
# extension in the package actually registered.
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

VERSION="$(php -r '$v = json_decode(file_get_contents("build/versions.json"), true); echo $v["active_development"]["version"] ?? ($v["current"]["version"] ?? "");')"

if [ -z "$VERSION" ]; then
    echo "ERROR: could not resolve active-development version from build/versions.json" >&2
    exit 1
fi

ZIP="build/dist/pkg_livingword-${VERSION}.zip"

echo "========================================================================"
echo " CLEAN-INSTALL TEST — pkg_livingword ${VERSION}"
echo "========================================================================"

echo "-- [1/4] reset test site(s) to a clean slate"
php build/reset-testsite.php

echo "-- [2/4] build package ${VERSION}"
composer build -- --version "$VERSION"

if [ ! -f "$ZIP" ]; then
    echo "ERROR: expected build artifact not found: $ZIP" >&2
    exit 1
fi

echo "-- [3/4] install ${ZIP} (fresh)"
"$BIN/cwm-install-zip" --zip "$ZIP"

echo "-- [4/4] verify extension registration"
"$BIN/cwm-verify" --target test

echo
echo "CLEAN-INSTALL TEST PASSED for ${VERSION}."
