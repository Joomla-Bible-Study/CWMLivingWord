/**
 * Playwright E2E configuration for CWM LivingWord.
 *
 * Today the suite is accessibility scanning only (WCAG 2.2 AA). The project
 * layout anticipates functional specs alongside it: anything under
 * tests/e2e/admin runs authenticated, anything under tests/e2e/site as a
 * visitor.
 *
 * Configuration is resolved in two layers:
 *   1. build.dist.properties — defaults (committed, safe to share)
 *   2. build.properties      — local overrides for credentials/URLs (gitignored)
 *
 * Run `composer setup` if build.properties does not exist yet.
 */

const { defineConfig, devices } = require('@playwright/test');
const { loadProps, installForRole } = require('./tests/e2e/helpers/properties');

const props = loadProps(__dirname);

const j6Url = props['builder.j6dev.url'] || 'https://j6-dev.local:8890';

// The role=test install — the site `composer test:install` provisions from the
// built package. Discovered by role, not by name: install ids are local to each
// developer's build.properties. No role=test install, no API project.
const testInstall = installForRole(props, 'test');

module.exports = defineConfig({
    testDir: './tests/e2e',
    outputDir: 'build/test-results/',
    fullyParallel: false,
    workers: 1,
    retries: 0,
    timeout: 30000,
    globalSetup: require.resolve('./tests/e2e/global-setup.js'),
    reporter: [
        ['list'],
        ['html', { outputFolder: 'build/reports/e2e', open: 'never' }],
    ],
    use: {
        ignoreHTTPSErrors: true,
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',

        // Real Chromium rather than Playwright's default headless shell.
        //
        // `headless: true` resolves to chrome-headless-shell, the old headless
        // implementation Google split out of the Chrome binary in Chrome 132.
        // It is supported and faster, but it is not the renderer any visitor
        // uses — and two of the rules gated on here measure rendered output
        // rather than markup:
        //
        //   target-size    2.5.8, new in WCAG 2.2 — element size in CSS px
        //   color-contrast 1.4.3 — computed foreground/background colours
        //
        // A pass from a renderer nobody runs is a weak basis for a conformance
        // claim. Accuracy is worth more than the speed.
        channel: 'chromium',
    },
    projects: [
        // Scanned on Joomla 6 only. LivingWord renders the same markup on 5, 6
        // and 7 — the templates carry no version branching — so scanning every
        // platform would report identical violations several times over and
        // slow the gate for nothing. Platform differences are what the install
        // and upgrade suites are for.
        //
        // If a violation ever proves platform-specific it will be a Joomla
        // template difference outside the scanned region; add a project then,
        // deliberately.
        {
            name: 'admin-j6',
            testMatch: '**/admin/**/*.spec.js',
            use: {
                ...devices['Desktop Chrome'],
                baseURL: j6Url,
                storageState: 'tests/e2e/.auth/admin-j6.json',
            },
        },
        {
            name: 'site-j6',
            testMatch: '**/site/**/*.spec.js',
            use: {
                ...devices['Desktop Chrome'],
                baseURL: j6Url,
            },
        },
        // Front-end specs that need a subscriber. Separate from site-j6 rather
        // than logging that project in, because the guest state is itself worth
        // scanning: cwmhome short-circuits to the onboarding picker for anyone
        // without a plan, so a logged-in site-j6 would stop covering the view
        // most first-time visitors actually see.
        //
        // The account is a plain Registered user seeded by global setup, never
        // the admin — running these as a Super User would hide every permission
        // the site applies.
        {
            name: 'member-j6',
            testMatch: '**/member/**/*.spec.js',
            use: {
                ...devices['Desktop Chrome'],
                baseURL: j6Url,
                storageState: 'tests/e2e/.auth/member-j6.json',
            },
        },
        // API acceptance runs against the role=test install and nowhere else.
        // Its whole point is asserting what a package install produces — a
        // symlinked dev site cannot represent that, and a dev site is exactly
        // what would hide a route that was never registered.
        ...(testInstall ? [{
            name: 'api-test',
            testMatch: '**/api/**/*.spec.js',
            use: {
                ...devices['Desktop Chrome'],
                baseURL: testInstall.url,
                storageState: 'tests/e2e/.auth/admin-test.json',
            },
        }] : []),
    ],
});
