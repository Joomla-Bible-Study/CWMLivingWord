/**
 * Create, edit and remove the component's other admin entities.
 *
 * Reading plans got this treatment in #130; these four are the rest of what an
 * administrator actually manages — resource links, study tools, reading groups
 * and the days inside a plan. All four have been scanned for accessibility
 * since #108 and asserted nothing about what they do.
 *
 * One table drives all of them, because the lifecycle is genuinely the same and
 * writing it four times would mean four places to fix when Joomla moves a
 * toolbar. What differs per entity is only its views, its required fields, and
 * whichever select has to be chosen before the form will save.
 *
 * Everything created here is deleted from the trash afterwards, so a long-lived
 * dev site gains no residue.
 *
 * @package  Livingword.Tests
 * @since    __DEPLOY_VERSION__
 */

const { test, expect } = require('@playwright/test');

const ADMIN = 'administrator/index.php?option=com_livingword';

/**
 * The entities, and the least a form needs before it will save.
 *
 * `selects` are fields whose value has to come from the page rather than the
 * test: a plan id is a real row, and hardcoding one would break the moment the
 * seeded data changed.
 */
const ENTITIES = [
    {
        label: 'resource link',
        list: 'cwmlinks',
        item: 'cwmlink',
        fields: { '#jform_name': null, '#jform_url': 'https://example.com/e2e-link' },
        selects: [],
    },
    {
        label: 'study tool',
        list: 'cwmtools',
        item: 'cwmtool',
        fields: { '#jform_name': null, '#jform_url': 'https://example.com/e2e-tool' },
        selects: [],
    },
    {
        label: 'reading group',
        list: 'cwmgroups',
        item: 'cwmgroup',
        fields: { '#jform_name': null },
        selects: ['#jform_plan_id'],
    },
    {
        label: 'plan day',
        list: 'cwmplandetails',
        item: 'cwmplandetail',
        fields: { '#jform_reading': null },
        selects: ['#jform_plan_id'],
        // A day inside a plan has no published state — no column, and the
        // toolbar offers delete alone. Asserting a trash it does not have
        // would be inventing a requirement.
        trashable: false,
    },
];

/**
 * Run a Joomla toolbar task, waiting for the navigation it causes.
 *
 * Armed before the submit fires: submitbutton() posts asynchronously, so a
 * later goto() would abort it mid-flight and lose the save (#130).
 *
 * @param   {object}  page  Playwright page
 * @param   {string}  task  e.g. "cwmlink.save"
 *
 * @returns {Promise<void>}
 */
async function submit(page, task) {
    const navigated = page.waitForNavigation({ waitUntil: 'domcontentloaded' }).catch(() => {});

    await page.evaluate((t) => window.Joomla.submitbutton(t), task);
    await navigated;
}

/**
 * Search a list, stating every filter in the URL.
 *
 * Joomla remembers list state per user, so a lookup that inherits it can be
 * searching the trash without saying so — which reads exactly like a failed
 * save (#130).
 *
 * @param   {object}  page       Playwright page
 * @param   {string}  view       List view name
 * @param   {string}  term       Search term
 * @param   {string}  published  Published filter, '' for live rows, '-2' for trashed
 *
 * @returns {Promise<number>}
 */
async function rowsMatching(page, view, term, published = '') {
    await page.goto(
        `${ADMIN}&view=${view}&filter%5Bsearch%5D=${encodeURIComponent(term)}&filter%5Bpublished%5D=${published}`
    );

    return page.locator(`#adminForm tbody tr:has-text("${term}")`).count();
}

for (const entity of ENTITIES) {
    test.describe(`${entity.label} CRUD @admin`, () => {
        test.describe.configure({ mode: 'serial' });

        const name = `E2E ${entity.label} ${Date.now()}`;

        test.afterAll(async ({ browser }) => {
            const page = await browser.newPage();

            try {
                if (await rowsMatching(page, entity.list, name)) {
                    await page.locator('#adminForm input[name="cid[]"]').first().check();
                    await submit(page, `${entity.list}.trash`);
                }

                if (await rowsMatching(page, entity.list, name, '-2')) {
                    await page.locator('#adminForm input[name="cid[]"]').first().check();
                    await submit(page, `${entity.list}.delete`);
                }
            } finally {
                await page.close();
            }
        });

        test('saves and appears in the list', async ({ page }) => {
            await page.goto(`${ADMIN}&view=${entity.item}&layout=edit`);

            for (const [selector, value] of Object.entries(entity.fields)) {
                await page.fill(selector, value ?? name);
            }

            for (const selector of entity.selects) {
                const options = await page.locator(`${selector} option`).evaluateAll(
                    (nodes) => nodes.map((node) => node.value).filter(Boolean)
                );

                test.skip(!options.length, `${selector} offers nothing to choose`);
                await page.locator(selector).selectOption(options[0]);
            }

            await submit(page, `${entity.item}.save`);

            await expect(page.locator('#system-message-container'), 'no save error')
                .not.toContainText(/error|invalid/i);

            expect(await rowsMatching(page, entity.list, name), 'listed after saving').toBeGreaterThan(0);
        });

        test('an edit persists', async ({ page }) => {
            const [field] = Object.keys(entity.fields);
            const edited  = `${name} edited`;

            await rowsMatching(page, entity.list, name);
            await page.locator(`#adminForm tbody tr a[href*="${entity.item}.edit"]`).first().click();
            await page.waitForLoadState('domcontentloaded');

            await page.fill(field, edited);
            await submit(page, `${entity.item}.save`);

            // From the list, not the form that still holds what was typed.
            expect(await rowsMatching(page, entity.list, edited), 'the edit is listed').toBeGreaterThan(0);

            await page.locator(`#adminForm tbody tr a[href*="${entity.item}.edit"]`).first().click();
            await page.waitForLoadState('domcontentloaded');
            await page.fill(field, name);
            await submit(page, `${entity.item}.save`);
        });

        const removalTitle = entity.trashable === false
            ? 'deleting removes it'
            : 'trashing keeps it recoverable until it is deleted';

        test(removalTitle, async ({ page }) => {
            await rowsMatching(page, entity.list, name);
            await page.locator('#adminForm input[name="cid[]"]').first().check();

            if (entity.trashable === false) {
                await submit(page, `${entity.list}.delete`);

                expect(await rowsMatching(page, entity.list, name), 'gone from the list').toBe(0);

                return;
            }

            await submit(page, `${entity.list}.trash`);

            expect(await rowsMatching(page, entity.list, name), 'gone from the live list').toBe(0);
            expect(await rowsMatching(page, entity.list, name, '-2'), 'still in the trash').toBeGreaterThan(0);

            await page.locator('#adminForm input[name="cid[]"]').first().check();
            await submit(page, `${entity.list}.delete`);

            expect(await rowsMatching(page, entity.list, name, '-2'), 'deleted for good').toBe(0);
        });
    });
}
