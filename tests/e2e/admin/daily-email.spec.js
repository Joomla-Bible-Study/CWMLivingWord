/**
 * The daily reading email, from scheduled task to delivered message.
 *
 * This is the component's one wholly invisible feature: nothing on any page
 * tells a maintainer whether the mail went out, and the failure mode is
 * silence. #114 shipped a release whose webservices plugin installed disabled;
 * the task plugin's own install script exists because Joomla registers plugins
 * disabled and an admin "can configure the email settings, create a scheduled
 * task, and still get silence".
 *
 * Requires a mail catcher — Mailpit — on the site under test, so no real
 * message can escape. Everything here skips cleanly when one is not listening,
 * because the alternative to a catcher is emailing real subscribers from a
 * test, which must never happen by accident.
 *
 * Setup, teardown:
 *   brew install mailpit && brew services start mailpit
 *   point the site's SMTP at 127.0.0.1:1025, auth off, encryption none
 *
 * @package  Livingword.Tests
 * @since    __DEPLOY_VERSION__
 */

const { test, expect } = require('@playwright/test');
const { catcherAvailable, clearInbox, waitForMail, messageBody } = require('../helpers/mailpit');
const { ensureTask, runTask } = require('../helpers/scheduler');

const ROUTINE = 'livingword.email_notifications';
const TITLE   = 'E2E Daily Reading Emails';

const SETTINGS = 'index.php?option=com_livingword&view=cwmsettings';

test.describe('Daily reading email @admin', () => {
    test.describe.configure({ mode: 'serial' });

    test.beforeEach(async ({ request }) => {
        test.skip(
            !(await catcherAvailable(request)),
            'no mail catcher listening — refusing to run a routine that would email real subscribers'
        );
    });

    test('a subscriber is opted in for the hour the routine will run', async ({ browser, baseURL }) => {
        // The routine mails only subscribers whose preferred hour matches the
        // server's current hour, so the reader has to be set up for *now* or
        // there is nothing to observe. Done through the preferences form, which
        // is also the only way a real reader could do it.
        const ctx = await browser.newContext({
            ignoreHTTPSErrors: true,
            storageState: 'tests/e2e/.auth/member-j6.json',
            baseURL,
        });

        try {
            const page = await ctx.newPage();

            await page.goto(SETTINGS);
            test.skip(!(await page.locator('#email_hour').count()), 'preferences are not reachable for this member');

            // The reader's timezone is pinned to UTC and the hour set in UTC,
            // so this is deterministic wherever the runner and the site happen
            // to be. The routine now reads each subscriber's own timezone —
            // before that it compared against date('G'), which Joomla pins to
            // UTC, so anyone who had chosen a zone was mailed at the wrong
            // hour and the column was read by nothing.
            const hour = String(new Date().getUTCHours());
            const zones = await page.locator('#timezone option').evaluateAll((o) => o.map((x) => x.value));

            await page.locator('#email').setChecked(true);

            if (zones.includes('UTC')) {
                await page.locator('#timezone').selectOption('UTC');
            }

            await page.locator('#email_hour').selectOption(hour);
            await page.locator('#settingsForm button[type="submit"]:not([name="action"])').click();
            await page.waitForLoadState('domcontentloaded');

            await page.goto(SETTINGS);
            await expect(page.locator('#email'), 'opted in').toBeChecked();
            await expect(page.locator('#email_hour'), 'set to the current hour').toHaveValue(hour);

        } finally {
            await ctx.close();
        }
    });

    test('running the task delivers a reading email', async ({ page, request }) => {
        // Created rather than assumed: the plugin does nothing until a task is
        // scheduled, so on a fresh site there is none to find.
        expect(await ensureTask(page, ROUTINE, TITLE), 'a task for the routine exists').toBeTruthy();

        await clearInbox(request);
        await runTask(page, TITLE);

        const messages = await waitForMail(request);

        expect(messages.length, 'the routine delivered a message').toBeGreaterThan(0);

        const subjects = messages.map((m) => m.Subject).join(' | ');

        expect(subjects, `subjects seen: ${subjects}`).toMatch(/reading|livingword/i);
    });

    test('the delivered email carries working one-click links', async ({ request, browser, baseURL }) => {
        const messages = await waitForMail(request, 2000);

        test.skip(!messages.length, 'no message captured by the previous test');

        const html = await messageBody(request, messages[0].ID);

        // The two links every reading email carries. They are the only place
        // these tokens are ever exposed, which is why the landing pages could
        // not be covered with valid tokens before now.
        //
        // Matched on the controller task, not on view=: a real reading email
        // links task=cwmcomplete.complete, while the a11y scans visit
        // view=cwmcomplete. Those are different entry points, and only the one
        // in the mail is the one a reader ever clicks.
        const complete = html.match(/https?:\/\/[^"'\s>]*cwmcomplete\.complete[^"'\s>]*/);
        const unsubscribe = html.match(/https?:\/\/[^"'\s>]*cwmunsubscribe\.unsubscribe[^"'\s>]*/);

        expect(complete, 'the email offers a one-click completion link').not.toBeNull();
        expect(unsubscribe, 'the email offers an unsubscribe link').not.toBeNull();

        // Followed as a stranger would: from a browser carrying no session.
        const guest = await browser.newContext({ ignoreHTTPSErrors: true, baseURL });

        try {
            const guestPage = await guest.newPage();
            const target = complete[0].replace(/&amp;/g, '&');

            await guestPage.goto(target);

            // A tokened link has to work without a login — that is its whole
            // point — and must not answer with an error page.
            await expect(guestPage.locator('body')).not.toContainText(/fatal|exception|stack trace/i);
            await expect(
                guestPage.locator('.com-livingword-complete, .alert, #system-message-container'),
                'the completion landing says something to the reader'
            ).not.toHaveCount(0);

            // The unsubscribe link is deliberately not followed: it would opt
            // the reader out, and the next run would then have nobody to mail.
            expect(unsubscribe[0], 'the unsubscribe link carries a token').toMatch(/token=[a-f0-9]{8,}/i);
        } finally {
            await guest.close();
        }
    });
});
