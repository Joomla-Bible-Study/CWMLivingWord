/**
 * Reading progress, end to end, as a subscriber.
 *
 * The component's central promise: a reader opens today's reading, marks it
 * read, and that survives. It is also the most JavaScript-dependent thing the
 * site does — cwmprogress.toggle is an AJAX endpoint, the button state is
 * rewritten in the browser, and none of it is exercised by a page scan.
 *
 * Every assertion here reads state back after a reload rather than trusting
 * the optimistic DOM update. A handler that flips a class without persisting
 * looks identical to a working one until the page is loaded again, and that is
 * exactly the failure worth catching.
 *
 * Each test restores the state it found, so the suite is re-runnable against a
 * long-lived dev site rather than only a freshly reset one.
 *
 * @package  Livingword.Tests
 * @since    __DEPLOY_VERSION__
 */

const { test, expect } = require('@playwright/test');

const HOME = 'index.php?option=com_livingword&view=cwmhome';

const DAY_CONTAINER = '[data-livingword-progress]';
const DAY_BUTTON    = '[data-livingword-progress] .livingword-mark-read-btn';
const PASSAGE       = '[data-livingword-passage-toggle]';

/**
 * Click a progress control and wait for the server to answer.
 *
 * Waiting on the response rather than a DOM change is deliberate: the DOM
 * changes optimistically, so asserting on it straight after the click would
 * pass even when the request failed.
 *
 * @param   {object}  page     Playwright page
 * @param   {object}  locator  The control to click
 *
 * @returns {Promise<object>}  The parsed JSON body
 */
async function toggleAndWait(page, locator) {
    const [response] = await Promise.all([
        page.waitForResponse((r) => r.url().includes('cwmprogress.toggle')),
        locator.click(),
    ]);

    expect(response.status(), 'the progress endpoint must answer 200').toBe(200);

    return response.json();
}

/**
 * Whether the day is currently marked complete, read from the server-rendered
 * markup rather than from any class the browser may have applied.
 *
 * @param   {object}  page  Playwright page
 *
 * @returns {Promise<boolean>}
 */
async function dayIsComplete(page) {
    return (await page.locator(DAY_CONTAINER).getAttribute('data-completed')) === '1';
}

test.describe('Reading progress @member', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto(HOME);

        test.skip(
            !(await page.locator(DAY_CONTAINER).count()),
            'no reading is scheduled for today on this plan, so there is no progress control to drive'
        );
    });

    test("today's reading offers a progress control to a subscriber", async ({ page }) => {
        await expect(page.locator(DAY_BUTTON)).toBeVisible();

        // The control carries what the endpoint needs. A missing plan or day
        // here is why toggling would answer "Invalid plan or day".
        const container = page.locator(DAY_CONTAINER);

        expect(Number(await container.getAttribute('data-plan-id')), 'plan id').toBeGreaterThan(0);
        expect(Number(await container.getAttribute('data-day')), 'reading day').toBeGreaterThan(0);
    });

    test('marking the day read survives a reload', async ({ page }) => {
        const before = await dayIsComplete(page);

        const body = await toggleAndWait(page, page.locator(DAY_BUTTON));

        expect(body.success, 'the endpoint reports success').toBeTruthy();
        expect(body.data.completed, 'the answer flips the day').toBe(!before);

        await page.goto(HOME);
        expect(await dayIsComplete(page), 'the new state came back from the database').toBe(!before);

        // Put it back.
        await toggleAndWait(page, page.locator(DAY_BUTTON));
        await page.goto(HOME);
        expect(await dayIsComplete(page), 'restored to the state this test found').toBe(before);
    });

    test('marking the day read marks every passage of it', async ({ page }) => {
        const passages = page.locator(PASSAGE);
        const count    = await passages.count();

        test.skip(count === 0, "today's reading has no per-passage controls");

        const before = await dayIsComplete(page);

        if (before) {
            await toggleAndWait(page, page.locator(DAY_BUTTON));
            await page.goto(HOME);
        }

        await toggleAndWait(page, page.locator(DAY_BUTTON));
        await page.goto(HOME);

        // Read from the server-rendered attributes: the JS updates these in the
        // browser too, so only a reload proves the passages were written.
        const states = await page.locator(PASSAGE).evaluateAll(
            (nodes) => nodes.map((node) => node.dataset.completed)
        );

        expect(states.every((state) => state === '1'), 'every passage is complete').toBeTruthy();

        await toggleAndWait(page, page.locator(DAY_BUTTON));

        if (before) {
            await page.goto(HOME);
            await toggleAndWait(page, page.locator(DAY_BUTTON));
        }
    });

    test('a single passage toggles without completing the day', async ({ page }) => {
        const passages = page.locator(PASSAGE);
        const count    = await passages.count();

        test.skip(count < 2, 'needs a reading with more than one passage to tell the two apart');

        // Start from a day that is not complete, so completing one passage
        // cannot be confused with the day already being done.
        if (await dayIsComplete(page)) {
            await toggleAndWait(page, page.locator(DAY_BUTTON));
            await page.goto(HOME);
        }

        const body = await toggleAndWait(page, page.locator(PASSAGE).first());

        expect(body.data.passage_completed, 'the passage is complete').toBeTruthy();
        expect(body.data.completed, 'one passage of several does not complete the day').toBeFalsy();

        await page.goto(HOME);

        const states = await page.locator(PASSAGE).evaluateAll(
            (nodes) => nodes.map((node) => node.dataset.completed)
        );

        expect(states[0], 'the toggled passage persisted').toBe('1');
        expect(states.slice(1).some((state) => state === '0'), 'the others are untouched').toBeTruthy();

        await toggleAndWait(page, page.locator(PASSAGE).first());
    });

    test('the endpoint refuses a request without a session token', async ({ page }) => {
        // The button always sends the token, so only a direct request covers
        // this. CSRF on a state-changing GET is the whole reason the token is
        // there — a reader's progress should not be editable by any page that
        // can make their browser issue a request.
        const body = await page.evaluate(async () => {
            const url = new URL(window.location.origin);

            url.pathname = '/index.php';
            url.search = 'option=com_livingword&task=cwmprogress.toggle&format=json&plan_id=1&day=1&passage_count=1';

            const res = await fetch(url.toString(), { credentials: 'same-origin' });

            return { status: res.status, text: await res.text() };
        });

        expect(body.text, 'an untokened toggle is refused').toContain('Invalid session token');
    });
});
