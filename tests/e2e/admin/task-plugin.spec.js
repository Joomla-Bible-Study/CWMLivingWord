/**
 * The scheduled task plugin, as far as it can be taken without sending mail.
 *
 * plg_task_livingword carries the three routines the component's email
 * delivery hangs off: the daily reading email, the weekly progress digest and
 * the accountability-partner digest. Nothing has ever asserted that Joomla can
 * see them, and the failure mode is silence — #114 shipped a release where the
 * webservices plugin installed disabled, and this plugin's own install script
 * exists because Joomla registers plugins disabled and "an admin can configure
 * the email settings, create a scheduled task, and still get silence".
 *
 * Two things are pinned here, both about reachability rather than delivery:
 * the plugin is enabled, and Joomla's scheduler offers all three routines when
 * an admin goes to create a task.
 *
 * DELIBERATELY NOT RUN: executing a routine sends real email to every
 * subscriber with email = 1. The dev site is configured with live SMTP
 * (mailonline = true, a real relay and a real from-address), so a spec that
 * ran the daily-email routine would mail actual people. Covering execution
 * needs a site whose mail goes to a catcher, or mailonline = false, and that
 * is a decision about the environment rather than something a test should
 * assume.
 *
 * @package  Livingword.Tests
 * @since    __DEPLOY_VERSION__
 */

const { test, expect } = require('@playwright/test');

const PLUGINS = 'administrator/index.php?option=com_plugins';
const SCHEDULER_NEW = 'administrator/index.php?option=com_scheduler&view=select&layout=default';

const ROUTINES = [
    'LivingWord: Send Daily Reading Emails',
    'LivingWord: Send Weekly Progress Digest',
    'LivingWord: Send Weekly Partner Progress Digest',
];

test.describe('Task plugin registration @admin', () => {
    test('the plugin is installed and enabled', async ({ page }) => {
        // Asked as a filter rather than by reading a status icon: "appears when
        // filtering for enabled plugins" is the same claim, and it does not
        // depend on how Joomla renders that column this version.
        await page.goto(
            `${PLUGINS}&filter%5Bsearch%5D=livingword&filter%5Bfolder%5D=task&filter%5Benabled%5D=1`
        );

        await expect(
            page.locator('#pluginList tbody tr, #adminForm tbody tr'),
            'the task plugin is listed among the enabled plugins'
        ).not.toHaveCount(0);
    });

    test('the scheduler offers all three LivingWord routines', async ({ page }) => {
        // Asserted against the response com_scheduler actually sends, not the
        // live DOM. The picker rearranges itself after load, so a DOM query
        // races that: the same locator answered 1 on one load and 0 on the
        // next. The server's own HTML cannot race, and it is the same claim —
        // these routines are on the screen where an admin creates a task.
        const response = await page.request.get(SCHEDULER_NEW);

        expect(response.status(), 'the routine picker loads').toBe(200);

        const html = await response.text();

        for (const routine of ROUTINES) {
            expect(html.includes(routine), `${routine} is offered`).toBeTruthy();
        }
    });
});
