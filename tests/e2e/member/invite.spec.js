/**
 * What the token-carrying landing pages must refuse.
 *
 * The tokens cwmcomplete and cwmunsubscribe consume are minted server-side and
 * rendered nowhere, so a valid one cannot be had without reading the database.
 * What can be pinned without it is the half that protects the reader: an
 * unknown token must be refused, and must not act. The invitation landing is
 * covered with a real token in tests/e2e/admin/invite.spec.js, where an
 * administrator session is available to produce one.
 *
 * @package  Livingword.Tests
 * @since    __DEPLOY_VERSION__
 */

const { test, expect } = require('@playwright/test');

const HOME = 'index.php?option=com_livingword&view=cwmhome';
const SETTINGS = 'index.php?option=com_livingword&view=cwmsettings';

test.describe('Invitation landing @member', () => {
    test('an unknown token is refused rather than guessed at', async ({ page }) => {
        await page.goto('index.php?option=com_livingword&view=cwminvite&token=not-a-real-invite-token');

        const invite = page.locator('.com-livingword-invite');

        await expect(invite).toBeVisible();
        await expect(invite.locator('.alert-warning'), 'the unknown-invite branch').toHaveCount(1);

        // And it offers no way into a group it could not identify.
        await expect(
            page.locator('form[action*="cwmgroup.join"]'),
            'nothing to join on an unknown invite'
        ).toHaveCount(0);
    });

    test('an empty token is refused too', async ({ page }) => {
        await page.goto('index.php?option=com_livingword&view=cwminvite&token=');

        await expect(page.locator('.com-livingword-invite .alert-warning')).toHaveCount(1);
    });
});

test.describe('Email landing pages @member', () => {
    test('completing a reading with an unknown token does not mark anything read', async ({ page }) => {
        await page.goto(HOME);

        const container = page.locator('[data-livingword-progress]');

        test.skip(!(await container.count()), 'no reading is scheduled for today, so there is no progress to protect');

        const before = await container.getAttribute('data-completed');

        await page.goto('index.php?option=com_livingword&view=cwmcomplete&token=not-a-real-action-token');
        await expect(page.locator('body')).not.toContainText(/fatal|exception|stack trace/i);

        // The reader's own progress is untouched: a stranger holding a guessed
        // token must not be able to mark somebody's reading complete.
        await page.goto(HOME);
        expect(
            await page.locator('[data-livingword-progress]').getAttribute('data-completed'),
            'progress unchanged by an unknown token'
        ).toBe(before);
    });

    test('unsubscribing with an unknown token leaves the subscription alone', async ({ page }) => {
        await page.goto(SETTINGS);

        const optIn  = page.locator('#email');
        const before = await optIn.isChecked().catch(() => null);

        test.skip(before === null, 'preferences are not reachable for this member');

        await page.goto('index.php?option=com_livingword&view=cwmunsubscribe&token=not-a-real-unsubscribe-token');
        await expect(page.locator('body')).not.toContainText(/fatal|exception|stack trace/i);

        await page.goto(SETTINGS);
        await expect(
            page.locator('#email'),
            'an unknown token did not switch the reader off email'
        ).toBeChecked({ checked: before });
    });
});
