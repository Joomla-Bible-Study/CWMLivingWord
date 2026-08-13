/**
 * build.properties access for the E2E suite.
 *
 * One parser shared by playwright.config.js and global-setup.js, with the
 * same two-layer resolution the PHP tooling uses:
 *   1. build.dist.properties — defaults (committed)
 *   2. build.properties      — local overrides for credentials (gitignored)
 *
 * Nothing here assumes what an install is named. Sites are discovered from
 * `builder.installs` and their `builder.<id>.role`, mirroring
 * PropertiesReader::installsFor() on the PHP side — someone whose test
 * install is called `trunk-test` gets exactly the same behaviour as one
 * whose install is called `j6-test`.
 */

const fs = require('fs');
const path = require('path');

function parseProperties(filePath) {
    const props = {};
    if (!fs.existsSync(filePath)) {
        return props;
    }
    const lines = fs.readFileSync(filePath, 'utf8').split('\n');
    for (const line of lines) {
        const trimmed = line.trim();
        if (!trimmed || trimmed.startsWith('#')) {
            continue;
        }
        const eq = trimmed.indexOf('=');
        if (eq === -1) {
            continue;
        }
        props[trimmed.slice(0, eq).trim()] = trimmed.slice(eq + 1).trim();
    }
    return props;
}

/**
 * @param {string} root Repo root.
 * @returns {object} Merged properties, build.properties winning.
 */
function loadProps(root) {
    const dist = parseProperties(path.join(root, 'build.dist.properties'));
    const local = parseProperties(path.join(root, 'build.properties'));
    return { ...dist, ...local };
}

/**
 * The install ids listed in builder.installs.
 *
 * @param {object} props
 * @returns {string[]}
 */
function installIds(props) {
    return (props['builder.installs'] || '')
        .split(',')
        .map((s) => s.trim())
        .filter(Boolean);
}

/**
 * Connection details for one named install.
 *
 * `path` is the filesystem root, needed by anything that shells out to the
 * install's own `cli/joomla.php` — seeding the front-end member account, for
 * one. It is per-install (`builder.<id>.path`, as CI writes it) and falls back
 * to positional lookup in the legacy comma-separated `builder.joomla_paths`,
 * which is how the older dev installs are still declared. Empty when neither
 * answers, and callers must cope: a URL-only install can still be browsed, it
 * just cannot be seeded.
 *
 * `member*` is the front-end account the member-* projects log in as. Not the
 * admin: these specs exercise what a subscriber can do, and running them as a
 * Super User would hide every permission the site actually applies.
 *
 * `partner*` is a second such account. The accountability-partner digest only
 * mails a *mutual* pairing where the partner shares their progress, so proving
 * it needs two readers who chose each other — one account cannot pair with
 * itself.
 *
 * @param {object} props
 * @param {string} id
 * @returns {{id: string, role: string, url: string, path: string, username: string, password: string,
 *            memberUsername: string, memberPassword: string, memberEmail: string}}
 */
function installById(props, id) {
    return {
        id,
        role: props[`builder.${id}.role`] || '',
        url: props[`builder.${id}.url`] || '',
        path: props[`builder.${id}.path`] || pathFromJoomlaPaths(props, id),
        username: props[`builder.${id}.username`] || props['builder.joomla_username'] || '',
        password: props[`builder.${id}.password`] || props['builder.joomla_password'] || '',
        memberUsername: props[`builder.${id}.member_username`] || 'lw-e2e-member',
        memberPassword: props[`builder.${id}.member_password`] || 'lw-e2e-member-pw-9134',
        memberEmail: props[`builder.${id}.member_email`] || 'lw-e2e-member@example.com',
        partnerUsername: props[`builder.${id}.partner_username`] || 'lw-e2e-partner',
        partnerPassword: props[`builder.${id}.partner_password`] || 'lw-e2e-partner-pw-9134',
        partnerEmail: props[`builder.${id}.partner_email`] || 'lw-e2e-partner@example.com',
    };
}

/**
 * Recover an install root from the legacy `builder.joomla_paths` list.
 *
 * The list is positional and carries no ids, so match on the directory name:
 * install id `j6dev` against a path ending `j6-dev` or `j6dev`. Only used when
 * the install declares no `builder.<id>.path` of its own.
 *
 * @param {object} props
 * @param {string} id
 * @returns {string}
 */
function pathFromJoomlaPaths(props, id) {
    const paths = (props['builder.joomla_paths'] || props['builder.joomla_path'] || '')
        .split(',')
        .map((entry) => entry.trim())
        .filter(Boolean);

    const wanted = id.toLowerCase().replace(/[^a-z0-9]/g, '');

    return paths.find((entry) => path.basename(entry).toLowerCase().replace(/[^a-z0-9]/g, '') === wanted) || '';
}

/**
 * The first install marked with the given role, or null. The role=test
 * install is the one `composer test:install` provisions from the built
 * package; the API acceptance suite runs there and nowhere else.
 *
 * @param {object} props
 * @param {string} role
 * @returns {object|null}
 */
function installForRole(props, role) {
    const id = installIds(props).find((name) => (props[`builder.${name}.role`] || '') === role);

    return id ? installById(props, id) : null;
}

module.exports = { parseProperties, loadProps, installIds, installById, installForRole };
