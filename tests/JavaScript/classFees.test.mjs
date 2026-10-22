import assert from 'node:assert/strict';
import { test } from 'node:test';
import { termCalendar, bulkClassFees } from '../../resources/js/classFees.js';

test('first term generates three consecutive four-month terms', () => {
    const calendar = termCalendar(null, '2026-01-01');
    calendar.init();
    assert.deepEqual(calendar.terms, [{ start: '2026-01-01', end: '2026-04-30' },
        { start: '2026-05-01', end: '2026-08-31' }, { start: '2026-09-01', end: '2026-12-31' }]);
});

test('manually adjusted first end regenerates later terms and saved overrides survive initialization', () => {
    const calendar = termCalendar(null, '2026-01-01');
    calendar.init();
    calendar.terms[0].end = '2026-04-28';
    calendar.fillRemaining();
    assert.equal(calendar.terms[1].start, '2026-04-29');
    assert.equal(calendar.terms[2].end, '2026-12-28');
    calendar.terms[1].start = '2026-05-02';
    const loaded = termCalendar(calendar.terms, '2026-01-01');
    loaded.init();
    assert.equal(loaded.terms[1].start, '2026-05-02');
});

test('month ends and leap-year academic years keep their boundary without drifting', () => {
    const calendar = termCalendar(null, '2026-01-31');
    calendar.init();
    assert.equal(calendar.terms[0].end, '2026-05-30');
    assert.equal(calendar.terms[1].end, '2026-09-29');
    assert.equal(calendar.terms[2].end, '2027-01-30');
    const leap = termCalendar(null, '2024-02-29');
    leap.init();
    assert.equal(leap.terms[2].end, '2025-02-27');
});

const candidates = [
    { key: '1:1', student_id: 1, class_id: 1, name: 'A student', admission: 'ADM01', class_name: 'Class A', installments: [
        { key: '10:2026-01-01', balance: 1000, type: 'exam', term: 1, label: 'Term 1' },
        { key: '10:2026-05-01', balance: 600, type: 'exam', term: 2, label: 'Term 2' }] },
    { key: '2:2', student_id: 2, class_id: 2, name: 'B student', admission: 'ADM02', class_name: 'Class B', installments: [
        { key: '20:2026-01-01', balance: 1000, type: 'exam', term: 1, label: 'Term 1' }] },
];

test('class select all and student search add across classes without duplicates', () => {
    const bulk = bulkClassFees(candidates, 1);
    bulk.init();
    assert.equal(bulk.visible.length, 1);
    bulk.toggleAll(true);
    assert.equal(bulk.allSelected, true);
    bulk.addSelected();
    assert.equal(bulk.lines.length, 2);
    bulk.classId = '';
    bulk.query = 'ADM02';
    assert.equal(bulk.visible[0].name, 'B student');
    bulk.toggleAll(true);
    bulk.addSelected();
    assert.equal(bulk.lines.length, 3);
    bulk.query = '';
    bulk.toggleAll(true);
    bulk.addSelected();
    assert.equal(bulk.lines.length, 3);
    assert.equal(bulk.total, 2600);
});

test('term selection and revenue toggle apply to queued rows and new additions', () => {
    const bulk = bulkClassFees(candidates);
    bulk.init();
    bulk.installment = 'term:1';
    bulk.toggleAll(true);
    bulk.addSelected();
    assert.equal(bulk.lines.length, 2);
    assert.equal(bulk.income, 2000);
    bulk.recordRevenue = false;
    bulk.applyRevenue();
    assert.equal(bulk.income, 0);
    bulk.installment = 'term:2';
    bulk.toggleAll(true);
    bulk.addSelected();
    assert.equal(bulk.lines[2].record_revenue, false);
    bulk.lines[0].amount = 500;
    bulk.lines[0].record_revenue = true;
    assert.equal(bulk.income, 500);
    assert.equal(bulk.total, 2100);
});

test('validation redisplay preserves amounts and accounting choices', () => {
    const bulk = bulkClassFees(candidates, '', [{ student_id: 1, class_room_id: 1, cycle: '10:2026-01-01', amount: '250', record_revenue: '0' }]);
    bulk.init();
    assert.equal(bulk.lines[0].amount, '250');
    assert.equal(bulk.lines[0].record_revenue, false);
    assert.equal(bulk.total, 250);
});

test('standalone fee script defines both Alpine factories without the main application bundle', async () => {
    const { readFile } = await import('node:fs/promises');
    const { runInNewContext } = await import('node:vm');
    const script = await readFile(new URL('../../public/js/class-fees.js', import.meta.url), 'utf8');
    const page = {};
    runInNewContext(script, page);
    assert.equal(typeof page.termCalendar, 'function');
    assert.equal(typeof page.bulkClassFees, 'function');
    const form = { ...page.termCalendar(null, '2026-01-01'), enabled: false, feeAmount: '700' };
    form.init();
    form.enabled = true;
    assert.equal(form.feeAmount, '700');
    assert.equal(form.terms.length, 3);
    assert.equal(form.terms[0].end, '2026-04-30');
    assert.equal(form.terms[1].start, '2026-05-01');
    assert.equal(form.terms[2].end, '2026-12-31');
    const bulk = page.bulkClassFees(candidates, 1);
    bulk.init();
    bulk.toggleAll(true);
    bulk.addSelected();
    assert.equal(bulk.lines.length, 2);
});
