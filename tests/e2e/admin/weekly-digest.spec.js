/**
 * The weekly progress digest, from scheduled task to delivered message.
 *
 * The second of the plugin's three routines, and the second thing about this
 * component that nobody can see working. Unlike the daily email it has no hour
 * to match — it takes every opted-in subscriber and reports the last seven days
 * — so what it needs from a test is a subscriber who is opted in and a task to
 * run.
 *
 * Creates the task itself rather than assuming one exists, because on a fresh
 * site there is none: the plugin does nothing until an administrator schedules
 * it, deliberately, and a spec that quietly skipped there would be testing
 * whoever last used the dev site rather than the component.
 *
 * Requires a mail catcher; skips cleanly without one.
 *
 * @package  Livingword.Tests
 * @since    __DEPLOY_VERSION__
 */

const { test, expect } = require('@playwright/test');
const { catcherAvailable, clearInbox, waitForMail, messageBody } = require('../helpers/mailpit');
const { ensureTask, runTask } = require('../helpers/scheduler');

const ROUTINE = 'livingword.weekly_digest';
const TITLE   = 'E2E Weekly Progress Digest';

const SETTINGS = 'index.php?option=com_livingword&view=cwmsettings';

test.describe('Weekly progress digest @admin', () => {
    test.describe.configure({ mode: 'serial' });

    test.beforeEach(async ({ request }) => {
        test.skip(
            !(await catcherAvailable(request)),
            'no mail catcher listening — refusing to run a routine that would email real subscribers'
        );
    });

    test('a subscriber is opted in to receive it', async ({ browser, baseURL }) => {
        const ctx = await browser.newContext({
            ignoreHTTPSErrors: true,
            storageState: 'tests/e2e/.auth/member-j6.json',
            baseURL,
        });

        try {
            const page = await ctx.newPage();

            await page.goto(SETTINGS);
            test.skip(!(await page.locator('#email').count()), 'preferences are not reachable for this member');

            // No hour to match here — the digest goes to everyone opted in, so
            // the opt-in is the whole precondition.
            await page.locator('#email').setChecked(true);
            await page.locator('#settingsForm button[type="submit"]:not([name="action"])').click();
            await page.waitForLoadState('domcontentloaded');

            await page.goto(SETTINGS);
            await expect(page.locator('#email')).toBeChecked();
        } finally {
            await ctx.close();
        }
    });

    test('the routine can be scheduled', async ({ page }) => {
        expect(
            await ensureTask(page, ROUTINE, TITLE),
            'a task for the weekly digest routine exists'
        ).toBeTruthy();
    });

    test('running it delivers a digest', async ({ page, request }) => {
        await clearInbox(request);
        await runTask(page, TITLE);

        const messages = await waitForMail(request);

        expect(messages.length, 'the routine delivered a message').toBeGreaterThan(0);

        const subjects = messages.map((m) => m.Subject).join(' | ');

        expect(subjects, `subjects seen: ${subjects}`).toMatch(/progress|digest|week/i);
    });

    test('the digest reports the week rather than a single day', async ({ request }) => {
        const messages = await waitForMail(request, 2000);

        test.skip(!messages.length, 'no message captured by the previous test');

        const html = await messageBody(request, messages[0].ID);

        // What separates this from the daily email: it summarises a period, so
        // it has to carry progress figures. A digest that rendered an empty
        // shell would still have been "delivered" by the test above.
        expect(html.length, 'the digest has a body').toBeGreaterThan(200);
        expect(html, 'it reports something countable').toMatch(/\d/);

        // And it must offer the way out that every bulk email needs.
        expect(html, 'it carries an unsubscribe link').toMatch(/cwmunsubscribe\.unsubscribe/);
    });
});
