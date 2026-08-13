/**
 * The invitation link an administrator hands out, followed as a stranger.
 *
 * The a11y suite visits cwminvite with a deliberately invalid token, which
 * scans the "unknown invite" branch and nothing else. The branch that matters
 * — a real invite arriving at a page that has to work for somebody with no
 * account — has never been exercised, and it is the whole point of the feature
 * (#55).
 *
 * This lives in the admin project because the token comes from the group
 * editor, which renders the shareable URL an admin actually copies. Building
 * the link in the test instead would prove nothing about what gets shared.
 *
 * The landing page itself is then opened in a context with no session at all,
 * because a guest is who invitations are sent to.
 *
 * @package  Livingword.Tests
 * @since    __DEPLOY_VERSION__
 */

const { test, expect } = require('@playwright/test');

const GROUPS = 'administrator/index.php?option=com_livingword&view=cwmgroups';

/**
 * The shareable invite URL for the first group that has one.
 *
 * @param   {object}  page  Playwright page, authenticated as an administrator
 *
 * @returns {Promise<string|null>}
 */
async function inviteUrl(page) {
    // Filters stated rather than inherited: Joomla remembers list state per
    // user, so an earlier spec's trash view would otherwise empty this list.
    await page.goto(`${GROUPS}&filter%5Bsearch%5D=&filter%5Bpublished%5D=`);

    const editLink = page.locator('#adminForm tbody tr a[href*="cwmgroup.edit"]').first();

    if (!(await editLink.count())) {
        return null;
    }

    await editLink.click();
    await page.waitForLoadState('domcontentloaded');

    const field = page.locator('#inviteUrlField');

    return (await field.count()) ? field.inputValue() : null;
}

test.describe('Group invitation link @admin', () => {
    test('the group editor offers a shareable invite URL', async ({ page }) => {
        const url = await inviteUrl(page);

        test.skip(url === null, 'no group on this site carries an invite token');

        expect(url, 'the URL points at the invitation landing').toContain('view=cwminvite');
        expect(url, 'and carries a token').toMatch(/token=.+/);
    });

    test('a stranger following the link is told what they are joining', async ({ page, browser }) => {
        const url = await inviteUrl(page);

        test.skip(url === null, 'no group on this site carries an invite token');

        // No storage state: an invitation is for somebody who is not logged in,
        // and that is the case the feature exists for.
        const guest = await browser.newContext({ ignoreHTTPSErrors: true });

        try {
            const guestPage = await guest.newPage();

            await guestPage.goto(url);

            const invite = guestPage.locator('.com-livingword-invite');

            await expect(invite, 'the invitation renders for a guest').toBeVisible();

            // Not the branch an unknown token gets — the difference this exists
            // to prove.
            await expect(
                invite.locator('.alert-warning'),
                'a real token is not treated as an unknown invite'
            ).toHaveCount(0);

            // A guest needs somewhere to go: the page has to offer a way in
            // rather than a dead end.
            await expect(
                guestPage.locator('a[href*="com_users"], form[action*="cwmgroup.join"]'),
                'a guest is offered login, registration, or the join itself'
            ).not.toHaveCount(0);
        } finally {
            await guest.close();
        }
    });
});
