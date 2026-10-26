import assert from 'node:assert/strict';
import { test } from 'node:test';
import { readFile } from 'node:fs/promises';
import { runInNewContext } from 'node:vm';

const source = await readFile(new URL('../../public/js/fee-collection.js', import.meta.url), 'utf8');
function makePage(fetcher = async () => ({ ok: true, json: async () => ({results: []}) })) {
    const navigations = [];
    const page = { URL, AbortController, setTimeout, clearTimeout, fetch: fetcher,
        window: { location: { assign: url => navigations.push(new URL(url)) } } };
    runInNewContext(source, page);
    const form = page.feeCollection({ url: 'https://school.test/revenue/items/create', type: 'exam',
        query: '', classId: '', year: 2026, allYears: true, includeInactive: false, studentId: 42,
        amount: 850, date: '2026-10-23', method: 'cash', recordRevenue: true });
    return { form, navigations };
}

test('typing loads suggestions with the selected class and student filters', async () => {
    let request;
    const {form} = makePage(async url => { request = new URL(url); return {ok: true, json: async () => ({results: [{id: 8, name: 'Ameli'}]})}; });
    form.query = 'Am'; form.classId = '5'; form.includeInactive = true;
    await form.search();
    assert.equal(request.searchParams.get('q'), 'Am');
    assert.equal(request.searchParams.get('class_room_id'), '5');
    assert.equal(request.searchParams.get('suggest'), '1');
    assert.equal(request.searchParams.has('student_id'), false);
    assert.equal(request.searchParams.get('include_inactive'), '1');
    assert.equal(form.results[0].name, 'Ameli');
    assert.equal(form.open, true);
    assert.equal(form.loading, false);
});

test('a late search response cannot replace newer suggestions', async () => {
    const waiting = [];
    const {form} = makePage(() => new Promise(resolve => waiting.push(resolve)));
    form.query = 'Am'; const first = form.search();
    form.query = 'Di'; const second = form.search();
    waiting[1]({ok: true, json: async () => ({results: [{id: 2, name: 'Dinu'}]})}); await second;
    waiting[0]({ok: true, json: async () => ({results: [{id: 1, name: 'Ameli'}]})}); await first;
    assert.equal(form.results[0].name, 'Dinu');
});

test('clearing the search removes results and ignores an in-flight response', async () => {
    let resolve;
    const {form} = makePage(() => new Promise(done => { resolve = done; }));
    form.query = 'Am'; const pending = form.search();
    form.query = ''; form.queueSearch();
    resolve({ok: true, json: async () => ({results: [{id: 1}]})}); await pending;
    assert.equal(form.results.length, 0);
    assert.equal(form.open, false);
    assert.equal(form.loading, false);
});

test('keyboard selection loads the chosen student while retaining class and period', () => {
    const {form, navigations} = makePage();
    form.query = 'Am'; form.classId = '5'; form.results = [{id: 10}, {id: 11}]; form.open = true;
    const event = key => ({key, preventDefault() {}});
    form.keydown(event('ArrowDown')); form.keydown(event('ArrowDown')); form.keydown(event('Enter'));
    assert.equal(navigations[0].searchParams.get('student_id'), '11');
    assert.equal(navigations[0].searchParams.get('class_room_id'), '5');
    assert.equal(navigations[0].searchParams.get('year'), '2026');
    form.keydown(event('Escape')); assert.equal(form.open, false);
});

test('category switching preserves the student and opens the correct payment workflow', () => {
    const {form, navigations} = makePage();
    form.categoryChanged('fee:facility');
    assert.equal(navigations[0].searchParams.get('quick'), 'facility');
    assert.equal(navigations[0].searchParams.get('student_id'), '42');
    form.categoryChanged('77');
    assert.equal(navigations[1].searchParams.has('quick'), false);
    assert.equal(navigations[1].searchParams.get('category_id'), '77');
    assert.equal(navigations[1].searchParams.get('student_id'), '42');
});

test('changing class clears the selected student and cheques force revenue tracking', () => {
    const {form, navigations} = makePage();
    form.classId = '9'; form.classChanged();
    assert.equal(navigations[0].searchParams.has('student_id'), false);
    assert.equal(navigations[0].searchParams.get('class_room_id'), '9');
    form.recordRevenue = false; form.method = 'cheque'; form.methodChanged();
    assert.equal(form.recordRevenue, true);
    assert.equal(form.viewReceipt, true);
});

test('search failures release loading state and leave a usable fallback', async () => {
    const {form} = makePage(async () => ({ok: false}));
    form.query = 'Am'; await form.search();
    assert.equal(form.loading, false);
    assert.match(form.error, /Search students/);
    assert.equal(form.open, false);
});


test('locked collection categories cannot navigate to another fee and keep an editable default amount', () => {
    const page = { URL, AbortController, setTimeout, clearTimeout, window: {location: {assign() { throw new Error('Category must stay fixed'); }}} };
    runInNewContext(source, page);
    const form = page.feeCollection({url: 'https://school.test/revenue/items/create', type: 'facility', categoryLocked: true, amount: 3800, defaultAmount: 3800});
    form.categoryChanged('fee:exam');
    form.categoryChanged('77');
    assert.equal(form.amount, 3800);
    form.amount = 1200;
    assert.equal(form.amount, 1200);
    assert.equal(form.defaultAmount, 3800);
});
