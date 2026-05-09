(function () {
    'use strict';

    const palette = {
        blue: '#2563eb',
        sky: '#0891b2',
        green: '#059669',
        amber: '#d97706',
        red: '#dc2626',
        violet: '#7c3aed',
        slate: '#475569',
        grid: '#e2e8f0',
        text: '#334155'
    };

    const chartColors = [
        palette.blue,
        palette.green,
        palette.amber,
        palette.red,
        palette.violet,
        palette.sky,
        palette.slate
    ];

    Chart.defaults.font.family = "'Inter', 'Segoe UI', Arial, sans-serif";
    Chart.defaults.color = palette.text;
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.tooltip.backgroundColor = '#0f172a';
    Chart.defaults.plugins.tooltip.padding = 12;

    function chartPanel(id) {
        const canvas = document.getElementById(id);
        if (!canvas) return null;
        return {
            canvas,
            card: canvas.closest('.chart-card'),
            loading: document.querySelector(`[data-chart-loading="${id}"]`),
            empty: document.querySelector(`[data-chart-empty="${id}"]`),
            error: document.querySelector(`[data-chart-error="${id}"]`)
        };
    }

    function setState(panel, state) {
        if (!panel) return;
        panel.canvas.hidden = state !== 'ready';
        if (panel.loading) panel.loading.hidden = state !== 'loading';
        if (panel.empty) panel.empty.hidden = state !== 'empty';
        if (panel.error) panel.error.hidden = state !== 'error';
    }

    function valuesTotal(rows, valueKey = 'value') {
        return rows.reduce((total, row) => total + Number(row[valueKey] || 0), 0);
    }

    function makeBar(id, rows, labelKey, valueKey, label, color) {
        const panel = chartPanel(id);
        if (!panel) return null;
        if (!rows.length || valuesTotal(rows, valueKey) === 0) {
            setState(panel, 'empty');
            return null;
        }
        setState(panel, 'ready');
        return new Chart(panel.canvas, {
            type: 'bar',
            data: {
                labels: rows.map((row) => row[labelKey]),
                datasets: [{
                    label,
                    data: rows.map((row) => Number(row[valueKey] || 0)),
                    backgroundColor: color || palette.blue,
                    borderRadius: 8,
                    maxBarThickness: 44
                }]
            },
            options: chartOptions({ indexAxis: rows.length > 6 ? 'y' : 'x' })
        });
    }

    function makeTechnicianBar(id, rows) {
        const panel = chartPanel(id);
        if (!panel) return null;
        if (!rows.length || rows.every((row) => Number(row.assigned || 0) === 0 && Number(row.completed || 0) === 0)) {
            setState(panel, 'empty');
            return null;
        }
        setState(panel, 'ready');
        return new Chart(panel.canvas, {
            type: 'bar',
            data: {
                labels: rows.map((row) => row.name),
                datasets: [
                    {
                        label: 'Assigned',
                        data: rows.map((row) => Number(row.assigned || 0)),
                        backgroundColor: palette.sky,
                        borderRadius: 8,
                        maxBarThickness: 38
                    },
                    {
                        label: 'Completed',
                        data: rows.map((row) => Number(row.completed || 0)),
                        backgroundColor: palette.green,
                        borderRadius: 8,
                        maxBarThickness: 38
                    }
                ]
            },
            options: chartOptions()
        });
    }

    function makeLine(id, rows) {
        const panel = chartPanel(id);
        if (!panel) return null;
        if (!rows.length || valuesTotal(rows, 'count') === 0) {
            setState(panel, 'empty');
            return null;
        }
        setState(panel, 'ready');
        return new Chart(panel.canvas, {
            type: 'line',
            data: {
                labels: rows.map((row) => row.date),
                datasets: [
                    {
                        label: 'Submitted',
                        data: rows.map((row) => Number(row.count || 0)),
                        borderColor: palette.blue,
                        backgroundColor: 'rgba(37, 99, 235, 0.14)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 3,
                        pointHoverRadius: 5
                    },
                    {
                        label: 'Completed',
                        data: rows.map((row) => Number(row.completed || 0)),
                        borderColor: palette.green,
                        backgroundColor: 'rgba(5, 150, 105, 0.08)',
                        fill: false,
                        tension: 0.35,
                        pointRadius: 3,
                        pointHoverRadius: 5
                    }
                ]
            },
            options: chartOptions()
        });
    }

    function makeDoughnut(id, rows) {
        const panel = chartPanel(id);
        if (!panel) return null;
        if (!rows.length || valuesTotal(rows) === 0) {
            setState(panel, 'empty');
            return null;
        }
        setState(panel, 'ready');
        return new Chart(panel.canvas, {
            type: 'doughnut',
            data: {
                labels: rows.map((row) => row.label),
                datasets: [{
                    data: rows.map((row) => Number(row.value || 0)),
                    backgroundColor: chartColors,
                    borderColor: '#ffffff',
                    borderWidth: 3,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '64%',
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    function chartOptions(extra = {}) {
        return Object.assign({
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: { position: 'bottom' }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { maxRotation: 0, autoSkip: true }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: palette.grid },
                    ticks: { precision: 0 }
                }
            }
        }, extra);
    }

    function setAllLoading() {
        document.querySelectorAll('.chart-card canvas').forEach((canvas) => {
            setState(chartPanel(canvas.id), 'loading');
        });
    }

    function setAllError() {
        document.querySelectorAll('.chart-card canvas').forEach((canvas) => {
            setState(chartPanel(canvas.id), 'error');
        });
    }

    async function fetchJson(url) {
        const response = await fetch(url, {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        });
        const payload = await response.json();
        if (!response.ok || !payload.success) {
            throw new Error(payload.error || 'Unable to load chart data.');
        }
        return payload.data;
    }

    function renderDashboard(data) {
        makeLine('dashboardTrendChart', data.series.requests_over_time);
        makeDoughnut('dashboardStatusChart', data.series.by_status);
        makeBar('dashboardCategoryChart', data.series.by_category, 'label', 'value', 'Requests', palette.blue);
    }

    function renderReports(data) {
        makeLine('reportsTrendChart', data.series.requests_over_time);
        makeDoughnut('reportsStatusChart', data.series.by_status);
        makeBar('reportsCategoryChart', data.series.by_category, 'label', 'value', 'Requests', palette.blue);
        makeBar('reportsLocationChart', data.series.by_location, 'label', 'value', 'Requests', palette.sky);
        makeBar('reportsFeedbackChart', data.series.feedback_ratings, 'label', 'value', 'Feedback', palette.amber);
        makeTechnicianBar('reportsTechnicianChart', data.tables.technician_performance || []);
    }

    async function init() {
        if (typeof Chart === 'undefined') {
            setAllError();
            return;
        }

        const root = document.querySelector('[data-chart-page]');
        if (!root) return;

        setAllLoading();

        try {
            const data = await fetchJson(root.dataset.chartApi);
            if (root.dataset.chartPage === 'dashboard') {
                renderDashboard(data);
            } else if (root.dataset.chartPage === 'reports') {
                renderReports(data);
            }
        } catch (error) {
            console.error(error);
            setAllError();
        }
    }

    document.addEventListener('DOMContentLoaded', init);
})();
