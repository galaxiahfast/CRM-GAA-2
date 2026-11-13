// Gráficas de «Gráficos Generales» (Chart.js). Cada gráfica toma sus colores de las variables del panel, así se ve bien en el
// tema claro y en el oscuro, y se vuelve a dibujar sola al cambiar de tema. Se dibuja cuando su pestaña es visible (con tamaño).
// Sin tooltip al pasar el cursor: solo se resalta la barra tocada (ver attachManualHover más abajo).
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

const shorten = (text, max) => (String(text).length > max ? `${String(text).slice(0, max - 1)}…` : String(text));

// ===== Cifras de ancho fijo en los ejes, igual que en las tablas del resto de la app (la letra Poppins no es
// tabular, así que sin esto los números del eje no quedan alineados). Chart.js dibuja el texto de los ejes en el
// lienzo (no con HTML), así que cada dígito se mide y se dibuja con una fuente monoespaciada (todas sus cifras
// miden lo mismo de verdad, a diferencia de un ancho fijo calculado a ojo) mientras el resto del texto (el punto,
// la «h») sigue con la fuente normal de la gráfica. =====
const DIGIT_FONT_STACK = 'ui-monospace, SFMono-Regular, Menlo, Consolas, monospace';

const charWidth = (ctx, ch, font, digitFont) => {
    ctx.font = /\d/.test(ch) ? digitFont : font;

    return ctx.measureText(ch).width;
};

const tabularWidth = (ctx, text, font, digitFont) => {
    let width = 0;
    for (const ch of text) width += charWidth(ctx, ch, font, digitFont);

    return width;
};

const drawTabularText = (ctx, text, x, y, align, font, digitFont) => {
    const total = tabularWidth(ctx, text, font, digitFont);
    let cursor = align === 'center' ? x - total / 2 : align === 'right' ? x - total : x;
    ctx.textAlign = 'left';
    for (const ch of text) {
        const width = charWidth(ctx, ch, font, digitFont);
        ctx.fillText(ch, cursor, y);
        cursor += width;
    }
};

// El texto nativo del eje se deja transparente (ocupa su espacio igual, para no mover el resto de la gráfica) y
// este plugin dibuja encima la versión con cifras alineadas.
const tabularAxesPlugin = {
    id: 'tabularAxes',
    afterDraw(chart) {
        const axes = chart.$tabularAxes;
        if (!axes) return;

        const { ctx } = chart;
        const fontSize = 15;
        const font = `400 ${fontSize}px ${chart.$tabularFont}`;
        const digitFont = `400 ${fontSize}px ${DIGIT_FONT_STACK}`;
        ctx.save();
        ctx.fillStyle = chart.$tabularColor;
        ctx.textBaseline = 'middle';

        if (axes.x) {
            // scale.bottom es el borde exterior del lienzo (no deja lugar para el texto): el hueco para la
            // etiqueta empieza donde termina el área de dibujo, chartArea.bottom.
            const scale = chart.scales.x;
            const y = chart.chartArea.bottom + (scale.options.ticks.padding ?? 10) + fontSize / 2;
            scale.ticks.forEach((tick, i) => drawTabularText(ctx, String(tick.label ?? ''), scale.getPixelForTick(i), y, 'center', font, digitFont));
        }
        if (axes.y) {
            // Igual en el otro eje: scale.left es el borde exterior; el hueco termina en chartArea.left.
            const scale = chart.scales.y;
            const x = chart.chartArea.left - (scale.options.ticks.padding ?? 10);
            scale.ticks.forEach((tick, i) => drawTabularText(ctx, String(tick.label ?? ''), x, scale.getPixelForTick(i), 'right', font, digitFont));
        }

        ctx.restore();
    },
};

// ===== Qué barra/día está bajo el cursor =====
// El zoom del apartado (--app-zoom) usa la propiedad CSS `zoom`, que estira el lienzo: su tamaño «de verdad»
// (canvas.width) no es el mismo que ocupa en pantalla (getBoundingClientRect). El manejo de ratón que trae
// Chart.js no tiene esto en cuenta y resaltaba una barra distinta de la que se tocaba. Por eso aquí se calcula
// a mano, con el mismo reescalado, en qué índice cae el cursor, y se le dice directamente al gráfico cuál resaltar.
const attachManualHover = (chart, categoryAxisKey) => {
    const canvas = chart.canvas;
    const activate = (index) => {
        chart.setActiveElements(index === null ? [] : [{ datasetIndex: 0, index }]);
        // update('none'): sin animación (si no, la barra tardaba los 250ms de la gráfica en resaltarse) y sin
        // rehacer el montaje completo (layout/leyenda), que es lo que rompía el gráfico con update() a secas.
        chart.update('none');
    };

    canvas.addEventListener('mousemove', (event) => {
        const rect = canvas.getBoundingClientRect();
        const scaleX = canvas.width / rect.width;
        const scaleY = canvas.height / rect.height;
        const localX = (event.clientX - rect.left) * scaleX;
        const localY = (event.clientY - rect.top) * scaleY;
        // Fuera del área de la gráfica no hay barra que resaltar.
        if (localX < chart.chartArea.left || localX > chart.chartArea.right || localY < chart.chartArea.top || localY > chart.chartArea.bottom) {
            activate(null);

            return;
        }
        const scale = chart.scales[categoryAxisKey];
        let index = scale.getValueForPixel(categoryAxisKey === 'x' ? localX : localY);
        if (index === undefined || index === null) { activate(null); return; }
        index = Math.max(0, Math.min(scale.ticks.length - 1, Math.round(index)));
        activate(index);
    });
    canvas.addEventListener('mouseleave', () => activate(null));
};

document.addEventListener('alpine:init', () => {
    window.Alpine.data('generalChart', (config) => {
        // Fuera de los datos reactivos de Alpine a propósito (variables normales de la función, no `this.x`):
        // Chart.js guarda referencias circulares y getters internos que, envueltos en el proxy reactivo de
        // Alpine/Livewire, terminaban en un desbordamiento de pila («Maximum call stack size exceeded») nada
        // más leerlos. Solo `empty` es reactivo porque la plantilla lo usa con x-show.
        let chart = null;
        let drawing = false;
        let sizeObserver = null;
        let themeObserver = null;

        const colors = (el) => {
            const style = getComputedStyle(el);
            const read = (name, fallback) => style.getPropertyValue(name).trim() || fallback;

            return {
                accent: read('--panel-accent', '#1A3A6B'),
                // Las barras van siempre en este azul fuerte, también en el tema oscuro (no el azul claro de
                // --panel-accent ahí, que se ve débil para una barra de color sólido).
                bar: '#1A3A6B',
                text: read('--panel-text', '#000000'),
                border: read('--panel-border', '#d4d4d8'),
                surface: read('--panel-surface', '#ffffff'),
                green: read('--prod-green', '#15803d'),
                font: style.fontFamily || 'Poppins, sans-serif',
            };
        };

        // Repinta un gráfico ya creado con los colores del tema actual (claro/oscuro), sin destruirlo.
        const applyColors = (c) => {
            if (!chart) return;

            chart.data.datasets[0].backgroundColor = c.bar;
            if (chart.data.datasets[1]) chart.data.datasets[1].borderColor = c.green;

            const axes = chart.$tabularAxes || {};
            ['x', 'y'].forEach((key) => {
                const scale = chart.options.scales[key];
                if (!scale) return;
                if (scale.grid) scale.grid.color = c.border;
                if (scale.border) scale.border.color = c.border;
                // Los ejes con cifras de ancho fijo quedan transparentes a propósito (los dibuja tabularAxesPlugin);
                // los demás (p. ej. los nombres de cliente/actividad) sí llevan el color del tema normal.
                if (scale.ticks && !axes[key]) scale.ticks.color = c.text;
            });

            chart.$tabularColor = c.text;
            chart.$tabularFont = c.font;
            chart.update();
        };

        const draw = async (el, canvas) => {
            // Varios disparadores (el ResizeObserver y los reintentos de init()) pueden llamar a draw() casi a
            // la vez; sin este candado, dos podían quedar esperando Chart.js a la vez y crear el gráfico dos
            // veces sobre el mismo lienzo (uno de los dos queda con un tamaño insignificante y se ve estirado).
            if (drawing || chart) return;
            drawing = true;
            const Chart = await waitForChart();
            if (!Chart || chart || !canvas || el.clientWidth === 0) { drawing = false; return; }

            const c = colors(el);
            const font = { family: c.font, size: 15, weight: '400' };
            const grid = { color: c.border, drawTicks: false };
            const ticks = { color: c.text, font, padding: 10 };
            // Mismo texto y tamaño, pero invisible: Chart.js sigue reservando el espacio del eje y tabularAxesPlugin
            // dibuja encima la versión con cifras de ancho fijo (ver más arriba).
            const tabularTicks = { ...ticks, color: 'transparent' };

            if (config.kind === 'day') {
                chart = new Chart(canvas, {
                    data: {
                        labels: config.labels,
                        datasets: [
                            {
                                type: 'bar',
                                label: 'Tiempo',
                                data: config.values.map((s) => s / 3600),
                                backgroundColor: c.bar,
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
                        // El manejo de ratón automático de Chart.js no tiene en cuenta el zoom del apartado y
                        // señalaba un día distinto del que se tocaba; se apaga y se calcula a mano (más abajo).
                        events: [],
                        plugins: {
                            legend: { display: false },
                            tooltip: { enabled: false },
                        },
                        scales: {
                            x: { grid: { display: false }, border: { color: c.border }, ticks: { ...tabularTicks, maxRotation: 0, autoSkip: true } },
                            y: { beginAtZero: true, grid, border: { display: false }, ticks: { ...tabularTicks, callback: (value) => `${Number(value).toFixed(1)}h` } },
                        },
                    },
                    plugins: [tabularAxesPlugin],
                });
                chart.$tabularAxes = { x: true, y: true };
                chart.$tabularFont = c.font;
                chart.$tabularColor = c.text;
                attachManualHover(chart, 'x');
                drawing = false;

                return;
            }

            // Ranking por cliente o por actividad: barras horizontales con el tiempo y el porcentaje del total
            chart = new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: config.labels,
                    datasets: [{
                        label: 'Tiempo',
                        data: config.values.map((s) => s / 3600),
                        backgroundColor: c.bar,
                        borderRadius: 6,
                        maxBarThickness: 32,
                    }],
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 250 },
                    events: [],
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: false },
                    },
                    scales: {
                        x: { beginAtZero: true, grid, border: { display: false }, ticks: { ...tabularTicks, callback: (value) => `${Number(value).toFixed(1)}h` } },
                        y: { grid: { display: false }, border: { color: c.border }, ticks: { ...ticks, autoSkip: false, callback(value) { return shorten(this.getLabelForValue(value), 28); } } },
                    },
                },
                plugins: [tabularAxesPlugin],
            });
            chart.$tabularAxes = { x: true };
            chart.$tabularFont = c.font;
            chart.$tabularColor = c.text;
            attachManualHover(chart, 'y');
            drawing = false;
        };

        return {
            empty: !(config.values || []).some((value) => value > 0),

            init() {
                sizeObserver = new ResizeObserver(() => {
                    if (!chart && this.$el.clientWidth > 0 && this.$el.clientHeight > 0) draw(this.$el, this.$refs.canvas);
                });
                sizeObserver.observe(this.$el);
                // El primer aviso del ResizeObserver a veces no llega (p. ej. si la pestaña ya estaba visible al
                // observar): sin esto el lienzo se queda con su tamaño por defecto (300×150) y luego se ve
                // estirado y desalineado. Se insiste unas cuantas veces hasta que el dibujo quede hecho.
                draw(this.$el, this.$refs.canvas);
                [50, 150, 400, 900].forEach((delay) => {
                    setTimeout(() => { if (!chart) draw(this.$el, this.$refs.canvas); }, delay);
                });
                // Cambio de tema claro/oscuro: se repinta con los colores nuevos (sin destruir el gráfico: hacerlo
                // justo cuando aún se estaba creando por primera vez podía dejarlo roto).
                themeObserver = new MutationObserver(() => {
                    if (chart) applyColors(colors(this.$el));
                });
                themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
            },

            destroy() {
                sizeObserver?.disconnect();
                themeObserver?.disconnect();
                chart?.destroy();
                chart = null;
            },
        };
    });
});
