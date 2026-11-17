#!/usr/bin/env bash
#
# Versions for the release-gate scripts, test-install.sh and test-upgrade.sh.
#
# Sourced, not run. gate_resolve_versions sets:
#
#   ARTIFACT_VERSION  What the built package actually carries: the <version> in
#                     the package manifest on disk. `composer build -- --version`
#                     only names the output file; the manifests inside the zip
#                     are whatever is on disk. So the filename has to come from
#                     here, or the zip's name and its contents disagree.
#
#   RELEASE_VERSION   The version being validated. cwm-release exports
#                     CWM_RELEASE_VERSION to the gate, because the gate runs
#                     before the bump, while the manifests still carry the
#                     previous release. It's the version results are reported
#                     against, and the schema the code under test must produce.
#                     Outside a release (a nightly, a PR, a hand run) it's
#                     ARTIFACT_VERSION. That's the order cwm-baseline uses to
#                     pick an upgrade baseline. test-upgrade.sh passes this as
#                     its --target, so the two can't disagree.
#
#   LABEL_NOTE        Empty when the two agree. Otherwise a parenthetical for
#                     headlines and result lines, so a pass can't be read as
#                     having tested only the previous version.
#
# Both scripts used to read versions.json `active_development`, which is
# neither of these. During the 5.8.0-rc1 release it still said 5.7.2, so the
# gate reported "CLEAN-INSTALL TEST PASSED for 5.7.2" and
# "UPGRADE TEST PASSED — 5.7.2 -> 5.7.2". After a pre-release it also lags the
# manifests, because only a stable release moves it.
#
# @package  Livingword.Build
# @since    __DEPLOY_VERSION__

# The variables below are set for the scripts that source this file, and the
# single-quoted strings are PHP code, not shell expansions.
# shellcheck disable=SC2034,SC2016
gate_resolve_versions() {
    local manifest

    manifest="$(php -r '$c = json_decode((string) file_get_contents("cwm-build.config.json"), true); echo $c["manifests"]["package"] ?? "";')"

    ARTIFACT_VERSION="$(php -r '$x = @simplexml_load_file($argv[1]); echo $x === false ? "" : trim((string) $x->version);' "$manifest")"

    if [ -z "$ARTIFACT_VERSION" ]; then
        echo "ERROR: could not read <version> from the package manifest (${manifest:-not configured})." >&2
        return 1
    fi

    RELEASE_VERSION="${CWM_RELEASE_VERSION:-$ARTIFACT_VERSION}"
    LABEL_NOTE=""

    if [ "$RELEASE_VERSION" != "$ARTIFACT_VERSION" ]; then
        LABEL_NOTE=" (build labelled ${ARTIFACT_VERSION} until the bump)"
    fi
}
