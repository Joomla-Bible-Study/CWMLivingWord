#!/usr/bin/env bash
#
# Full release gate: clean install, then upgrade from the last release.
#
# Runs both suites even when the first fails, then reports. A composer script
# array (["@test:install", "@test:upgrade"]) stops at the first non-zero exit,
# which meant a known registration drift hid the upgrade run entirely — and the
# upgrade path is the half that carries real users.
#
# Run via: composer test:release
#
# @package  Livingword.Build
# @since    __DEPLOY_VERSION__

set -uo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

FAILED=()

echo "########################################################################"
echo "# 1/2  CLEAN INSTALL"
echo "########################################################################"
bash build/test-install.sh || FAILED+=("clean install")

echo
echo "########################################################################"
echo "# 2/2  UPGRADE FROM LAST RELEASE"
echo "########################################################################"
bash build/test-upgrade.sh || FAILED+=("upgrade")

echo
echo "========================================================================"

if [ ${#FAILED[@]} -gt 0 ]; then
    echo " RELEASE GATE FAILED:"
    for f in "${FAILED[@]}"; do
        echo "   - ${f}"
    done
    echo "========================================================================"
    exit 1
fi

echo " RELEASE GATE PASSED — clean install and upgrade both verified."
echo "========================================================================"
