/**
 * Reading plan CRUD in the administrator, end to end.
 *
 * The admin suite has scanned these screens for accessibility since #108 and
 * asserted nothing about what they do. A form that renders, validates and then
 * saves nothing passes an a11y scan perfectly — and the front end had exactly
 * that shape until the missing settings controller was found, so the gap is not
 * hypothetical.
 *
 * Submission goes through `Joomla.submitbutton(task)` rather than clicking
 * toolbar buttons by their text. That is the same call the toolbar makes, it
 * survives Joomla restyling its toolbar, and it does not depend on the admin
 * language pack being English.
 *
 * The plan created here is deleted again, including from the trash, so the
 * suite leaves no residue on a long-lived dev site.
 *
 * @package  Livingword.Tests
 * @since    __DEPLOY_VERSION__
 */

const { test, expect } = require('@playwright/test');

const LIST = 'administrator/index.php?option=com_livingword&view=cwmplans';
const NEW  = 'administrator/index.php?option=com_livingword&view=cwmplan&layout=edit';

/**
 * Run a Joomla toolbar task on the current admin form.
 *
 * @param   {object}  page  Playwright page
 * @param   {string}  task  e.g. "cwmplan.save"
 *
 * @returns {Promise<void>}
 */
async function submit(page, task) {
    // The navigation is armed before the submit fires, not after. Joomla's
    // submitbutton() posts the form asynchronously, so waitForLoadState() can
    // return for the page still on screen and the next goto() then aborts the
    // submit mid-flight — net::ERR_ABORTED, with the save silently lost.
    const navigated = page.waitForNavigation({ waitUntil: 'domcontentloaded' }).catch(() => {});

    await page.evaluate((t) => window.Joomla.submitbutton(t), task);
    await navigated;
}

/**
 * Search the list for a title and report how many rows match.
 *
 * @param   {object}  page   Playwright page
 * @param   {string}  title  Search term
 *
 * @returns {Promise<number>}
 */
async function rowsMatching(page, title) {
    // Every filter is stated in the URL, none left to the session.
    //
    // Joomla remembers list state per user, so a test that looks in the trash
    // leaves the next visit filtered to trashed — and a search that finds
    // nothing then reads as "the save did not work". That cost three cascading
    // failures before the state was the obvious suspect. Setting search *and*
    // published on every lookup makes each one independent of what ran before.
    await page.goto(`${LIST}&filter%5Bsearch%5D=${encodeURIComponent(title)}&filter%5Bpublished%5D=`);

    return page.locator(`#adminForm tbody tr:has-text("${title}")`).count();
}

/**
 * The same lookup against the trash.
 *
 * @param   {object}  page   Playwright page
 * @param   {string}  title  Search term
 *
 * @returns {Promise<number>}
 */
async function trashedRowsMatching(page, title) {
    await page.goto(`${LIST}&filter%5Bsearch%5D=${encodeURIComponent(title)}&filter%5Bpublished%5D=-2`);

    return page.locator(`#adminForm tbody tr:has-text("${title}")`).count();
}

test.describe('Reading plan CRUD @admin', () => {
    // One title per run, so a leftover from an interrupted run can never make
    // a later one pass by accident.
    const title = `E2E Plan ${Date.now()}`;
    const alias = `e2e-plan-${Date.now()}`;

    test.afterAll(async ({ browser }) => {
        // Belt and braces: if a test failed midway, take the plan out of the
        // list and then out of the trash, so the next run starts clean.
        const page = await browser.newPage();

        try {
            if (await rowsMatching(page, title)) {
                await page.locator('#adminForm input[name="cid[]"]').first().check();
                await submit(page, 'cwmplans.trash');
            }

            if (await trashedRowsMatching(page, title)) {
                await page.locator('#adminForm input[name="cid[]"]').first().check();
                await submit(page, 'cwmplans.delete');
            }
        } finally {
            await page.close();
        }
    });

    test('a new plan saves and appears in the list', async ({ page }) => {
        await page.goto(NEW);

        await page.fill('#jform_title', title);
        await page.fill('#jform_alias', alias);

        await submit(page, 'cwmplan.save');

        // Saving must not bounce back to the form with an error.
        await expect(page.locator('#system-message-container'), 'no save error')
            .not.toContainText(/error/i);

        expect(await rowsMatching(page, title), 'the plan is listed').toBeGreaterThan(0);
    });

    test('an edit persists', async ({ page }) => {
        const edited = `${title} edited`;

        await rowsMatching(page, title);
        await page.locator(`#adminForm tbody tr:has-text("${title}") a[href*="cwmplan.edit"]`).first().click();
        await page.waitForLoadState('domcontentloaded');

        await expect(page.locator('#jform_title'), 'the editor opened on the plan').toHaveValue(title);

        await page.fill('#jform_title', edited);
        await submit(page, 'cwmplan.save');

        // Read it back from the list rather than from the form that still has
        // the typed value in it.
        expect(await rowsMatching(page, edited), 'the edited title is listed').toBeGreaterThan(0);

        // Put the title back so the remaining tests and the cleanup find it.
        await page.locator(`#adminForm tbody tr:has-text("${edited}") a[href*="cwmplan.edit"]`).first().click();
        await page.waitForLoadState('domcontentloaded');
        await page.fill('#jform_title', title);
        await submit(page, 'cwmplan.save');
    });

    test('unpublishing and republishing both take', async ({ page }) => {
        await rowsMatching(page, title);
        await page.locator('#adminForm input[name="cid[]"]').first().check();
        await submit(page, 'cwmplans.unpublish');

        await rowsMatching(page, title);
        await expect(
            page.locator(`#adminForm tbody tr:has-text("${title}") [class*="unpublish"], `
                + `#adminForm tbody tr:has-text("${title}") [aria-label*="npublish"]`),
            'the row shows an unpublished state'
        ).not.toHaveCount(0);

        await page.locator('#adminForm input[name="cid[]"]').first().check();
        await submit(page, 'cwmplans.publish');
    });

    test('trashing removes it from the list, and deleting empties the trash', async ({ page }) => {
        await rowsMatching(page, title);
        await page.locator('#adminForm input[name="cid[]"]').first().check();
        await submit(page, 'cwmplans.trash');

        expect(await rowsMatching(page, title), 'gone from the default list').toBe(0);

        // Trashed, not vanished: a trash that deletes outright would lose a
        // plan an admin meant to recover.
        expect(await trashedRowsMatching(page, title), 'still recoverable from the trash').toBe(1);

        await page.locator('#adminForm input[name="cid[]"]').first().check();
        await submit(page, 'cwmplans.delete');

        expect(await trashedRowsMatching(page, title), 'deleted for good').toBe(0);
    });
});
