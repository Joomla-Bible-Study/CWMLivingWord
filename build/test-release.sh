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
SKIPPED=()

# Exit 3 from a phase means "not applicable here", not "passed" and not
# "failed" — see the note in test-upgrade.sh. Reported loudly at the end so a
# skipped phase can never be mistaken for a green one.
run_phase() {
    local label="$1"
    shift

    "$@"
    local status=$?

    if [ "$status" -eq 0 ]; then
        return 0
    fi

    if [ "$status" -eq 3 ]; then
        SKIPPED+=("$label")

        return 0
    fi

    FAILED+=("$label")
}

echo "########################################################################"
echo "# 1/3  CLEAN INSTALL"
echo "########################################################################"
run_phase "clean install" bash build/test-install.sh

echo
echo "########################################################################"
echo "# 2/3  UPGRADE FROM LAST RELEASE"
echo "########################################################################"
run_phase "upgrade" bash build/test-upgrade.sh

echo
echo "########################################################################"
echo "# 3/3  ACCESSIBILITY (WCAG 2.2 AA)"
echo "########################################################################"
run_phase "accessibility" npm run --silent test:a11y

echo
echo "========================================================================"

for s in "${SKIPPED[@]:-}"; do
    [ -n "$s" ] && echo " SKIPPED (not applicable): ${s}"
done

if [ ${#FAILED[@]} -gt 0 ]; then
    echo " RELEASE GATE FAILED:"
    for f in "${FAILED[@]}"; do
        echo "   - ${f}"
    done
    echo "========================================================================"
    exit 1
fi

if [ ${#SKIPPED[@]} -gt 0 ]; then
    echo " RELEASE GATE PASSED — with the phase(s) above not applicable."
else
    echo " RELEASE GATE PASSED — install, upgrade and accessibility all verified."
fi

echo "========================================================================"
