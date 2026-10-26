// This file also runs directly from Blade before Alpine initializes. Keep it
// independent of the Vite bundle so PHP-only deployments cannot break fee forms.
(() => {
'use strict';

// UTC arithmetic keeps calendar dates stable across time zones and daylight saving.
function termCalendar(initialTerms = null, firstDate = '') {
    const parse = value => new Date(`${value}T00:00:00Z`);
    const format = date => date.toISOString().slice(0, 10);
    const addMonths = (value, months) => {
        const date = parse(value);
        const day = date.getUTCDate();
        date.setUTCDate(1);
        date.setUTCMonth(date.getUTCMonth() + months);
        const last = new Date(Date.UTC(date.getUTCFullYear(), date.getUTCMonth() + 1, 0)).getUTCDate();
        date.setUTCDate(Math.min(day, last));
        return format(date);
    };
    const nextDay = value => { const date = parse(value); date.setUTCDate(date.getUTCDate() + 1); return format(date); };
    const previousDay = value => { const date = parse(value); date.setUTCDate(date.getUTCDate() - 1); return format(date); };
    return {
        terms: initialTerms || [{ start: firstDate, end: '' }, { start: '', end: '' }, { start: '', end: '' }],
        init() { if (!initialTerms && firstDate) this.firstStartChanged(); },
        firstStartChanged() {
            if (!/^\d{4}-\d{2}-\d{2}$/.test(this.terms[0].start)) return;
            this.terms[0].end = previousDay(addMonths(this.terms[0].start, 4));
            this.fillRemaining();
        },
        fillRemaining() {
            if (!/^\d{4}-\d{2}-\d{2}$/.test(this.terms[0].end)) return;
            this.terms[1].start = nextDay(this.terms[0].end);
            this.terms[1].end = previousDay(addMonths(this.terms[1].start, 4));
            this.terms[2].start = addMonths(this.terms[1].start, 4);
            this.terms[2].end = previousDay(addMonths(this.terms[1].start, 8));
        },
    };
}

function bulkClassFees(candidates, initialClass = '', previous = []) {
    return {
        candidates, classId: String(initialClass || ''), query: '', installment: 'exam', selected: [],
        lines: [], recordRevenue: true, saving: false, message: '',
        init() {
            for (const old of previous || []) {
                const candidate = candidates.find(c => c.student_id == old.student_id && c.class_id == old.class_room_id);
                const cycle = candidate?.installments.find(c => c.key === old.cycle);
                if (candidate && cycle) this.lines.push(this.makeLine(candidate, cycle, old));
                else this.message = 'Some earlier selections are no longer available. Review the list before saving.';
            }
        },
        matchesCycle(cycle) {
            if (this.installment === 'all') return true;
            if (this.installment.startsWith('term:')) return cycle.type === 'exam' && cycle.term === Number(this.installment.split(':')[1]);
            return cycle.type === this.installment;
        },
        get visible() {
            const needle = this.query.trim().toLowerCase();
            return this.candidates.filter(c => (!this.classId || c.class_id == this.classId)
                && (!needle || `${c.name} ${c.admission}`.toLowerCase().includes(needle))
                && c.installments.some(i => this.matchesCycle(i)));
        },
        get allSelected() { return this.visible.length > 0 && this.visible.every(c => this.selected.includes(c.key)); },
        toggleAll(checked) {
            const keys = this.visible.map(c => c.key);
            this.selected = checked ? [...new Set([...this.selected, ...keys])] : this.selected.filter(k => !keys.includes(k));
        },
        makeLine(student, cycle, old = {}) {
            return { key: `${student.student_id}:${cycle.key}`, student_id: student.student_id,
                class_room_id: student.class_id, name: student.name, admission: student.admission,
                class_name: student.class_name, cycle: cycle.key, label: cycle.label, balance: cycle.balance,
                amount: old.amount ?? cycle.balance, record_revenue: old.record_revenue === undefined ? this.recordRevenue : Boolean(Number(old.record_revenue)) };
        },
        addSelected() {
            this.message = '';
            for (const student of this.candidates.filter(c => this.selected.includes(c.key))) {
                for (const cycle of student.installments.filter(c => this.matchesCycle(c))) {
                    const line = this.makeLine(student, cycle);
                    if (this.lines.some(existing => existing.key === line.key)) continue;
                    if (this.lines.length >= 500) { this.message = 'A batch supports up to 500 installment payments. Save this list before adding more.'; return; }
                    this.lines.push(line);
                }
            }
            this.selected = [];
        },
        applyRevenue() { this.lines.forEach(line => { line.record_revenue = this.recordRevenue; }); },
        money(amount) { return Number(amount || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
        get total() { return this.lines.reduce((sum, line) => sum + Number(line.amount || 0), 0); },
        get income() { return this.lines.filter(line => line.record_revenue).reduce((sum, line) => sum + Number(line.amount || 0), 0); },
    };
}

function bulkClassFeeReversals(candidates, initialClass = '', previous = []) {
    return {
        candidates, classId: String(initialClass || ''), query: '', installment: 'exam', selected: [],
        lines: [], saving: false, message: '',
        init() {
            for (const old of previous || []) {
                const student = this.candidates.find(c => c.student_id == old.student_id && c.class_id == old.class_room_id);
                const cycle = student?.installments.find(c => c.key === old.cycle);
                if (cycle && !cycle.blocked_reason && cycle.fingerprint === old.fingerprint) {
                    const line = this.makeLine(student, cycle);
                    if (!this.lines.some(existing => existing.key === line.key)) this.lines.push(line);
                } else this.message = 'Some selected fees changed or need individual review. Select them again after reviewing the current payments.';
            }
        },
        matchesCycle(cycle) {
            if (this.installment === 'all') return true;
            if (this.installment.startsWith('term:')) return cycle.type === 'exam' && cycle.term === Number(this.installment.split(':')[1]);
            return cycle.type === this.installment;
        },
        matching(student) { return student.installments.filter(cycle => this.matchesCycle(cycle)); },
        canSelect(student) { return this.matching(student).some(cycle => !cycle.blocked_reason); },
        get visible() {
            const needle = this.query.trim().toLowerCase();
            return this.candidates.filter(student => (!this.classId || student.class_id == this.classId)
                && (!needle || `${student.name} ${student.admission}`.toLowerCase().includes(needle))
                && this.matching(student).length > 0);
        },
        get selectable() { return this.visible.filter(student => this.canSelect(student)); },
        get allSelected() { return this.selectable.length > 0 && this.selectable.every(student => this.selected.includes(student.key)); },
        toggleAll(checked) {
            const keys = this.selectable.map(student => student.key);
            this.selected = checked ? [...new Set([...this.selected, ...keys])] : this.selected.filter(key => !keys.includes(key));
        },
        makeLine(student, cycle) {
            return { key: `${student.student_id}:${cycle.key}`, student_id: student.student_id, class_room_id: student.class_id,
                name: student.name, admission: student.admission, class_name: student.class_name, cycle: cycle.key,
                label: cycle.label, fingerprint: cycle.fingerprint, paid: cycle.paid, revenue: cycle.revenue,
                history: cycle.history, pending: cycle.pending, payments: cycle.payments };
        },
        addSelected() {
            this.message = '';
            for (const student of this.candidates.filter(candidate => this.selected.includes(candidate.key))) {
                for (const cycle of this.matching(student)) {
                    if (cycle.blocked_reason) { this.message = 'Fees with refunds, waivers or unresolved cheque states need individual review and were not added.'; continue; }
                    const line = this.makeLine(student, cycle);
                    if (this.lines.some(existing => existing.key === line.key)) continue;
                    if (this.lines.length >= 500) { this.message = 'A reversal batch supports up to 500 student fees. Save or clear this list before adding more.'; return; }
                    this.lines.push(line);
                }
            }
            this.selected = [];
        },
        payload() { return JSON.stringify(this.lines.map(line => ({ student_id: line.student_id, class_room_id: line.class_room_id, cycle: line.cycle, fingerprint: line.fingerprint }))); },
        money(amount) { return Number(amount || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
        get total() { return this.lines.reduce((sum, line) => sum + Number(line.paid || 0), 0); },
        get income() { return this.lines.reduce((sum, line) => sum + Number(line.revenue || 0), 0); },
        get pending() { return this.lines.reduce((sum, line) => sum + Number(line.pending || 0), 0); },
        get receiptCount() { return this.lines.reduce((sum, line) => sum + line.payments.length, 0); },
    };
}

    globalThis.termCalendar = termCalendar;
    globalThis.bulkClassFees = bulkClassFees;
    globalThis.bulkClassFeeReversals = bulkClassFeeReversals;
})();
