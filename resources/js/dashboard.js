import Chart from 'chart.js/auto';

function readDashboardData() {
    const el = document.getElementById('dashboard-data');
    if (!el) return null;
    try {
        return JSON.parse(el.textContent || '{}');
    } catch {
        return null;
    }
}

function makeSparkline(ctx, labels, data) {
    return new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    data,
                    borderWidth: 2,
                    pointRadius: 0,
                    tension: 0.35,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { enabled: true } },
            scales: {
                x: { display: false },
                y: { display: false },
            },
        },
    });
}

function hasAnyValue(arr) {
    if (!Array.isArray(arr) || arr.length === 0) return false;
    return arr.some((v) => {
        const n = Number(v);
        return Number.isFinite(n) && n !== 0;
    });
}

function showEmptyState(canvasEl, message) {
    if (!canvasEl) return;
    const container = canvasEl.parentElement;
    if (!container) return;
    container.innerHTML = `<div class="flex h-full items-center justify-center text-sm text-gray-500">${message}</div>`;
}

export function initDashboardCharts() {
    const data = readDashboardData();
    if (!data) return;

    const cashFlowCanvas = document.getElementById('cashflowChart');
    if (cashFlowCanvas) {
        const labels = data?.cashFlow?.labels || [];
        const series = data?.cashFlow?.data || [];
        if (!Array.isArray(labels) || labels.length === 0 || !hasAnyValue(series)) {
            showEmptyState(cashFlowCanvas, 'No cash flow data for the selected range.');
        } else {
            makeSparkline(cashFlowCanvas.getContext('2d'), labels, series);
        }
    }

    const monthlyCanvas = document.getElementById('monthlyBarChart');
    if (monthlyCanvas) {
        const labels = data?.monthly?.labels || [];
        const revenue = data?.monthly?.revenue || [];
        const expense = data?.monthly?.expense || [];

        if (!Array.isArray(labels) || labels.length === 0 || (!hasAnyValue(revenue) && !hasAnyValue(expense))) {
            showEmptyState(monthlyCanvas, 'No revenue/expense data for the selected range.');
        } else {
            new Chart(monthlyCanvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels,
                    datasets: [
                        { label: 'Revenue', data: revenue },
                        { label: 'Expense', data: expense },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } },
                },
            });
        }
    }

    const revDonut = document.getElementById('revDonut');
    if (revDonut) {
        const labels = data?.revCats?.labels || [];
        const series = data?.revCats?.data || [];
        if (!Array.isArray(labels) || labels.length === 0 || !hasAnyValue(series)) {
            showEmptyState(revDonut, 'No revenue breakdown data for the selected range.');
        } else {
            new Chart(revDonut.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels,
                    datasets: [{ data: series }],
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
            });
        }
    }

    const expDonut = document.getElementById('expDonut');
    if (expDonut) {
        const labels = data?.expCats?.labels || [];
        const series = data?.expCats?.data || [];
        if (!Array.isArray(labels) || labels.length === 0 || !hasAnyValue(series)) {
            showEmptyState(expDonut, 'No expense breakdown data for the selected range.');
        } else {
            new Chart(expDonut.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels,
                    datasets: [{ data: series }],
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
            });
        }
    }

    const enrollLine = document.getElementById('enrollmentLine');
    if (enrollLine) {
        const labels = data?.enroll?.labels || [];
        const series = data?.enroll?.data || [];
        if (!Array.isArray(labels) || labels.length === 0 || !hasAnyValue(series)) {
            showEmptyState(enrollLine, 'No enrollment trend data available.');
        } else {
            new Chart(enrollLine.getContext('2d'), {
                type: 'line',
                data: {
                    labels,
                    datasets: [{ label: 'Enrollments', data: series, tension: 0.35 }],
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
            });
        }
    }
}
