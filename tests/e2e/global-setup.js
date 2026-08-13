/**
 * Playwright global setup — authenticates against the dev-site admins and
 * saves session storage state for reuse across all admin specs.
 *
 * Runs once before the entire test suite. Three behaviours matter here, all
 * from #1342 — a transient login failure used to cost the whole run:
 *
 *   - Only the sites the selected projects actually use are authenticated.
 *     Which sites those are is read from the resolved config: a project that
 *     declares a storageState needs its site's admin session, one that
 *     doesn't (the site-* projects) needs nothing. `--project=admin-j6`
 *     therefore never touches j5-dev.
 *   - A still-valid saved session is reused instead of re-authenticating.
 *     Joomla admin sessions outlive a test run by default, so back-to-back
 *     runs skip the login dance (and its flake surface) entirely.
 *   - A login that does run is retried with backoff before it fails the run.
 *     The observed failure mode is a rejection that succeeds moments later
 *     with identical credentials — a flake, not a misconfiguration.
 *
 * Configuration follows a two-layer approach:
 *   1. build.dist.properties — defaults (committed)
 *   2. build.properties      — local overrides for credentials (gitignored)
 *
 * Uses a real browser context so that the user-agent stored in the Joomla
 * PHP session matches the Chromium user-agent used by the actual test runs.
 */

const fs = require('fs');
const path = require('path');
const { execFileSync } = require('child_process');
const { chromium } = require('@playwright/test');
const { loadProps, installById, installForRole } = require('./helpers/properties');

/**
 * Project names passed on the command line, e.g. --project=admin-j6.
 *
 * Playwright hands globalSetup the full resolved config with every project
 * in it, regardless of any --project filter (upstream: playwright#14212),
 * so the filter has to be recovered from argv. Empty array = no filter.
 */
function selectedProjectNames() {
    const names = [];
    const argv = process.argv;

    for (let i = 0; i < argv.length; i += 1) {
        if (argv[i] === '--project' && argv[i + 1]) {
            names.push(argv[i + 1]);
        } else if (argv[i].startsWith('--project=')) {
            names.push(argv[i].slice('--project='.length));
        }
    }

    return names;
}

/**
 * Whether a saved storage state still holds an authenticated admin session.
 *
 * One page load either way: reusing costs the same navigation the login
 * would start with, and skips the credential dance that follows.
 */
async function stateStillValid(browser, baseUrl, storageStatePath) {
    if (!fs.existsSync(storageStatePath)) {
        return false;
    }

    const ctx = await browser.newContext({ ignoreHTTPSErrors: true, storageState: storageStatePath });

    try {
        const page = await ctx.newPage();
        await page.goto(`${baseUrl}/administrator/index.php`, {
            waitUntil: 'domcontentloaded',
            timeout: 15000,
        });

        // Valid means the sidebar toggle rendered — a positive signal only an
        // authenticated backend produces (the login page has no sidebar). "No
        // login form" would also be true of an error page, and treating that
        // as a live session skips the login that would surface the problem.
        //
        // waitFor, not isVisible: isVisible() answers for the instant it is
        // called and ignores its timeout option.
        await page.locator('#menu-collapse').waitFor({ state: 'visible', timeout: 5000 });

        return true;
    } catch {
        return false;
    } finally {
        await ctx.close();
    }
}

async function loginAdmin(browser, baseUrl, username, password, storageStatePath) {
    const ctx = await browser.newContext({ ignoreHTTPSErrors: true });

    try {
        const page = await ctx.newPage();

        // Load the login page
        await page.goto(`${baseUrl}/administrator/index.php`, { waitUntil: 'networkidle' });

        // Confirm the login form is present. Already-authenticated is only
        // claimed on the positive signal (the admin sidebar), never on the
        // form's mere absence.
        const loginVisible = await page.locator('#form-login').isVisible().catch(() => false);
        if (!loginVisible) {
            const authed = await page.locator('#menu-collapse')
                .waitFor({ state: 'visible', timeout: 5000 })
                .then(() => true, () => false);
            if (authed) {
                console.log(`  Already authenticated at ${baseUrl}, saving state.`);
                await ctx.storageState({ path: storageStatePath });
                return;
            }
            throw new Error(`Neither the login form nor the admin UI rendered at ${baseUrl}/administrator/`);
        }

        // Fill credentials
        await page.fill('#mod-login-username', username);
        await page.fill('#mod-login-password', password);

        // Submit the form via JS to bypass Joomla's form-validate JS which
        // can prevent submission in headless mode. form.submit() bypasses the
        // onsubmit handler but correctly includes all hidden fields (CSRF token).
        await page.evaluate(() => document.getElementById('form-login').submit());

        // Success must be a POSITIVE signal — the sidebar toggle, which only
        // an authenticated backend renders (the login page has no sidebar).
        // "The login form is gone" is not enough: mid-redirect the form is
        // absent from a page that is not logged in either, and that false
        // success once saved a guest session as auth state while the real
        // problem (stale credentials in build.properties) went unreported.
        //
        // waitFor, not waitForLoadState-then-isVisible: networkidle can
        // resolve on the *old* page before the submit's navigation begins,
        // and isVisible() answers for that instant without waiting.
        const authed = await page.locator('#menu-collapse')
            .waitFor({ state: 'visible', timeout: 25000 })
            .then(() => true, () => false);

        if (!authed) {
            const rejected = await page.locator('#form-login').isVisible().catch(() => false);
            throw new Error(
                `Login failed for ${baseUrl} — ` +
                (rejected
                    ? 'credentials rejected (check this site\'s username/password in build.properties).\n'
                    : 'no admin UI appeared after submit.\n') +
                `Current URL: ${page.url()}`
            );
        }

        await ctx.storageState({ path: storageStatePath });
        console.log(`  Saved auth state → ${path.basename(storageStatePath)}`);
    } finally {
        await ctx.close();
    }
}

/**
 * loginAdmin with retries. The whole suite hangs off this succeeding, so a
 * transient rejection gets a second and third chance before it is allowed
 * to be fatal.
 */
async function loginAdminWithRetry(browser, baseUrl, username, password, storageStatePath) {
    const attempts = 3;

    for (let attempt = 1; attempt <= attempts; attempt += 1) {
        try {
            await loginAdmin(browser, baseUrl, username, password, storageStatePath);
            return;
        } catch (err) {
            if (attempt === attempts) {
                throw err;
            }
            const delayMs = attempt * 2500;
            console.warn(
                `  Login attempt ${attempt}/${attempts} failed (${String(err.message || err).split('\n')[0]}); `
                + `retrying in ${delayMs / 1000}s…`
            );
            await new Promise((resolve) => { setTimeout(resolve, delayMs); });
        }
    }
}

/**
 * Ensure the front-end member account exists, using the install's own CLI.
 *
 * Seeded rather than assumed: the member specs are about what a subscriber can
 * do, so they need an account that is not a Super User, and every developer
 * would otherwise have to hand-create the same one. `user:add` answers "The
 * username already exists!" on a repeat run, which is success here — what
 * matters is that the account exists, not who made it.
 *
 * Silent no-op when the install declares no filesystem path. A URL-only site
 * can still be browsed, and if the account really is missing, the login below
 * fails with a message that says exactly that.
 *
 * @param   {object}  install  Install descriptor from installById()
 *
 * @returns {void}
 */
function ensureMemberAccount(install, account) {
    if (!install.path || !fs.existsSync(path.join(install.path, 'cli/joomla.php'))) {
        return;
    }

    let output;

    try {
        output = execFileSync('php', [
            path.join(install.path, 'cli/joomla.php'),
            'user:add',
            `--username=${account.username}`,
            '--name=LivingWord E2E Member',
            `--password=${account.password}`,
            `--email=${account.email}`,
            '--usergroup=Registered',
        ], { encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'] });
    } catch (error) {
        output = `${error.stdout || ''}${error.stderr || ''}`;
    }

    if (/already exists/i.test(output) || /User created/i.test(output)) {
        return;
    }

    console.log(`  Warning: could not seed ${account.username} — ${output.trim().slice(0, 200)}`);
}

/**
 * Whether a saved state still holds an authenticated front-end session.
 *
 * Asks for the profile view, which com_users renders only to a logged-in user
 * and otherwise answers with the login form.
 *
 * @param   {object}  browser           Playwright browser
 * @param   {string}  baseUrl           Site root
 * @param   {string}  storageStatePath  Saved state file
 *
 * @returns {Promise<boolean>}
 */
async function memberStateStillValid(browser, baseUrl, storageStatePath) {
    if (!fs.existsSync(storageStatePath)) {
        return false;
    }

    const ctx = await browser.newContext({ ignoreHTTPSErrors: true, storageState: storageStatePath });

    try {
        const page = await ctx.newPage();

        await page.goto(`${baseUrl}/index.php?option=com_users&view=profile`, {
            waitUntil: 'domcontentloaded',
            timeout: 15000,
        });

        return !(await page.locator('#username').isVisible().catch(() => false));
    } catch {
        return false;
    } finally {
        await ctx.close();
    }
}

/**
 * Log the member in on the front end and save the session.
 *
 * @param   {object}  browser           Playwright browser
 * @param   {object}  install           Install descriptor from installById()
 * @param   {string}  storageStatePath  Where to save the session
 *
 * @returns {Promise<void>}
 */
async function loginMember(browser, install, account, storageStatePath) {
    const ctx = await browser.newContext({ ignoreHTTPSErrors: true });

    try {
        const page = await ctx.newPage();

        await page.goto(`${install.url}/index.php?option=com_users&view=login`, { waitUntil: 'domcontentloaded' });
        await page.fill('#username', account.username);
        await page.fill('#password', account.password);

        // Enter rather than a button click: the login module and the com_users
        // login view render different submit controls depending on template,
        // and both submit the form the field belongs to.
        await page.locator('#password').press('Enter');
        await page.waitForLoadState('domcontentloaded');

        await page.goto(`${install.url}/index.php?option=com_users&view=profile`, { waitUntil: 'domcontentloaded' });

        if (await page.locator('#username').isVisible().catch(() => false)) {
            throw new Error(
                `Front-end login failed for ${account.username} at ${install.url}.\n` +
                'Set builder.<install>.member_username / member_password in build.properties, or let global ' +
                'setup seed the account by declaring builder.<install>.path.'
            );
        }

        await ctx.storageState({ path: storageStatePath });
        console.log(`  Saved member session → ${path.basename(storageStatePath)}`);
    } finally {
        await ctx.close();
    }
}

/**
 * Ensure the member is subscribed to a reading plan.
 *
 * Not cosmetic setup — it is what makes most of the site reachable at all.
 * cwmhome short-circuits to the onboarding picker for anyone without a plan,
 * and cwmplanview, cwmgroups and cwmsettings render that same picker, so an
 * unsubscribed session sees the onboarding hero at every one of those URLs and
 * a spec looking for their real content finds nothing.
 *
 * Subscribing through the onboarding form rather than the database keeps the
 * fixture honest: it is the same POST a first-time visitor makes, so if
 * cwmsubscribe.start ever breaks, every member spec fails at setup and says so.
 *
 * @param   {object}  browser  Playwright browser
 * @param   {object}  install  Install descriptor from installById()
 * @param   {string}  statePath  Saved member session
 *
 * @returns {Promise<void>}
 */
async function ensureMemberSubscription(browser, install, statePath) {
    const ctx = await browser.newContext({ ignoreHTTPSErrors: true, storageState: statePath });

    try {
        const page = await ctx.newPage();

        await page.goto(`${install.url}/index.php?option=com_livingword&view=cwmhome`, {
            waitUntil: 'domcontentloaded',
        });

        const subscribe = page.locator('form[action*="cwmsubscribe"] button[type="submit"]').first();

        if (!(await subscribe.count())) {
            return;
        }

        console.log('  Member has no plan — subscribing through the onboarding form.');
        await subscribe.click();
        await page.waitForLoadState('domcontentloaded');

        const stillOnboarding = await page.locator('.com-livingword-onboarding').count();

        if (stillOnboarding) {
            console.log('  Warning: the member is still unsubscribed after posting cwmsubscribe.start.');

            return;
        }

        // The subscription lives in #__livingword_users, not the session, but
        // re-saving keeps this state file the single thing a spec needs.
        await ctx.storageState({ path: statePath });
    } finally {
        await ctx.close();
    }
}

/**
 * Authenticate the front-end member for any selected project that wants one.
 *
 * Mirrors the admin pass above: a project needs a member session exactly when
 * it declares that session's storageState, so running only `--project=site-j6`
 * never logs anybody in.
 *
 * @param   {object}    config    Resolved Playwright config
 * @param   {object}    props     build.properties
 * @param   {string}    authDir   Directory holding saved sessions
 * @param   {string[]}  selected  Project names from argv, empty for all
 *
 * @returns {Promise<void>}
 */
async function authenticateMembers(config, props, authDir, selected) {
    const stateFile = 'member-j6.json';
    const projects  = (config.projects || [])
        .filter((p) => !selected.length || selected.includes(p.name));

    // Any j6 browser project, not just the one that declares the member state.
    //
    // Sessions are only refreshed for the projects selected, so a spec in one
    // tier that reaches into the other gets whatever stale file is on disk —
    // and a saved *login page* reads exactly like an empty list, which cost an
    // hour of chasing an admin screen that appeared unable to see its own
    // groups. The daily-email spec legitimately needs both: an administrator to
    // run the task, and the member whose preferences decide whether the routine
    // has anybody to send to.
    const wanted = projects.some(
        (p) => p.use && (
            (typeof p.use.storageState === 'string' && p.use.storageState.endsWith(stateFile))
            || p.name === 'admin-j6'
        )
    );

    if (!wanted) {
        return;
    }

    const install = installById(props, 'j6dev');

    // Two readers, not one. The accountability-partner digest only mails a
    // mutual pairing, and an account cannot pair with itself — so the second
    // account is what makes that third routine testable at all. Both are
    // subscribed, because every plan-bearing view falls back to the onboarding
    // picker without a plan.
    const accounts = [
        {
            username: install.memberUsername,
            password: install.memberPassword,
            email: install.memberEmail,
            stateFile,
        },
        {
            username: install.partnerUsername,
            password: install.partnerPassword,
            email: install.partnerEmail,
            stateFile: 'partner-j6.json',
        },
    ];

    const browser = await chromium.launch({ channel: 'chromium' });

    try {
        for (const account of accounts) {
            const statePath = path.join(authDir, account.stateFile);

            ensureMemberAccount(install, account);

            if (await memberStateStillValid(browser, install.url, statePath)) {
                console.log(`Reusing valid session for ${account.username} (${account.stateFile}).`);
            } else {
                console.log(`\nAuthenticating ${account.username} against ${install.url}…`);
                await loginMember(browser, install, account, statePath);
            }

            await ensureMemberSubscription(browser, install, statePath);
        }
    } finally {
        await browser.close();
    }
}

module.exports = async function globalSetup(config) {
    const root = path.join(__dirname, '../..');
    const props = loadProps(root);

    const authDir = path.join(__dirname, '.auth');
    if (!fs.existsSync(authDir)) {
        fs.mkdirSync(authDir, { recursive: true });
    }

    // The sites this repo can authenticate against, keyed by the storage
    // state file the Playwright projects reference. The two dev sites have
    // fixed property keys; the test site is discovered by role, because
    // install naming is local to each build.properties.
    const testInstall = installForRole(props, 'test');

    const sites = [
        {
            label: 'j5-dev',
            url: props['builder.j5dev.url'] || 'https://j5-dev.local:8890',
            username: props['builder.j5dev.username'] || props['builder.joomla_username'],
            password: props['builder.j5dev.password'] || props['builder.joomla_password'],
            stateFile: 'admin-j5.json',
        },
        {
            label: 'j6-dev',
            url: props['builder.j6dev.url'] || 'https://j6-dev.local:8890',
            username: props['builder.j6dev.username'] || props['builder.joomla_username'],
            password: props['builder.j6dev.password'] || props['builder.joomla_password'],
            stateFile: 'admin-j6.json',
        },
        ...(testInstall ? [{
            label: testInstall.id,
            url: testInstall.url,
            username: testInstall.username,
            password: testInstall.password,
            stateFile: 'admin-test.json',
        }] : []),
    ];

    // Authenticate only the sites the selected projects consume. A project
    // needs a site's admin session exactly when it declares that site's
    // storageState; the site-* projects declare none and need none.
    const selected = selectedProjectNames();
    const projects = (config.projects || [])
        .filter((p) => !selected.length || selected.includes(p.name));

    const needed = sites.filter((site) => projects.some(
        (p) => p.use && typeof p.use.storageState === 'string' && p.use.storageState.endsWith(site.stateFile)
    ));

    // Before the early return below: a run of member specs alone needs no
    // admin session at all, and must still get its member one.
    await authenticateMembers(config, props, authDir, selected);

    if (!needed.length) {
        console.log('\nGlobal setup: no selected project needs an admin session.\n');
        return;
    }

    const missing = needed.filter((site) => !site.username || !site.password);
    if (missing.length) {
        throw new Error(
            `Missing admin credentials for ${missing.map((s) => s.label).join(', ')}.\n` +
            'Set builder.j5dev.username / builder.j5dev.password (and j6dev) in build.properties.\n' +
            'Or set builder.joomla_username / builder.joomla_password as a shared fallback.'
        );
    }

    // globalSetup runs outside the project fixtures, so the `use.channel`
    // setting in playwright.config.js does not reach this call — it has to be
    // repeated. Without it this launches Playwright's default headless
    // browser, chrome-headless-shell, while every test runs real Chromium.
    //
    // Two reasons that matters: the shell may not be installed at all (this
    // failed here with "Executable doesn't exist"), and authenticating in one
    // browser while testing in another is a difference worth not having in a
    // login flow.
    const browser = await chromium.launch({ channel: 'chromium' });

    try {
        for (const site of needed) {
            const statePath = path.join(authDir, site.stateFile);

            if (await stateStillValid(browser, site.url, statePath)) {
                console.log(`Reusing valid admin session for ${site.label} (${path.basename(statePath)}).`);
                continue;
            }

            console.log(`\nAuthenticating against ${site.label} (${site.url})…`);
            await loginAdminWithRetry(browser, site.url, site.username, site.password, statePath);
        }
    } finally {
        await browser.close();
    }

    console.log('Global setup complete.\n');
};
