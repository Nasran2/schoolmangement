import assert from 'node:assert/strict';
import { test } from 'node:test';
import { termCalendar, bulkClassFees, bulkClassFeeReversals } from '../../resources/js/classFees.js';

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
    bulk.terms = ['1'];
    bulk.toggleAll(true);
    bulk.addSelected();
    assert.equal(bulk.lines.length, 2);
    assert.equal(bulk.income, 2000);
    bulk.recordRevenue = false;
    bulk.applyRevenue();
    assert.equal(bulk.income, 0);
    bulk.terms = ['2'];
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
    assert.equal(typeof page.bulkClassFeeReversals, 'function');
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

const paidCandidates = candidates.map(student => ({ ...student, installments: student.installments.map((cycle, index) => ({
    ...cycle, paid: cycle.balance, fingerprint: student.key + ':' + index, revenue: index === 0 ? cycle.balance : 0,
    history: index === 0 ? 0 : cycle.balance, pending: 0, blocked_reason: null,
    payments: [{ source: index === 0 ? 'revenue' : 'history', id: index + student.student_id, amount: cycle.balance }],
})) }));

test('bulk make unpaid selects paid students across classes, terms and search without duplicate fees', () => {
    const state = bulkClassFeeReversals(paidCandidates, 1);
    state.init();
    state.toggleAll(true);
    state.addSelected();
    assert.equal(state.lines.length, 2);
    assert.equal(state.total, 1600);
    assert.equal(state.income, 1000);
    assert.equal(state.receiptCount, 2);
    state.classId = '';
    state.query = 'ADM02';
    state.toggleAll(true);
    state.addSelected();
    state.query = '';
    state.toggleAll(true);
    state.addSelected();
    assert.equal(state.lines.length, 3);
    assert.equal(state.total, 2600);
    const payload = JSON.parse(state.payload());
    assert.deepEqual(Object.keys(payload[0]), ['student_id', 'class_room_id', 'cycle', 'fingerprint']);
    assert.equal(payload[0].fingerprint, paidCandidates[0].installments[0].fingerprint);
});

test('bulk make unpaid excludes blocked fees and preserves only unchanged selections after an error', () => {
    const data = structuredClone(paidCandidates);
    data[0].installments[0].blocked_reason = 'Refund requires individual review';
    data[1].installments[0].blocked_reason = 'Waiver requires review';
    const state = bulkClassFeeReversals(data);
    state.init();
    assert.equal(state.visible.length, 2);
    assert.equal(state.selectable.length, 1);
    state.toggleAll(true);
    state.addSelected();
    assert.equal(state.lines.length, 1);
    assert.equal(state.lines[0].label, 'Term 2');
    assert.match(state.message, /individual review/);
    const previous = JSON.parse(state.payload());
    const unchanged = bulkClassFeeReversals(data, '', previous);
    unchanged.init();
    assert.equal(unchanged.lines.length, 1);
    data[0].installments[1].fingerprint = 'changed';
    const changed = bulkClassFeeReversals(data, '', previous);
    changed.init();
    assert.equal(changed.lines.length, 0);
    assert.match(changed.message, /changed/);
});

test('bulk make unpaid term and facility selection and 500-fee limit are enforced in the queue', () => {
    const state = bulkClassFeeReversals(paidCandidates);
    state.init();
    state.terms = ['2'];
    state.toggleAll(true);
    state.addSelected();
    assert.equal(state.lines.length, 1);
    assert.equal(state.lines[0].label, 'Term 2');
    state.lines = [];
    state.installment = 'facility';
    assert.equal(state.visible.length, 0);
    state.installment = 'all';
    state.selectTerms(true);
    state.candidates = Array.from({ length: 501 }, (_, index) => ({ ...paidCandidates[0], key: `1:${index + 1}`, student_id: index + 1, installments: [paidCandidates[0].installments[0]] }));
    state.toggleAll(true);
    state.addSelected();
    assert.equal(state.lines.length, 500);
    assert.match(state.message, /500/);
});

test('bulk term collection and reversal include optional computer fees while keeping fee-only filters', () => {
    for (const factory of [bulkClassFees, bulkClassFeeReversals]) {
        const page = factory([]);
        const exam = {type:'exam', term:2};
        const computer = {type:'computer', term:2};
        const facility = {type:'facility'};
        assert.equal(page.matchesCycle(exam), true);
        assert.equal(page.matchesCycle(computer), true);
        assert.equal(page.matchesCycle(facility), false);
        page.terms = ['2'];
        assert.equal(page.matchesCycle(exam), true);
        assert.equal(page.matchesCycle(computer), true);
        assert.equal(page.matchesCycle({type:'computer',term:1}), false);
        page.installment = 'computer';
        assert.equal(page.matchesCycle(exam), false);
        assert.equal(page.matchesCycle(computer), true);
        page.installment = 'all';
        assert.equal(page.matchesCycle(facility), true);
    }
});

const computerCandidates = [1, 2].map(id => ({ key: `${id}:${id}`, student_id: id, class_id: id,
    name: `Computer student ${id}`, admission: `C0${id}`, class_name: `Class ${id}`,
    installments: ['exam', 'computer'].flatMap((type, typeIndex) => [1, 2, 3].map(term => ({
        key: `${id * 10 + typeIndex}:2026-${String(term * 4 - 3).padStart(2, '0')}-01`,
        type, term, label: `${type} Term ${term}`, balance: type === 'exam' ? 700 : 300,
        paid: 300, revenue: 300, history: 0, pending: 0, blocked_reason: null, fingerprint: `${id}:${type}:${term}`,
        payments: [{ source: 'revenue', id: id * 10 + term, amount: 300 }],
    }))).concat([{ key: `${id * 10 + 2}:2026-01-08`, type: 'facility', label: 'Facility', balance: 5000,
        paid: 5000, revenue: 5000, history: 0, pending: 0, blocked_reason: null, fingerprint: `${id}:facility`, payments: [] }]),
}));

test('computer-only multi-term selection queues exactly those fees for all matching students', () => {
    for (const factory of [bulkClassFees, bulkClassFeeReversals]) {
        const page = factory(computerCandidates);
        page.init();
        page.installment = 'computer';
        page.terms = ['1', '2'];
        assert.equal(page.selectionSummary(), 'Computer fee · Terms 1 & 2');
        assert.equal(page.visible.length, 2);
        page.toggleAll(true);
        page.addSelected();
        assert.equal(page.lines.length, 4);
        assert.equal(page.total, 1200);
        assert.ok(page.lines.every(line => /^computer Term [12]$/.test(line.label)));
        assert.deepEqual([...new Set(page.lines.map(line => line.student_id))], [1, 2]);
        page.toggleAll(true);
        page.addSelected();
        assert.equal(page.lines.length, 4);
        page.terms = ['3'];
        page.toggleAll(true);
        page.addSelected();
        assert.equal(page.lines.length, 6);
        assert.equal(page.total, 1800);
    }
});

test('fee type and term subsets work independently with class, search, no terms and annual fees', () => {
    for (const factory of [bulkClassFees, bulkClassFeeReversals]) {
        const page = factory(computerCandidates, 2);
        page.init();
        page.installment = 'computer';
        page.terms = ['2'];
        page.toggleAll(true);
        page.addSelected();
        assert.equal(page.lines.length, 1);
        assert.equal(page.lines[0].student_id, 2);
        assert.equal(page.lines[0].label, 'computer Term 2');
        page.classId = '';
        page.query = 'C01';
        page.terms = ['1'];
        page.toggleAll(true);
        page.addSelected();
        assert.equal(page.lines.length, 2);
        assert.equal(page.lines[1].label, 'computer Term 1');
        page.selectTerms(false);
        assert.equal(page.visible.length, 0);
        assert.equal(page.selected.length, 0);
        assert.equal(page.lines.length, 2, 'filters must not silently change queued payments');
        page.installment = 'facility';
        assert.equal(page.visible.length, 1);
        assert.equal(page.selectionSummary(), 'Facility fee · Annual fee');
        assert.equal(page.matchesCycle({type:'computer',term:1}), false);
        page.installment = 'all';
        assert.equal(page.matchesCycle({type:'facility'}), true);
        assert.equal(page.matchesCycle({type:'exam',term:1}), false);
        page.selectTerms(true);
        assert.equal(page.matchesCycle({type:'exam',term:4}), true, 'All terms retains legacy quarterly fees');
        page.installment = 'exam';
        page.terms = ['1', '3'];
        assert.equal(page.matchesCycle({type:'exam',term:3}), true);
        assert.equal(page.matchesCycle({type:'exam',term:2}), false);
        assert.equal(page.matchesCycle({type:'computer',term:3}), false);
    }
});

test('adding after narrowing a search never queues students outside the visible selection', () => {
    for (const factory of [bulkClassFees, bulkClassFeeReversals]) {
        const page = factory(computerCandidates);
        page.init();
        page.installment = 'computer';
        page.terms = ['1'];
        page.toggleAll(true);
        page.query = 'C02';
        page.addSelected();
        assert.equal(page.lines.length, 1);
        assert.equal(page.lines[0].student_id, 2);
    }
});

test('standalone bulk forms retain multi-term filtering when an old compiled bundle replaces legacy globals', async () => {
    const { readFile } = await import('node:fs/promises');
    const { runInNewContext } = await import('node:vm');
    const page = {};
    runInNewContext(await readFile(new URL('../../public/js/class-fees.js', import.meta.url), 'utf8'), page);
    page.bulkClassFees = () => ({ installment:'exam' });
    page.bulkClassFeeReversals = () => ({ installment:'exam' });
    for (const factory of [page.schoolFeeForms.payments, page.schoolFeeForms.reversals]) {
        const state = factory(computerCandidates);
        state.init();
        state.installment = 'computer';
        state.terms = ['1', '2'];
        state.toggleAll(true);
        state.addSelected();
        assert.equal(state.lines.length, 4);
        assert.equal(state.total, 1200);
    }
});
