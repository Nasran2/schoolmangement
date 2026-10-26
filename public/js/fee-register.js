(() => {
'use strict';

// Independent of the compiled bundle: shared-hosting uploads need no asset build.
function feeRegisterRequests(fetcher) {
    let controller = null;
    let revision = 0;
    return {
        cancel() { revision++; controller?.abort(); },
        async load(url) {
            controller?.abort();
            controller = new AbortController();
            const current = ++revision;
            try {
                const response = await fetcher(url, { signal: controller.signal, headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (!response.ok) throw new Error('The student list could not be refreshed.');
                const result = await response.json();
                return current === revision ? result : null;
            } catch (error) {
                if (current !== revision || error.name === 'AbortError') return null;
                throw error;
            }
        },
    };
}
function paymentMethodState(method, previousMethod, preference) {
    return { forceRevenue: method === 'cheque', restorePreference: previousMethod === 'cheque' && method !== 'cheque', preference };
}

globalThis.feeRegisterRequests = feeRegisterRequests;
globalThis.feeRegisterPaymentMethodState = paymentMethodState;

function start(root) {
    const form = root.querySelector('[data-register-filters]');
    const results = root.querySelector('[data-register-results]');
    const status = root.querySelector('[data-register-status]');
    const notice = root.querySelector('[data-register-notice]');
    const dialog = root.querySelector('[data-register-dialog]');
    const panel = root.querySelector('[data-register-panel]');
    const title = root.querySelector('#fee-register-dialog-title');
    const requests = feeRegisterRequests((...args) => fetch(...args));
    let timer = null, failed = false, saving = false, panelController = null, panelRevision = 0;
    function query(page = 1) {
        const url = new URL(form.action, window.location.href);
        url.search = new URLSearchParams(new FormData(form)).toString();
        if (page > 1) url.searchParams.set('page', String(page));
        return url;
    }
    function announce(message, receiptUrl = null) {
        notice.replaceChildren(document.createTextNode(message));
        if (receiptUrl) {
            const link = document.createElement('a');
            link.href = receiptUrl; link.textContent = ' View / print bill'; link.className = 'underline font-semibold';
            link.target = '_blank'; link.rel = 'noopener'; notice.append(link);
        }
        notice.hidden = false;
    }
    async function refresh(page = 1, scrollToResults = false) {
        clearTimeout(timer);
        const url = query(page); url.searchParams.set('fragment', '1');
        results.setAttribute('aria-busy', 'true'); status.textContent = 'Searching students…';
        try {
            const data = await requests.load(url.toString());
            if (!data) return;
            results.innerHTML = data.html;
            const scope = form.querySelector('[name="selection_scope"]');
            if (scope.value !== data.scope) {
                form.querySelector('[data-register-terms]').innerHTML = data.terms_html;
                scope.value = data.scope;
            }
            history.replaceState(null, '', data.url);
            status.textContent = `${data.count} students found.`; failed = false;
            results.removeAttribute('aria-busy');
            if (scrollToResults) results.scrollIntoView({block:'start', behavior:'smooth'});
        } catch (error) {
            failed = true; status.textContent = 'Could not refresh the list. Use Apply filters to reload the page.';
            results.removeAttribute('aria-busy');
        }
    }
    form.addEventListener('input', event => {
        if (event.target.name === 'q') { requests.cancel(); clearTimeout(timer); timer = setTimeout(() => refresh(), 220); }
    });
    form.addEventListener('change', event => { if (event.target.name !== 'q') refresh(); });
    form.addEventListener('click', event => {
        const control = event.target.closest('[data-exam-select]');
        if (!control) return;
        form.querySelectorAll('[name="exam_dates[]"]').forEach(input => { input.checked = control.dataset.examSelect === 'all'; });
        refresh();
    });
    form.addEventListener('submit', event => {
        const button = event.submitter;
        if (button?.name === 'pdf' || button?.name === 'download') {
            clearTimeout(timer); requests.cancel(); results.removeAttribute('aria-busy'); status.textContent = 'Download requested for the selected filters.';
            return;
        }
        if (failed) return; // A regular GET remains available if live search fails.
        event.preventDefault(); refresh();
    });
    root.addEventListener('click', async event => {
        const close = event.target.closest('[data-register-close]');
        if (close) { if (!saving) { panelRevision++; panelController?.abort(); dialog.close(); } return; }
        const page = event.target.closest('[data-register-page]');
        if (page) { event.preventDefault(); await refresh(Number(new URL(page.href).searchParams.get('page') || 1), true); return; }
        const action = event.target.closest('[data-register-action]');
        if (!action) return;
        event.preventDefault(); panelController?.abort(); panelController = new AbortController();
        const revision = ++panelRevision;
        title.textContent = action.dataset.registerAction === 'pay' ? 'Collect payment' : 'Make fee unpaid';
        panel.textContent = 'Loading fee details…'; dialog.showModal();
        const url = new URL(action.href); url.hash = ''; url.searchParams.set('view', 'students'); url.searchParams.set('action_panel', action.dataset.registerAction);
        try {
            const response = await fetch(url, {signal: panelController.signal, headers: {Accept:'text/html','X-Requested-With':'XMLHttpRequest'}});
            if (!response.ok) throw new Error('Could not load the fee details. Close this dialog and reload the list.');
            const html = await response.text();
            if (revision !== panelRevision) return;
            panel.innerHTML = html;
            const paymentForm = panel.querySelector('[data-register-payment]');
            if (paymentForm) setupPayment(paymentForm);
        } catch (error) { if (error.name !== 'AbortError' && revision === panelRevision) panel.textContent = error.message; }
    });
    dialog.addEventListener('cancel', event => { if (saving) event.preventDefault(); else { panelRevision++; panelController?.abort(); } });
    function setupPayment(paymentForm) {
        const method = paymentForm.querySelector('[data-register-method]');
        const revenue = paymentForm.querySelector('[data-register-revenue]');
        const revenueValue = paymentForm.querySelector('[data-register-revenue-value]');
        let previousMethod = method?.value, preference = revenue?.checked;
        const updateMethod = () => {
            const state = paymentMethodState(method.value, previousMethod, preference);
            paymentForm.querySelectorAll('[data-register-method-fields]').forEach(group => {
                const enabled = group.dataset.registerMethodFields === method.value;
                group.hidden = !enabled;
                group.querySelectorAll('input').forEach(input => { input.disabled = !enabled; input.required = enabled && input.hasAttribute('data-method-required'); });
            });
            if (state.forceRevenue) { if (previousMethod !== 'cheque') preference = revenue.checked; revenue.checked = true; }
            else if (state.restorePreference) revenue.checked = preference;
            revenue.disabled = state.forceRevenue;
            revenueValue.value = revenue.checked ? '1' : '0'; previousMethod = method.value;
        };
        method?.addEventListener('change', updateMethod);
        revenue?.addEventListener('change', () => { preference = revenue.checked; revenueValue.value = revenue.checked ? '1' : '0'; });
        if (method) updateMethod();
        paymentForm.addEventListener('submit', async event => {
            event.preventDefault(); if (saving) return;
            const data = new FormData(paymentForm);
            for (const [name, value] of new FormData(form)) data.append(`return_filters[${name.endsWith('[]') ? name.slice(0,-2) : name}]${name.endsWith('[]') ? '[]' : ''}`, value);
            const errors = paymentForm.querySelector('[data-register-form-errors]'); errors.hidden = true;
            saving = true; dialog.querySelectorAll('button').forEach(button => { button.disabled = true; });
            try {
                const response = await fetch(paymentForm.action, {method:'POST',body:data,headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}});
                const result = await response.json();
                if (!response.ok) throw new Error(result.errors ? Object.values(result.errors).flat().join(' ') : (result.message || 'The payment could not be saved.'));
                dialog.close(); announce(result.message, result.receipt_url); await refresh(Number(new URL(window.location.href).searchParams.get('page') || 1));
            } catch (error) { errors.textContent = error.message || 'Could not confirm the result. Retry the same form to avoid duplicate payments.'; errors.hidden = false; }
            finally { saving = false; dialog.querySelectorAll('button').forEach(button => { button.disabled = false; }); }
        });
    }
    window.addEventListener('popstate', () => window.location.reload());
}
if (typeof document !== 'undefined') document.querySelectorAll('[data-fee-register]').forEach(start);
})();
