/**
 * Front-end preferences, end to end, as a subscriber.
 *
 * This suite exists because of what it found. `site/tmpl/cwmsettings/default.php`
 * has posted to `task=cwmsettings.save` since the Joomla 3 → 5 migration, and
 * no CwmsettingsController was ever written, so every save answered
 * "Invalid controller class: cwmsettings" with a 404. Nobody could change their
 * plan, translation, email preferences, timezone or accountability partner from
 * the site, in any release of the Joomla 5 line.
 *
 * Nothing caught it because nothing posted the form. The a11y scans visit
 * cwmsettings as a guest, so they render the login prompt and never reach it.
 *
 * The round trip is therefore the point: fill, submit, reload, and read the
 * values back out of a fresh render. Asserting on the success message alone
 * would pass against a controller that redirects without writing anything.
 *
 * @package  Livingword.Tests
 * @since    __DEPLOY_VERSION__
 */

const { test, expect } = require('@playwright/test');

const SETTINGS = 'index.php?option=com_livingword&view=cwmsettings';

/**
 * The value of an option that is not currently selected, so a save has
 * something to prove. Returns null when the control offers no alternative.
 *
 * @param   {object}  select  Playwright locator for a <select>
 *
 * @returns {Promise<string|null>}
 */
async function anotherOption(select) {
    const current = await select.inputValue();
    const values = await select.locator('option').evaluateAll(
        (options) => options.map((option) => option.value).filter((value) => value !== '')
    );

    return values.find((value) => value !== current) ?? null;
}

/**
 * Whether cwmsettings actually renders its form.
 *
 * It does not, for a member without a plan: cwmsettings, cwmplanview and
 * cwmgroups all fall back to cwmhome's onboarding picker, so the URL answers
 * 200 with LivingWord markup that contains no form at all. Global setup
 * subscribes the member for exactly this reason, and this guard covers the
 * case where that did not take — skipping with the reason beats failing as
 * though the preferences code were broken.
 *
 * @param   {object}  page  Playwright page
 *
 * @returns {Promise<boolean>}
 */
async function settingsViewReachable(page) {
    await page.goto(SETTINGS);

    return page.locator('#settingsForm').count().then((n) => n > 0);
}

test.describe('Front-end preferences @member', () => {
    test.beforeEach(async ({ page }) => {
        test.skip(
            !(await settingsViewReachable(page)),
            'cwmsettings does not route to itself on this site — the view renders the default page instead'
        );
    });

    test('the preferences form renders for a logged-in member', async ({ page }) => {
        await page.goto(SETTINGS);

        await expect(page.locator('#settingsForm')).toBeVisible();
        await expect(page.locator('#bible_version')).toBeVisible();
    });

    test('saving reaches a controller rather than a 404', async ({ page }) => {
        await page.goto(SETTINGS);

        const response = await Promise.all([
            page.waitForResponse((r) => r.request().method() === 'POST'),
            page.locator('#settingsForm button[type="submit"]:not([name="action"])').click(),
        ]).then(([r]) => r);

        // The bug this suite was written for: com_livingword answered every
        // save with 404 "Invalid controller class: cwmsettings".
        expect(response.status(), 'POST to cwmsettings.save must not 404').not.toBe(404);
        await expect(page.locator('body')).not.toContainText('Invalid controller class');
    });

    test('a changed translation survives a reload', async ({ page }) => {
        await page.goto(SETTINGS);

        const select = page.locator('#bible_version');
        const target = await anotherOption(select);

        test.skip(target === null, 'only one translation is configured, so nothing can change');

        await select.selectOption(target);
        await page.locator('#settingsForm button[type="submit"]:not([name="action"])').click();
        await page.waitForLoadState('domcontentloaded');

        // Read it back from a fresh render, not from the redirected page: the
        // point is that it reached the database.
        await page.goto(SETTINGS);
        await expect(page.locator('#bible_version')).toHaveValue(target);
    });

    test('the email opt-in toggles and persists', async ({ page }) => {
        await page.goto(SETTINGS);

        const optIn  = page.locator('#email');
        const before = await optIn.isChecked();

        await optIn.setChecked(!before);
        await page.locator('#settingsForm button[type="submit"]:not([name="action"])').click();
        await page.waitForLoadState('domcontentloaded');

        await page.goto(SETTINGS);
        await expect(page.locator('#email')).toBeChecked({ checked: !before });

        // Put it back, so a re-run starts where this one did.
        await page.locator('#email').setChecked(before);
        await page.locator('#settingsForm button[type="submit"]:not([name="action"])').click();
        await page.waitForLoadState('domcontentloaded');
    });

    test('saving preferences does not drop the subscription', async ({ page }) => {
        // saveSettings() rewrites every column of the user's row, including
        // ones this form does not post — plan_view among them. A controller
        // that sent defaults for those would quietly undo the subscription and
        // drop the member back to the onboarding picker, which is the state
        // every plan-bearing view falls back to.
        await page.goto(SETTINGS);
        await expect(page.locator('#settingsForm input[name="plan_view"], #settingsForm select[name="plan_view"]'))
            .toHaveCount(0);

        await page.locator('#settingsForm button[type="submit"]:not([name="action"])').click();
        await page.waitForLoadState('domcontentloaded');

        for (const view of ['cwmhome', 'cwmplanview']) {
            await page.goto(`index.php?option=com_livingword&view=${view}`);
            await expect(
                page.locator('.com-livingword-onboarding'),
                `${view} fell back to onboarding after saving preferences`
            ).toHaveCount(0);
        }
    });
});
