/**
 * Driving Joomla's scheduler from a spec.
 *
 * The task plugin does nothing until a scheduled task exists — deliberately, as
 * its install script explains. So a mail spec has to be able to create the task
 * it needs rather than assume somebody made one by hand, or it silently tests
 * nothing on a fresh site.
 *
 * @package  Livingword.Tests
 * @since    __DEPLOY_VERSION__
 */

const ADMIN = 'administrator/index.php?option=com_scheduler';

const TASKS = `${ADMIN}&view=tasks&filter%5Bsearch%5D=&filter%5Bstate%5D=`;

/**
 * Find the task row for a routine, creating the task when there is none.
 *
 * Creation goes through the same screens an administrator uses: the routine
 * picker's own link, then the task form. An execution rule has to be filled in
 * or the form refuses to save ("Invalid field: Interval in Minutes"), which is
 * worth knowing — the rule is irrelevant to a test that runs the task by hand,
 * but the form does not care.
 *
 * @param   {object}  page     Playwright page, authenticated as an administrator
 * @param   {string}  routine  e.g. "livingword.weekly_digest"
 * @param   {string}  title    Title to give a task this creates
 *
 * @returns {Promise<boolean>}  Whether a task for the routine now exists
 */
async function ensureTask(page, routine, title) {
    await page.goto(TASKS);

    if (await page.locator(`#adminForm tbody tr:has-text("${title}")`).count()) {
        return true;
    }

    await page.goto(`${ADMIN}&task=task.add&type=${encodeURIComponent(routine)}`);

    if (!(await page.locator('#jform_title').count())) {
        return false;
    }

    await page.fill('#jform_title', title);

    const ruleType = page.locator('#jform_execution_rules_rule_type');

    if (await ruleType.count()) {
        await ruleType.selectOption('interval-hours');
        await page.waitForTimeout(500);
    }

    for (const id of await page.locator('input[id*="interval"]:visible').evaluateAll((els) => els.map((e) => e.id))) {
        await page.locator(`#${id}`).fill('24');
    }

    const navigated = page.waitForNavigation({ waitUntil: 'domcontentloaded' }).catch(() => {});

    await page.evaluate(() => window.Joomla.submitbutton('task.save'));
    await navigated;

    await page.goto(TASKS);

    return (await page.locator(`#adminForm tbody tr:has-text("${title}")`).count()) > 0;
}

/**
 * Run a task now, from the list's own Run Task button.
 *
 * The CLI runner would be the obvious choice and is not usable here: on any
 * site with Proclaim installed, `scheduler:run` dies before any task executes
 * (Proclaim#1787), including for core tasks. The admin button is the path that
 * works.
 *
 * @param   {object}  page   Playwright page, authenticated as an administrator
 * @param   {string}  title  Task title to run
 *
 * @returns {Promise<void>}
 */
async function runTask(page, title) {
    await page.goto(TASKS);

    const row = page.locator('#adminForm tbody tr').filter({ hasText: title }).first();

    await row.locator('button:has-text("Run Task")').click();
}

module.exports = { TASKS, ensureTask, runTask };
