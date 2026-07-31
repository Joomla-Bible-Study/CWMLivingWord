/**
 * E2E — Web Services API acceptance on a package-installed site
 *
 * Runs against the role=test install (`api-test` project) — the site
 * `composer test:install` provisions from the built package, not a dev site
 * assembled from symlinks. That distinction matters here more than anywhere
 * else in the suite: a symlinked dev site can serve an API whose routes were
 * never registered by the shipped package, which is the exact bug this file
 * exists to catch.
 *
 * Two groups of assertion.
 *
 * **Reachability.** An unauthenticated request must be refused with 401, never
 * 404. A 404 means the routes were never registered — the plugin did not
 * install, did not enable, or never ran — and that failure is invisible from
 * the outside because "no such route" and "no such API" look identical.
 *
 * **Authorisation.** The user-scoped endpoints promise that a request reaches
 * only the requester's own rows, and that promise is currently structural: the
 * models default to user 0, the controller reads identity from the session
 * alone. Structure is a good argument and not evidence. These tests are the
 * evidence — in particular the negative ones, which assert what must NOT
 * happen: a client-supplied user_id must not be honoured, and credential
 * columns must never appear in a response.
 *
 * Serial: the token test feeds every authenticated request after it.
 */

const { test, expect } = require('@playwright/test');

const API = '/api/index.php/v1/livingword';

/** Set by the token test, consumed by everything after it. */
let apiToken = '';

/**
 * Authenticated request headers.
 *
 * @returns {object}
 */
function auth() {
    return { 'X-Joomla-Token': apiToken, Accept: 'application/vnd.api+json' };
}

test.describe.serial('Web Services API acceptance (package install) @api', () => {
    test('the webservices plugin is installed and enabled', async ({ page }) => {
        await page.goto(
            '/administrator/index.php?option=com_plugins&filter[search]=livingword&filter[folder]=webservices',
            // domcontentloaded, not networkidle: the Joomla admin keeps
            // background requests going, so networkidle can never settle and
            // the wait becomes a 30s timeout on a page that rendered instantly.
            // The assertions below wait for what they actually need.
            { waitUntil: 'domcontentloaded' },
        );

        const rows = page.locator('#pluginList tbody tr');

        expect(
            await rows.count(),
            'plg_webservices_livingword is not in the Plugins screen. If this site has no '
            + 'LivingWord at all, run `composer test:install` first — this suite asserts the '
            + 'state that harness produces.',
        ).toBeGreaterThan(0);

        await expect(
            rows.first().locator('.tbody-icon .icon-publish, a[data-bs-original-title*="Unpublish"], .icon-publish'),
            'plg_webservices_livingword exists but is disabled — a clean install must come up '
            + 'with the API reachable.',
        ).toHaveCount(1);
    });

    test('unauthenticated request is denied with 401, not 404', async ({ request }) => {
        const response = await request.get(`${API}/plans`);

        expect(
            response.status(),
            '404 means the API routes were never registered — the plugin did not install, did '
            + 'not enable, or never ran. That is indistinguishable from "no such API" to a '
            + 'client, which is why it is asserted separately. 401 is the only acceptable no.',
        ).not.toBe(404);

        expect(response.status()).toBe(401);
    });

    test('a token from the admin profile reaches the API and gets JSON:API', async ({ page, request }) => {
        // Obtain the token the way an administrator does — from their own
        // account via the header's "Edit Account" link. On a pristine account
        // the token plugin removes its fields entirely, because the seed is
        // only generated when the user is saved with the plugin active. A
        // fresh install's admin is in exactly that state, so a save may be
        // needed first.
        await page.goto('/administrator/index.php', { waitUntil: 'domcontentloaded' });

        const editAccount = page.locator('a[href*="task=user.edit"]', { hasText: 'Edit Account' }).first();
        await expect(editAccount, 'No "Edit Account" link in the admin header').toBeAttached();

        await page.goto(await editAccount.getAttribute('href'), { waitUntil: 'domcontentloaded' });

        if (!(await page.locator('#jform_joomlatoken_token').count())) {
            await page.click('.button-apply');
            await page.waitForLoadState('domcontentloaded');
        }

        const tokenField = page.locator('#jform_joomlatoken_token');
        const alerts = (await page.locator('joomla-alert, .alert').allInnerTexts())
            .join(' | ').replace(/\s+/g, ' ').slice(0, 300);

        await expect(
            tokenField,
            'No Joomla API Token field on the account form, even after a seed-generating save. '
            + `Landed on: ${page.url()} — messages: ${alerts || '(none)'}`,
        ).toBeAttached();

        apiToken = await tokenField.inputValue();
        expect(apiToken, 'The profile never produced a token value').not.toBe('');

        const response = await request.get(`${API}/plans`, { headers: auth() });

        expect(response.status()).toBe(200);

        const body = await response.json();
        expect(Array.isArray(body.data), 'Expected a JSON:API document with a data array').toBe(true);
    });

    test('every catalog resource answers', async ({ request }) => {
        for (const resource of ['plans', 'plandays', 'tools', 'links', 'groups']) {
            const response = await request.get(`${API}/${resource}`, { headers: auth() });

            expect(
                response.status(),
                `${resource} did not answer. A 404 here means this one resource's route is `
                + 'missing while the others registered — a typo in the RESOURCES map.',
            ).toBe(200);
        }
    });

    test('the catalog refuses writes', async ({ request }) => {
        const response = await request.post(`${API}/plans`, {
            headers: auth(),
            data: { data: { type: 'plans', attributes: { title: 'written over the API' } } },
        });

        expect(
            [404, 405].includes(response.status()),
            'The catalog is read-only by design, enforced by both the route map and '
            + `AbstractReadOnlyController. POST returned ${response.status()}; expected 404 or 405.`,
        ).toBe(true);
    });

    // --- Authorisation -----------------------------------------------------
    //
    // The tests that matter. Each asserts something that must NOT happen.

    test('user data is refused without a token', async ({ request }) => {
        for (const resource of ['progress', 'notes', 'settings']) {
            const response = await request.get(`${API}/${resource}`);

            expect(
                response.status(),
                `${resource} answered without authentication. User data is never public, `
                + 'whatever public_reads is set to — "readable without a token" and "scoped to '
                + 'the current user" cannot both be true.',
            ).toBe(401);
        }
    });

    test('settings never expose the credential columns', async ({ request }) => {
        const response = await request.get(`${API}/settings`, { headers: auth() });
        expect(response.status()).toBe(200);

        const raw = JSON.stringify(await response.json());

        for (const column of ['unsubscribe_token', 'action_token', 'accountability_partner_id']) {
            expect(
                raw,
                `${column} appeared in a settings response. The token columns are credentials: `
                + 'they cancel a subscription and mark readings complete from an emailed link '
                + 'with no further authentication, so anything that can read them can act as '
                + 'the user. They are excluded at the model, not the view, precisely so a view '
                + 'that renders whatever it is given cannot leak them.',
            ).not.toContain(column);
        }
    });

    test('a client-supplied user_id is not honoured', async ({ request }) => {
        // Post progress claiming to be another user. The write must land on the
        // authenticated user, because no code path reads user_id from a
        // request — so the foreign id should have no effect whatsoever.
        const foreignUserId = 999999;

        const created = await request.post(`${API}/progress`, {
            headers: auth(),
            data: {
                data: {
                    type: 'progress',
                    attributes: { plan_id: 1, day: 1, user_id: foreignUserId },
                },
            },
        });

        expect(
            [200, 201].includes(created.status()),
            `Expected the write to succeed for the authenticated user; got ${created.status()}.`,
        ).toBe(true);

        // Read back. Every row must belong to the caller, never to the id sent.
        const listed = await request.get(`${API}/progress`, { headers: auth() });
        expect(listed.status()).toBe(200);

        const body = await listed.json();
        const rows = Array.isArray(body.data) ? body.data : [];

        for (const row of rows) {
            expect(
                String(row.attributes?.user_id ?? ''),
                'A progress row came back belonging to the user_id supplied in the request '
                + 'body. The client must never be able to choose whose data it reads or '
                + 'writes; that id is supposed to be ignored entirely.',
            ).not.toBe(String(foreignUserId));
        }
    });

    test('settings rejects a write to a non-writable field', async ({ request }) => {
        const response = await request.patch(`${API}/settings`, {
            headers: auth(),
            data: {
                data: {
                    type: 'settings',
                    attributes: { unsubscribe_token: 'chosen-by-the-client', streak_best: 9999 },
                },
            },
        });

        expect(
            response.status(),
            'A PATCH containing only non-writable fields must be refused, not silently ignored. '
            + 'Accepting it would let a client choose its own unsubscribe token — and then act '
            + 'as the user from an email link — or claim a reading streak it never earned.',
        ).toBe(400);
    });
});
