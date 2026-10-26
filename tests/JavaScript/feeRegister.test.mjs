import assert from 'node:assert/strict';
import { test } from 'node:test';
import { readFile } from 'node:fs/promises';
import { runInNewContext } from 'node:vm';

const code = await readFile(new URL('../../public/js/fee-register.js', import.meta.url), 'utf8');
function harness() {
    const page = { AbortController };
    runInNewContext(code, page);
    const pending = [];
    const requests = page.feeRegisterRequests((url, options) => new Promise(resolve => pending.push({ url, options, resolve })));
    return { requests, pending, page };
}
const response = payload => ({ ok: true, json: async () => payload });

test('instant register requests carry AJAX headers and ignore older responses', async () => {
    const { requests, pending } = harness();
    const old = requests.load('/class-fees?view=students&q=Ali');
    const latest = requests.load('/class-fees?view=students&q=Alice&status=unpaid');
    assert.equal(pending[0].options.signal.aborted, true);
    assert.equal(pending[1].options.headers.Accept, 'application/json');
    pending[1].resolve(response({ html: 'Alice only', count: 1 }));
    assert.equal((await latest).html, 'Alice only');
    pending[0].resolve(response({ html: 'Old unrelated students' }));
    assert.equal(await old, null);
});

test('clearing a pending search invalidates its eventual response and a retry can succeed', async () => {
    const { requests, pending } = harness();
    const old = requests.load('/search?q=Old');
    requests.cancel();
    pending[0].resolve(response({ html: 'Obsolete' }));
    assert.equal(await old, null);
    const next = requests.load('/search?q=New');
    pending[1].resolve(response({ html: 'New result' }));
    assert.equal((await next).html, 'New result');
});

test('a failed search is reported so the regular Apply filters fallback stays usable', async () => {
    const { requests, pending } = harness();
    const result = requests.load('/search');
    pending[0].resolve({ ok: false });
    await assert.rejects(result, /could not be refreshed/);
});
