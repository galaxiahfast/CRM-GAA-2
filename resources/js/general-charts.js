// Gráficas de «Gráficos Generales» (Chart.js). Cada gráfica toma sus colores de las variables del panel, así se ve bien en el
// tema claro y en el oscuro, y se vuelve a dibujar sola al cambiar de tema. Se dibuja cuando su pestaña es visible (con tamaño).
const waitForChart = () => new Promise((resolve) => {
    if (window.Chart) return resolve(window.Chart);
    let tries = 0;
    const timer = setInterval(() => {
        tries++;
        if (window.Chart || tries > 200) {
            clearInterval(timer);
            resolve(window.Chart || null);
        }
    }, 50);
});

// 3725 s => «001h 02m 05s», igual que en el resto de Control de Actividades
const hms = (seconds) => {
    const s = Math.max(0, Math.round(Number(seconds) || 0));
    return `${String(Math.floor(s / 3600)).padStart(3, '0')}h ${String(Math.floor((s % 3600) / 60)).padStart(2, '0')}m ${String(s % 60).padStart(2, '0')}s`;
};

const shorten = (text, max) => (String(text).length > max ? `${String(text).slice(0, max - 1)}…` : String(text));

document.addEventListener('alpine:init', () => {
    window.Alpine.data('generalChart', (config) => ({
        chart: null,
        sizeObserver: null,
        themeObserver: null,
        empty: !(config.values || []).some((value) => value > 0),

        init() {
            this.sizeObserver = new ResizeObserver(() => {
                if (!this.chart && this.$el.clientWidth > 0 && this.$el.clientHeight > 0) this.draw();
            });
            this.sizeObserver.observe(this.$el);
            // Cambio de tema claro/oscuro: se redibuja con los colores nuevos
            this.themeObserver = new MutationObserver(() => {
                if (!this.chart) return;
                this.chart.destroy();
                this.chart = null;
                requestAnimationFrame(() => this.draw());
                setTimeout(() => { if (!this.chart) this.draw(); }, 60);
            });
            this.themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        },

        destroy() {
            this.sizeObserver?.disconnect();
            this.themeObserver?.disconnect();
            this.chart?.destroy();
            this.chart = null;
        },

        colors() {
            const style = getComputedStyle(this.$el);
            const read = (name, fallback) => style.getPropertyValue(name).trim() || fallback;

            return {
                accent: read('--panel-accent', '#1A3A6B'),
                text: read('--panel-text', '#000000'),
                border: read('--panel-border', '#d4d4d8'),
                surface: read('--panel-surface', '#ffffff'),
                green: read('--prod-green', '#15803d'),
                font: style.fontFamily || 'Poppins, sans-serif',
            };
        },

        async draw() {
            const Chart = await waitForChart();
            if (!Chart || this.chart || !this.$refs.canvas || this.$el.clientWidth === 0) return;

            const c = this.colors();
            const font = { family: c.font, size: 15, weight: '400' };
            const tooltip = {
                backgroundColor: c.surface,
                titleColor: c.text,
                bodyColor: c.text,
                borderColor: c.border,
                borderWidth: 1,
                padding: 15,
                titleFont: font,
                bodyFont: font,
                displayColors: false,
            };
            const grid = { color: c.border, drawTicks: false };
            const ticks = { color: c.text, font, padding: 10 };

            if (config.kind === 'day') {
                this.chart = new Chart(this.$refs.canvas, {
                    data: {
                        labels: config.labels,
                        datasets: [
                            {
                                type: 'bar',
                                label: 'Tiempo',
                                data: config.values.map((s) => s / 3600),
                                backgroundColor: c.accent,
                                borderRadius: 6,
                                maxBarThickness: 48,
                                order: 2,
                            },
                            {
                                type: 'line',
                                label: 'Promedio',
                                data: config.values.map(() => config.average / 3600),
                                borderColor: c.green,
                                borderWidth: 2,
                                borderDash: [6, 6],
                                pointRadius: 0,
                                order: 1,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: { duration: 250 },
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                ...tooltip,
                                itemSort: (a, b) => a.datasetIndex - b.datasetIndex,
                                callbacks: {
                                    title: (items) => config.titles?.[items[0].dataIndex] ?? items[0].label,
                                    label: (item) => item.datasetIndex === 0
                                        ? `Tiempo: ${hms(config.values[item.dataIndex])}`
                                        : `Promedio: ${hms(config.average)}`,
                                },
                            },
                        },
                        scales: {
                            x: { grid: { display: false }, border: { color: c.border }, ticks: { ...ticks, maxRotation: 0, autoSkip: true } },
                            y: { beginAtZero: true, grid, border: { display: false }, ticks: { ...ticks, callback: (value) => `${Number(value).toFixed(Number.isInteger(value) ? 0 : 1)}h` } },
                        },
                    },
                });

                return;
            }

            // Ranking por cliente o por actividad: barras horizontales con el tiempo y el porcentaje del total
            const total = config.total || 0;
            this.chart = new Chart(this.$refs.canvas, {
                type: 'bar',
                data: {
                    labels: config.labels,
                    datasets: [{
                        label: 'Tiempo',
                        data: config.values.map((s) => s / 3600),
                        backgroundColor: c.accent,
                        borderRadius: 6,
                        maxBarThickness: 32,
                    }],
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 250 },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            ...tooltip,
                            callbacks: {
                                label: (item) => {
                                    const seconds = config.values[item.dataIndex];
                                    const pct = total > 0 ? Math.round((seconds * 100) / total) : 0;
                                    return `${hms(seconds)} · ${pct}%`;
                                },
                            },
                        },
                    },
                    scales: {
                        x: { beginAtZero: true, grid, border: { display: false }, ticks: { ...ticks, callback: (value) => `${Number(value).toFixed(Number.isInteger(value) ? 0 : 1)}h` } },
                        y: { grid: { display: false }, border: { color: c.border }, ticks: { ...ticks, autoSkip: false, callback(value) { return shorten(this.getLabelForValue(value), 28); } } },
                    },
                },
            });
        },
    }));
});
