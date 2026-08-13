/**
 * Talking to the mail catcher.
 *
 * Every spec that exercises outbound mail needs the same four things: is a
 * catcher listening, empty it, wait for what arrives, read one message. Shared
 * here so a second mail spec cannot drift from the first — and so the refusal
 * to run without a catcher is written once, in one place, since the
 * alternative to a catcher is a test that emails real subscribers.
 *
 * Mailpit by default; anything exposing the same REST shape would do.
 * Override the location with MAILPIT_URL.
 *
 * @package  Livingword.Tests
 * @since    __DEPLOY_VERSION__
 */

const MAILPIT = process.env.MAILPIT_URL || 'http://127.0.0.1:8025';

/**
 * Whether a catcher is listening and its inbox is readable.
 *
 * @param   {object}  request  Playwright APIRequestContext
 *
 * @returns {Promise<boolean>}
 */
async function catcherAvailable(request) {
    try {
        const res = await request.get(`${MAILPIT}/api/v1/info`, { timeout: 3000 });

        return res.ok();
    } catch {
        return false;
    }
}

/**
 * Empty the inbox, so what arrives next can only be what the test caused.
 *
 * @param   {object}  request  Playwright APIRequestContext
 *
 * @returns {Promise<void>}
 */
async function clearInbox(request) {
    await request.delete(`${MAILPIT}/api/v1/messages`);
}

/**
 * Wait for captured mail, since delivery is asynchronous to the click.
 *
 * @param   {object}  request  Playwright APIRequestContext
 * @param   {number}  timeout  Milliseconds to keep asking
 *
 * @returns {Promise<Array>}   Message summaries, newest first
 */
async function waitForMail(request, timeout = 20000) {
    const deadline = Date.now() + timeout;

    for (;;) {
        const res = await request.get(`${MAILPIT}/api/v1/messages?limit=20`);
        const body = res.ok() ? await res.json() : { messages: [] };

        if ((body.messages || []).length || Date.now() > deadline) {
            return body.messages || [];
        }

        await new Promise((resolve) => {
            setTimeout(resolve, 500);
        });
    }
}

/**
 * The full body of one captured message, HTML and text concatenated.
 *
 * @param   {object}  request  Playwright APIRequestContext
 * @param   {string}  id       Mailpit message id
 *
 * @returns {Promise<string>}
 */
async function messageBody(request, id) {
    const res = await request.get(`${MAILPIT}/api/v1/message/${id}`);
    const body = await res.json();

    return `${body.HTML || ''}${body.Text || ''}`;
}

module.exports = { MAILPIT, catcherAvailable, clearInbox, waitForMail, messageBody };
