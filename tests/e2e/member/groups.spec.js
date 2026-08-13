/**
 * Reading groups, end to end, as a subscriber.
 *
 * Joining and leaving are the only writes a reader can make to somebody else's
 * data, so the round trip matters twice over: it has to take effect, and it has
 * to be refused without a token.
 *
 * These specs mutate real membership, so each one restores what it found —
 * including on the way out of a failure, since a member left in a group would
 * change what the next run starts from.
 *
 * @package  Livingword.Tests
 * @since    __DEPLOY_VERSION__
 */

const { test, expect } = require('@playwright/test');

const GROUPS = 'index.php?option=com_livingword&view=cwmgroups';

const AVAILABLE_JOIN = 'form[action*="cwmgroup.join"] button[type="submit"]';
const LEAVE          = 'form[action*="cwmgroup.leave"] button[type="submit"]';

/**
 * The group ids the member currently belongs to, read from the leave forms
 * the detail pages render — membership as the site itself reports it.
 *
 * @param   {object}  page  Playwright page
 *
 * @returns {Promise<string[]>}
 */
async function joinedGroupIds(page) {
    await page.goto(GROUPS);

    return page.locator('form[action*="cwmgroup.join"] input[name="group_id"]').evaluateAll(
        (inputs) => inputs.map((input) => input.value)
    ).then(async (available) => {
        const all = await page.locator('a[href*="group_id="], a[href*="cwmgroupdetail"]').evaluateAll(
            (links) => links.map((link) => (link.href.match(/group_id=(\d+)/) || [])[1]).filter(Boolean)
        );

        // Anything listed but not offering a join button is already joined.
        return all.filter((id) => !available.includes(id));
    });
}

/**
 * Leave every group the member is in, so a run starts from a known state.
 *
 * @param   {object}  page  Playwright page
 *
 * @returns {Promise<void>}
 */
async function leaveEverything(page) {
    for (const id of await joinedGroupIds(page)) {
        await page.goto(`index.php?option=com_livingword&view=cwmgroupdetail&group_id=${id}`);

        if (await page.locator(LEAVE).count()) {
            await page.locator(LEAVE).click();
            await page.waitForLoadState('domcontentloaded');
        }
    }
}

test.describe('Reading groups @member', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto(GROUPS);

        test.skip(
            !(await page.locator(AVAILABLE_JOIN).count()),
            'no group on this site is open to join, so there is nothing to exercise'
        );
    });

    test.afterEach(async ({ page }) => {
        await leaveEverything(page);
    });

    test('the groups view offers a group to join', async ({ page }) => {
        await expect(page.locator(AVAILABLE_JOIN).first()).toBeVisible();

        const groupId = await page.locator('form[action*="cwmgroup.join"] input[name="group_id"]').first().inputValue();

        expect(Number(groupId), 'the join form carries a real group id').toBeGreaterThan(0);
    });

    test('joining shows up as membership on a fresh load', async ({ page }) => {
        const before = await page.locator(AVAILABLE_JOIN).count();

        await page.locator(AVAILABLE_JOIN).first().click();
        await page.waitForLoadState('domcontentloaded');

        // From a fresh render of the list: one fewer group left to join is the
        // site's own account of the membership, not the redirect's.
        await page.goto(GROUPS);
        await expect(page.locator(AVAILABLE_JOIN)).toHaveCount(before - 1);
    });

    test('a joined group offers a way out again', async ({ page }) => {
        const groupId = await page.locator('form[action*="cwmgroup.join"] input[name="group_id"]').first().inputValue();

        await page.locator(AVAILABLE_JOIN).first().click();
        await page.waitForLoadState('domcontentloaded');

        await page.goto(`index.php?option=com_livingword&view=cwmgroupdetail&group_id=${groupId}`);
        await expect(page.locator(LEAVE), 'a member is offered Leave').toHaveCount(1);

        await page.locator(LEAVE).click();
        await page.waitForLoadState('domcontentloaded');

        // Back to being offered the join, which is the same statement made the
        // other way round.
        await page.goto(GROUPS);
        await expect(
            page.locator(`form[action*="cwmgroup.join"] input[name="group_id"][value="${groupId}"]`)
        ).toHaveCount(1);
    });

    test('joining is refused without a session token', async ({ page }) => {
        const groupId = await page.locator('form[action*="cwmgroup.join"] input[name="group_id"]').first().inputValue();

        const body = await page.evaluate(async (id) => {
            const url = new URL(window.location.origin);

            url.pathname = '/index.php';
            url.search = `option=com_livingword&task=cwmgroup.join&group_id=${id}`;

            const res = await fetch(url.toString(), { credentials: 'same-origin' });

            return res.text();
        }, groupId);

        // Joomla's own wording; the controller dies with Text::_('JINVALID_TOKEN'),
        // which resolves to this sentence on a site with the language installed.
        expect(body, 'an untokened join is refused').toMatch(/invalid security token|JINVALID_TOKEN/i);

        // And it did not quietly work anyway.
        await page.goto(GROUPS);
        await expect(
            page.locator(`form[action*="cwmgroup.join"] input[name="group_id"][value="${groupId}"]`),
            'the member was not joined by the untokened request'
        ).toHaveCount(1);
    });
});
