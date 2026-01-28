export function teacherPicker({ lookupUrl, initialTeacherId, initialVisitingTeacherId }) {
    return {
        lookupUrl,
        query: '',
        open: false,
        loading: false,
        results: [],
        teacherId: '',
        visitingTeacherId: '',

        async init() {
            if (initialTeacherId) {
                await this.loadSelected('teacher', initialTeacherId);
            } else if (initialVisitingTeacherId) {
                await this.loadSelected('visiting', initialVisitingTeacherId);
            }
        },

        async loadSelected(type, id) {
            try {
                const res = await fetch(`${this.lookupUrl}?type=${encodeURIComponent(type)}&id=${encodeURIComponent(id)}`, {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                    },
                });
                if (!res.ok) return;
                const item = await res.json();
                this.select(item, false);
            } catch (e) {
                // ignore
            }
        },

        clear() {
            this.teacherId = '';
            this.visitingTeacherId = '';
            this.query = '';
            this.results = [];
            this.open = false;
        },

        select(item, close = true) {
            if (!item) return;
            this.query = item.label || item.name || '';
            this.teacherId = item.type === 'teacher' ? item.id : '';
            this.visitingTeacherId = item.type === 'visiting' ? item.id : '';
            this.results = [];
            if (close) this.open = false;
        },

        async search() {
            const q = (this.query || '').trim();
            if (q.length < 1) {
                this.results = [];
                return;
            }
            this.loading = true;
            this.open = true;
            try {
                const res = await fetch(`${this.lookupUrl}?q=${encodeURIComponent(q)}`, {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                    },
                });
                const data = res.ok ? await res.json() : [];
                this.results = Array.isArray(data) ? data : [];
            } catch (e) {
                this.results = [];
            } finally {
                this.loading = false;
            }
        },
    };
}
