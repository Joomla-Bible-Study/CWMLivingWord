/**
 * Reading journal autosave, end to end, as a subscriber.
 *
 * There is no save button. The textarea debounces for 800ms and then fires a
 * request, so the whole feature is invisible to a page scan and to anything
 * that does not wait: assert too early and a working autosave looks broken,
 * assert on the DOM alone and a broken one looks fine.
 *
 * So every test here waits for the request the browser actually made, and the
 * persistence tests read the note back from a fresh render rather than from
 * the textarea they typed into.
 *
 * Each test leaves the journal as it found it.
 *
 * @package  Livingword.Tests
 * @since    __DEPLOY_VERSION__
 */

const { test, expect } = require('@playwright/test');

const HOME = 'index.php?option=com_livingword&view=cwmhome';

const JOURNAL  = '[data-livingword-notes]';
const TEXTAREA = '[data-livingword-notes] .livingword-notes-textarea';
const STATUS   = '[data-livingword-notes] .livingword-notes-status';

// The client debounces for 800ms; allow for the round trip after it.
const AUTOSAVE_TIMEOUT = 10000;

/**
 * Type into the journal and wait for the save the debounce triggers.
 *
 * @param   {object}  page  Playwright page
 * @param   {string}  text  What to leave in the textarea
 *
 * @returns {Promise<object>}  The parsed JSON body of the save
 */
async function typeAndWaitForSave(page, text) {
    const textarea = page.locator(TEXTAREA);

    await textarea.fill(text);

    const response = await page.waitForResponse(
        (r) => r.url().includes('cwmnotes.save'),
        { timeout: AUTOSAVE_TIMEOUT }
    );

    expect(response.status(), 'the notes endpoint must answer 200').toBe(200);

    return response.json();
}

test.describe('Reading journal @member', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto(HOME);

        test.skip(
            !(await page.locator(JOURNAL).count()),
            'no reading is scheduled for today on this plan, so there is no journal to write in'
        );
    });

    test('the journal renders with what the endpoint needs', async ({ page }) => {
        await expect(page.locator(TEXTAREA)).toBeVisible();

        const journal = page.locator(JOURNAL);

        expect(Number(await journal.getAttribute('data-plan-id')), 'plan id').toBeGreaterThan(0);
        expect(Number(await journal.getAttribute('data-day')), 'reading day').toBeGreaterThan(0);
        expect(await journal.getAttribute('data-notes-url'), 'save endpoint').toContain('cwmnotes.save');
    });

    test('typing saves the note without a save button', async ({ page }) => {
        const original = await page.locator(TEXTAREA).inputValue();
        const written  = `e2e autosave ${Date.now()}`;

        const body = await typeAndWaitForSave(page, written);

        expect(body.success, 'the endpoint reports success').toBeTruthy();

        await typeAndWaitForSave(page, original);
    });

    test('a saved note survives a reload', async ({ page }) => {
        const original = await page.locator(TEXTAREA).inputValue();
        const written  = `e2e persistence ${Date.now()}`;

        await typeAndWaitForSave(page, written);

        // From a fresh render: the textarea still holding the text proves
        // nothing, since that is where it was typed.
        await page.goto(HOME);
        await expect(page.locator(TEXTAREA)).toHaveValue(written);

        await typeAndWaitForSave(page, original);
        await page.goto(HOME);
        await expect(page.locator(TEXTAREA)).toHaveValue(original);
    });

    test('clearing a note persists as empty rather than being ignored', async ({ page }) => {
        const original = await page.locator(TEXTAREA).inputValue();

        await typeAndWaitForSave(page, `e2e to be cleared ${Date.now()}`);
        await page.goto(HOME);

        // An endpoint that treats empty as "nothing to do" would leave the old
        // text in place, and a reader who deleted their note would find it back.
        await typeAndWaitForSave(page, '');
        await page.goto(HOME);
        await expect(page.locator(TEXTAREA)).toHaveValue('');

        if (original !== '') {
            await typeAndWaitForSave(page, original);
        }
    });

    test('the save status is readable, not a raw language key', async ({ page }) => {
        const original = await page.locator(TEXTAREA).inputValue();

        await typeAndWaitForSave(page, `e2e status ${Date.now()}`);

        const status = page.locator(STATUS);

        await expect(status).not.toBeEmpty();

        // The client asks Joomla.Text for these strings. Joomla answers with
        // the key itself when the key was never pushed to the page with
        // Text::script(), and because a key is a non-empty string the client's
        // `|| 'Saved'` fallback never fires — so the reader is shown
        // COM_LIVINGWORD_NOTES_SAVED verbatim.
        await expect(status, 'the status shows a translated string').not.toContainText(/^COM_LIVINGWORD_/);

        await typeAndWaitForSave(page, original);
    });

    test('the endpoint refuses a save without a session token', async ({ page }) => {
        const body = await page.evaluate(async () => {
            const url = new URL(window.location.origin);

            url.pathname = '/index.php';
            url.search = 'option=com_livingword&task=cwmnotes.save&format=json'
                + '&plan_id=1&day=1&note_text=untokened';

            const res = await fetch(url.toString(), { credentials: 'same-origin' });

            return res.text();
        });

        expect(body, 'an untokened save is refused').toContain('Invalid session token');
    });
});
