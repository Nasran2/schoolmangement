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

export function initDashboardCharts() {
    const data = readDashboardData();
    if (!data) return;

    const cashFlowCanvas = document.getElementById('cashflowChart');
    if (cashFlowCanvas) {
        makeSparkline(cashFlowCanvas.getContext('2d'), data.cashFlow.labels, data.cashFlow.data);
    }

    const monthlyCanvas = document.getElementById('monthlyBarChart');
    if (monthlyCanvas) {
        new Chart(monthlyCanvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: data.monthly.labels,
                datasets: [
                    { label: 'Revenue', data: data.monthly.revenue },
                    { label: 'Expense', data: data.monthly.expense },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
            },
        });
    }

    const revDonut = document.getElementById('revDonut');
    if (revDonut) {
        new Chart(revDonut.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: data.revCats.labels,
                datasets: [{ data: data.revCats.data }],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
        });
    }

    const expDonut = document.getElementById('expDonut');
    if (expDonut) {
        new Chart(expDonut.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: data.expCats.labels,
                datasets: [{ data: data.expCats.data }],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
        });
    }

    const enrollLine = document.getElementById('enrollmentLine');
    if (enrollLine) {
        new Chart(enrollLine.getContext('2d'), {
            type: 'line',
            data: {
                labels: data.enroll.labels,
                datasets: [{ label: 'Enrollments', data: data.enroll.data, tension: 0.35 }],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
        });
    }
}
