export function teacherEvaluationForm(state) {
    return {
        ...state,
        saving: false,
        pendingStatus: state.pendingStatus || 'draft',
        missingDetails: state.initialMissingDetails || [],
        returnFocus: null,
        init() {
            if (this.missingDetails.length) this.$nextTick(() => this.openIncompletePrompt());
        },
        collectMissingDetails(form) {
            const data = new FormData(form);
            const filled = value => value != null && String(value).trim() !== '';
            const value = name => data.get(name);
            const missing = [];
            const checklist = this.checklist;
            for (const [field, label] of Object.entries(checklist.fields)) {
                if (!filled(value(field))) missing.push(label);
            }
            if (checklist.annual) {
                let started = false;
                for (const supervisor of [1, 2, 3]) {
                    const ratings = this.ratingValues(supervisor);
                    if (!checklist.criteria.some(criterion => filled(ratings[criterion.id]))) continue;
                    started = true;
                    for (const criterion of checklist.criteria) {
                        if (!filled(ratings[criterion.id])) missing.push(`Supervisor ${supervisor}: ${criterion.area} rating`);
                    }
                }
                if (!started) missing.push('Annual performance ratings (no ratings entered)');
            }
            for (const [group, settings] of Object.entries(checklist.rows)) {
                const entries = this[group].filter(entry => Object.values(entry).some(filled));
                if (group === 'evidence' && !checklist.annual && !entries.length) missing.push('Evidence and examples (no entries added)');
                entries.forEach((entry, index) => {
                    for (const [field, label] of Object.entries(settings.fields)) {
                        if (!filled(entry[field])) missing.push(`${settings.label} ${index + 1}: ${label}`);
                    }
                });
            }
            for (const pair of checklist.pairs) {
                if (filled(value(pair.name)) && !filled(value(pair.date))) missing.push(`${pair.label} date`);
                else if (filled(value(pair.date)) && !filled(value(pair.name))) missing.push(`${pair.label} name`);
            }
            if (filled(value('follow_up_completed_date')) && !filled(value('follow_up_notes'))) missing.push('Follow-up review notes');
            return missing;
        },
        reviewBeforeSave(event) {
            if (this.saving) { event.preventDefault(); return; }
            this.pendingStatus = event.submitter?.value || this.pendingStatus;
            this.$refs.pendingStatus.value = this.pendingStatus;
            this.$refs.acknowledgeInput.value = '0';
            this.returnFocus = event.submitter;
            this.missingDetails = this.collectMissingDetails(event.currentTarget);
            if (this.missingDetails.length) {
                event.preventDefault();
                this.openIncompletePrompt();
            } else this.saving = true;
        },
        openIncompletePrompt() {
            if (!this.$refs.incompleteDialog.open) this.$refs.incompleteDialog.showModal();
            this.$nextTick(() => this.$refs.returnToForm.focus());
        },
        closeIncompletePrompt() {
            this.$refs.incompleteDialog.close();
            this.returnFocus?.focus();
        },
        confirmIncompleteSave() {
            const form = this.$refs.evaluationForm;
            if (!form.checkValidity()) {
                this.closeIncompletePrompt();
                form.reportValidity();
                return;
            }
            if (this.saving) return;
            this.saving = true;
            this.$refs.pendingStatus.value = this.pendingStatus;
            this.$refs.acknowledgeInput.value = '1';
            HTMLFormElement.prototype.submit.call(form);
        },
        ratingValues(supervisor) {
            return supervisor === 1 ? this.ratings : this.additionalRatings[supervisor];
        },
        ratedCount(supervisor) {
            const values = this.ratingValues(supervisor);
            return Object.keys(this.weights).filter(id => values[id] !== '' && values[id] != null).length;
        },
        supervisorScore(supervisor) {
            const ids = Object.keys(this.weights);
            if (!ids.length || this.ratedCount(supervisor) !== ids.length) return null;
            const values = this.ratingValues(supervisor);
            return Math.round(ids.reduce((sum, id) => sum + this.weights[id] * Number(values[id]) / 5, 0) * 100) / 100;
        },
        completedCount() {
            return [1, 2, 3].filter(supervisor => this.supervisorScore(supervisor) !== null).length;
        },
        overallScore() {
            const scores = [1, 2, 3].map(supervisor => this.supervisorScore(supervisor)).filter(score => score !== null);
            return scores.length ? (scores.reduce((sum, score) => sum + score, 0) / scores.length).toFixed(2) + ' / 100' : 'Awaiting a completed supervisor review';
        },
        clearSupervisor(supervisor) {
            const values = this.ratingValues(supervisor);
            Object.keys(this.weights).forEach(id => { values[id] = ''; });
        },
    };
}
