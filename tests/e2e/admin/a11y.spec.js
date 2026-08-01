/**
 * E2E — WCAG 2.2 AA, administrator views
 *
 * Scans every screen the administrator half of the component renders: the
 * control panel, the list views and the edit forms. Confined to
 * `section#content`, the region the component renders into — the Atum
 * template's sidebar, toolbar and header belong to Joomla, and their markup is
 * neither ours to fix nor fair to judge us on.
 *
 * Admin pages need an authenticated session; the admin-* Playwright project
 * supplies one via storageState.
 *
 * Each case also carries a "does this screen render at all" duty: it requires
 * `section#content` before scanning, so a view that 500s or comes back blank
 * fails here rather than passing silently.
 */

const { test, expect } = require('@playwright/test');
const { expectNoViolations } = require('../helpers/axe');

const LIVINGWORD_CONTENT = 'section#content';

/**
 * Widgets rendered by third-party code we neither author nor can patch.
 *
 * Excluded by selector rather than by disabling rules, so `target-size` and
 * `aria-input-field-name` still apply to LivingWord's own markup on the same
 * page.
 *
 *   .choices  Joomla's Choices.js — the remove button is under the 24x24
 *             minimum of SC 2.5.8. Used by the tag fields added in 5.6.0.
 *   .tox-tinymce  the editor on plan and plan-day descriptions: role
 *                 "application" without its required ARIA attributes, and
 *                 editor iframes with no title.
 */
const THIRD_PARTY = [
    '.choices',
    '.tox-tinymce',
    // Joomla's own system-message component. It sits inside section#content,
    // so a message enqueued by our install script — "the task plugin has been
    // enabled" — drags Joomla's alert styling into our scan. On Joomla 5.4's
    // Atum those alerts fail contrast (#8494ab on #e4ebf5 and similar); on 6.1
    // they pass, which is why this only appeared once CI ran against 5.4.
    //
    // The message text is ours; its markup and palette are Joomla's, and we can
    // neither fix nor be judged on them. Excluded by container rather than by
    // disabling color-contrast, so the rule still applies to everything we do
    // render on the same page.
    '#system-message-container',
    'joomla-alert',
];

/**
 * Every list-style admin screen, by view name.
 */
const SCREENS = [
    ['control panel', 'cwmcpanel'],
    ['reading plans', 'cwmplans'],
    ['plan days', 'cwmplandetails'],
    ['resource links', 'cwmlinks'],
    ['study tools', 'cwmtools'],
    ['groups', 'cwmgroups'],
    ['subscribers', 'cwmusers'],
    ['database maintenance', 'cwmutilities'],
];

/**
 * Every entity edit form, reached as a fresh add.
 *
 * No fixture record is needed — an add renders the same form an edit does, and
 * form controls are where labelling and grouping failures concentrate, and
 * where a screen-reader user is most likely to be blocked outright.
 */
const FORMS = [
    ['reading plan', 'cwmplan'],
    ['plan day', 'cwmplandetail'],
    ['resource link', 'cwmlink'],
    ['study tool', 'cwmtool'],
    ['group', 'cwmgroup'],
];

test.describe('Admin accessibility (WCAG 2.2 AA) @a11y', () => {
    for (const [label, view] of SCREENS) {
        test(`${label} meets WCAG 2.2 AA`, async ({ page }) => {
            await page.goto(`administrator/index.php?option=com_livingword&view=${view}`);

            await expect(
                page.locator(LIVINGWORD_CONTENT),
                `${label} did not render a content region`
            ).toBeVisible();

            await expectNoViolations(page, expect, {
                include: LIVINGWORD_CONTENT,
                exclude: THIRD_PARTY,
            });
        });
    }

    for (const [label, view] of FORMS) {
        test(`${label} form meets WCAG 2.2 AA`, async ({ page }) => {
            await page.goto(
                `administrator/index.php?option=com_livingword&view=${view}&layout=edit`
            );

            await expect(
                page.locator(`${LIVINGWORD_CONTENT} form`),
                `${label} form did not render`
            ).toBeVisible();

            await expectNoViolations(page, expect, {
                include: LIVINGWORD_CONTENT,
                exclude: THIRD_PARTY,
            });
        });
    }
});
