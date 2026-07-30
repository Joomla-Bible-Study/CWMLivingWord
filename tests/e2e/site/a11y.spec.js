/**
 * E2E — WCAG 2.2 AA, public site views
 *
 * Scans every page a visitor can reach. The scan is confined to the
 * `com-livingword-*` wrapper each view renders, because everything around it
 * is the site's Joomla template and other extensions — markup LivingWord does
 * not own and cannot fix.
 *
 * These degrade rather than fail on an empty database: with no plans or groups
 * a view still renders its wrapper, and an empty wrapper is still worth
 * scanning — headings, landmarks and the empty-state message are exactly the
 * markup a screen-reader user meets first.
 *
 * The three unrouted views (cwmcomplete, cwminvite, cwmunsubscribe) are
 * reached by their raw query string. They are the pages a person lands on from
 * an email or an invitation link — often the first LivingWord page they ever
 * see, and the one they meet without having chosen to.
 */

const { test, expect } = require('@playwright/test');
const { expectNoViolations } = require('../helpers/axe');

/**
 * Matches the per-view wrapper: com-livingword-home, -resources, -groups and
 * so on. Deliberately the outer wrapper rather than an inner content div — the
 * wrapper is where landmarks and headings live.
 */
const LIVINGWORD_CONTENT = '[class*="com-livingword-"]';

/**
 * Every menu-reachable view, by the name the site router accepts.
 */
const VIEWS = [
    ['home', 'cwmhome'],
    ['plan view', 'cwmplanview'],
    ['resources', 'cwmresources'],
    ['study tools', 'cwmtools'],
    ['groups', 'cwmgroups'],
    ['settings', 'cwmsettings'],
];

/**
 * Views reached from an email or invitation rather than from a menu.
 *
 * Scanned with a deliberately invalid token: the failure path is what an
 * expired or mistyped link produces, it needs no fixture, and its message is
 * as much a part of the experience as the success path. A visitor who follows
 * a stale unsubscribe link still has to be able to read what went wrong.
 */
const LANDING_PAGES = [
    ['completion landing', 'index.php?option=com_livingword&view=cwmcomplete&token=invalid-token-for-a11y-scan'],
    ['unsubscribe landing', 'index.php?option=com_livingword&view=cwmunsubscribe&token=invalid-token-for-a11y-scan'],
    ['invitation landing', 'index.php?option=com_livingword&view=cwminvite&token=invalid-token-for-a11y-scan'],
];

test.describe('Site accessibility (WCAG 2.2 AA) @a11y', () => {
    for (const [label, view] of VIEWS) {
        test(`${label} meets WCAG 2.2 AA`, async ({ page }) => {
            await page.goto(`index.php?option=com_livingword&view=${view}`);

            await expect(
                page.locator(LIVINGWORD_CONTENT).first(),
                `${label} did not render a LivingWord wrapper`
            ).toBeVisible();

            await expectNoViolations(page, expect, { include: LIVINGWORD_CONTENT });
        });
    }

    for (const [label, url] of LANDING_PAGES) {
        test(`${label} meets WCAG 2.2 AA`, async ({ page }) => {
            await page.goto(url);

            await expect(
                page.locator(LIVINGWORD_CONTENT).first(),
                `${label} did not render a LivingWord wrapper`
            ).toBeVisible();

            await expectNoViolations(page, expect, { include: LIVINGWORD_CONTENT });
        });
    }
});
