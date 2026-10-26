(() => {
    'use strict';
    function feeCollection(config) {
        return {
            query: config.query || '', classId: String(config.classId || ''), year: config.year,
            allYears: config.allYears, includeInactive: config.includeInactive, selectedId: config.studentId || '',
            defaultAmount: config.defaultAmount ?? config.amount ?? '', amount: config.amount ?? '', date: config.date, method: config.method || 'cash',
            recordRevenue: config.recordRevenue !== false, results: [], open: false, loading: false,
            error: '', highlighted: -1, saving: false, viewReceipt: true, requestVersion: 0, searchTimer: null, searchController: null,
            init() { this.methodChanged(); },
            url(studentId = this.selectedId) {
                const url = new URL(config.url);
                url.searchParams.set('quick', config.type);
                url.searchParams.set('year', this.year);
                url.searchParams.set('all_years', this.allYears ? '1' : '0');
                url.searchParams.set('include_inactive', this.includeInactive ? '1' : '0');
                if (this.classId) url.searchParams.set('class_room_id', this.classId);
                if (this.query.trim()) url.searchParams.set('q', this.query.trim());
                if (studentId) url.searchParams.set('student_id', studentId);
                return url;
            },
            queueSearch() {
                clearTimeout(this.searchTimer);
                this.requestVersion++;
                this.searchController?.abort();
                this.results = []; this.open = false; this.error = ''; this.loading = false;
                if (!this.query.trim()) return;
                this.searchTimer = setTimeout(() => this.search(), 250);
            },
            async search() {
                const version = ++this.requestVersion;
                this.searchController?.abort();
                this.searchController = new AbortController();
                if (!this.query.trim()) { this.results = []; this.open = false; this.loading = false; return; }
                this.loading = true; this.error = '';
                const url = this.url(''); url.searchParams.set('suggest', '1');
                try {
                    const response = await fetch(url.toString(), { signal: this.searchController.signal, headers: { Accept: 'application/json' } });
                    if (!response.ok) throw new Error('Search failed');
                    const data = await response.json();
                    if (version !== this.requestVersion) return;
                    this.results = Array.isArray(data.results) ? data.results : [];
                    this.highlighted = -1; this.open = true;
                } catch (error) {
                    if (version === this.requestVersion && error.name !== 'AbortError') {
                        this.results = []; this.open = false; this.error = 'Suggestions could not load. Use Search students to try again.';
                    }
                } finally { if (version === this.requestVersion) this.loading = false; }
            },
            choose(student) { if (student?.id) window.location.assign(this.url(student.id).toString()); },
            keydown(event) {
                if (event.key === 'Escape') { this.open = false; return; }
                if (!this.open || !this.results.length) return;
                if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                    event.preventDefault();
                    const delta = event.key === 'ArrowDown' ? 1 : -1;
                    this.highlighted = Math.max(0, Math.min(this.results.length - 1, this.highlighted + delta));
                } else if (event.key === 'Enter' && (this.highlighted >= 0 || this.results.length === 1)) {
                    event.preventDefault(); this.choose(this.results[Math.max(0, this.highlighted)]);
                }
            },
            classChanged() { window.location.assign(this.url('').toString()); },
            categoryChanged(value) {
                if (config.categoryLocked) return;
                const url = this.url();
                url.searchParams.delete('q');
                if (value.startsWith('fee:')) url.searchParams.set('quick', value.slice(4));
                else { url.searchParams.delete('quick'); url.searchParams.set('category_id', value); }
                window.location.assign(url.toString());
            },
            methodChanged() { if (this.method === 'cheque') this.recordRevenue = true; },
            money(value) { return Number(value || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
        };
    }
    globalThis.feeCollection = feeCollection;
})();
