/**
 * The accountability-partner digest, from pairing to delivered message.
 *
 * The last of the plugin's three routines, and the one with a real precondition
 * rather than a schedule: it mails nobody unless two readers have chosen *each
 * other* and the partner shares their progress. Three conditions, each of which
 * silently drops a reader from the run — mutual pairing, email opt-in, and the
 * partner's share_progress — so "no mail arrived" has three innocent
 * explanations and one guilty one, and only a test that sets all three up can
 * tell them apart.
 *
 * That is also why one seeded account was not enough: an account cannot pair
 * with itself. Global setup seeds and subscribes a second reader.
 *
 * Requires a mail catcher; skips cleanly without one.
 *
 * @package  Livingword.Tests
 * @since    __DEPLOY_VERSION__
 */

const { test, expect } = require('@playwright/test');
const { catcherAvailable, clearInbox, waitForMail, messageBody } = require('../helpers/mailpit');
const { ensureTask, runTask } = require('../helpers/scheduler');

const ROUTINE = 'livingword.partner_digest';
const TITLE   = 'E2E Partner Progress Digest';

const SETTINGS = 'index.php?option=com_livingword&view=cwmsettings';

const READERS = [
    { state: 'tests/e2e/.auth/member-j6.json', label: 'member' },
    { state: 'tests/e2e/.auth/partner-j6.json', label: 'partner' },
];

/**
 * Point a reader at the only other reader offered, sharing progress.
 *
 * Returns the partner's user id, so the pairing can be asserted as mutual
 * rather than merely set.
 *
 * @param   {object}  browser  Playwright browser
 * @param   {string}  baseURL  Site root
 * @param   {string}  state    Saved session for the reader doing the choosing
 *
 * @returns {Promise<string|null>}  The chosen partner's id, or null if none offered
 */
async function pairWithTheOtherReader(browser, baseURL, state) {
    const ctx = await browser.newContext({ ignoreHTTPSErrors: true, storageState: state, baseURL });

    try {
        const page = await ctx.newPage();

        await page.goto(SETTINGS);

        if (!(await page.locator('#accountability_partner_id').count())) {
            return null;
        }

        const options = await page.locator('#accountability_partner_id option').evaluateAll(
            (nodes) => nodes.map((node) => node.value).filter(Boolean)
        );

        if (!options.length) {
            return null;
        }

        const [partnerId] = options;

        await page.locator('#accountability_partner_id').selectOption(partnerId);
        await page.locator('#share_progress').setChecked(true);
        await page.locator('#email').setChecked(true);
        await page.locator('#settingsForm button[type="submit"]:not([name="action"])').click();
        await page.waitForLoadState('domcontentloaded');

        // Read back: a pairing that did not persist is the difference between
        // this routine mailing two people and mailing nobody.
        await page.goto(SETTINGS);
        await expect(page.locator('#accountability_partner_id')).toHaveValue(partnerId);
        await expect(page.locator('#share_progress')).toBeChecked();

        return partnerId;
    } finally {
        await ctx.close();
    }
}

test.describe('Accountability partner digest @admin', () => {
    test.describe.configure({ mode: 'serial' });

    test.beforeEach(async ({ request }) => {
        test.skip(
            !(await catcherAvailable(request)),
            'no mail catcher listening — refusing to run a routine that would email real subscribers'
        );
    });

    test('two readers can choose each other', async ({ browser, baseURL }) => {
        const chosen = [];

        for (const reader of READERS) {
            const partnerId = await pairWithTheOtherReader(browser, baseURL, reader.state);

            test.skip(partnerId === null, `no partner is offered to the ${reader.label}`);
            chosen.push(partnerId);
        }

        // Each picked somebody, and not the same somebody — which is what makes
        // the pairing mutual rather than two readers pointing at one person.
        expect(chosen[0], 'the two readers chose different people').not.toBe(chosen[1]);
    });

    test('the routine can be scheduled', async ({ page }) => {
        expect(
            await ensureTask(page, ROUTINE, TITLE),
            'a task for the partner digest routine exists'
        ).toBeTruthy();
    });

    test('running it delivers a digest to a paired reader', async ({ page, request }) => {
        await clearInbox(request);
        await runTask(page, TITLE);

        const messages = await waitForMail(request);

        expect(messages.length, 'the routine delivered a message').toBeGreaterThan(0);

        // Mutual pairing means both readers qualify, so both should hear about
        // it. One message would mean the routine stopped after the first half
        // of the pair.
        expect(messages.length, 'both halves of the pairing were mailed').toBeGreaterThan(1);
    });

    test('the digest tells a reader about their partner', async ({ request }) => {
        const messages = await waitForMail(request, 2000);

        test.skip(!messages.length, 'no message captured by the previous test');

        const html = await messageBody(request, messages[0].ID);

        // The point of this email is somebody else's progress. A digest that
        // named nobody would be indistinguishable from the weekly one.
        expect(html, 'it names the partner').toMatch(/LivingWord E2E Member/);
        expect(html, 'it carries an unsubscribe link').toMatch(/cwmunsubscribe\.unsubscribe/);
    });
});
