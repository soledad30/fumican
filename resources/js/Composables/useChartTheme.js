/**
 * Opciones ApexCharts legibles según variables CSS del tema activo.
 */
function readToken(name, fallback) {
    const v = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    return v || fallback;
}

export function getChartThemeOptions() {
    const text = readToken('--color-text-base', '#134e4a');
    const muted = readToken('--color-text-muted', '#5f7a76');
    const primary = readToken('--color-primary', '#059669');
    const border = readToken('--color-border', '#a7f3d0');
    const chromeDark = document.documentElement.getAttribute('data-chrome-mode') === 'dark';

    return {
        chart: {
            foreColor: text,
            background: 'transparent',
            toolbar: { tools: { download: false } },
        },
        theme: {
            mode: chromeDark ? 'light' : 'light',
            palette: 'palette1',
        },
        title: {
            style: {
                fontSize: '14px',
                fontWeight: 600,
                color: text,
            },
        },
        subtitle: {
            style: { color: muted },
        },
        xaxis: {
            labels: { style: { colors: muted, fontSize: '11px' } },
            title: { style: { color: text, fontWeight: 500 } },
            axisBorder: { color: border },
            axisTicks: { color: border },
        },
        yaxis: {
            labels: { style: { colors: muted, fontSize: '11px' } },
            title: { style: { color: text, fontWeight: 500 } },
        },
        legend: {
            labels: { colors: text },
            markers: { strokeWidth: 0 },
        },
        grid: {
            borderColor: border,
            strokeDashArray: 3,
        },
        tooltip: {
            theme: 'light',
            style: { fontSize: '12px' },
        },
        colors: [primary, readToken('--color-accent', '#0d9488'), '#3b82f6', '#f97316', '#8b5cf6'],
    };
}

export function mergeChartOptions(custom = {}) {
    const base = getChartThemeOptions();
    return {
        ...base,
        ...custom,
        chart: { ...base.chart, ...(custom.chart || {}) },
        title: { ...base.title, ...(custom.title || {}), style: { ...base.title.style, ...(custom.title?.style || {}) } },
        xaxis: { ...base.xaxis, ...(custom.xaxis || {}), labels: { ...base.xaxis.labels, ...(custom.xaxis?.labels || {}) } },
        yaxis: custom.yaxis ?? base.yaxis,
        legend: { ...base.legend, ...(custom.legend || {}) },
        grid: { ...base.grid, ...(custom.grid || {}) },
    };
}
