// Gráficas de «Gráficos Generales» (Chart.js). Cada gráfica toma sus colores de las variables del panel, así se ve bien en el
// tema claro y en el oscuro, y se vuelve a dibujar sola al cambiar de tema. Se dibuja cuando su pestaña es visible (con tamaño).
// Los globos al pasar el cursor usan el mismo diseño que el resto de la app (no el tooltip genérico de Chart.js): un bloque
// blanco/oscuro con borde, título en azul y una flechita, igual que «Comparación con el reloj checador» en Manager Control.
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

// Diferencia con signo: +001h 00m 00s / −001h 00m 00s / 000h 00m 00s, igual que en Manager Control
const signedHms = (seconds) => {
    const sign = seconds < 0 ? '−' : seconds > 0 ? '+' : '';
    return sign + hms(Math.abs(seconds));
};

const shorten = (text, max) => (String(text).length > max ? `${String(text).slice(0, max - 1)}…` : String(text));

// ===== Cifras de ancho fijo en los ejes, igual que en las tablas del resto de la app (cada dígito mide 0.64em; la
// letra Poppins no es tabular, así que sin esto los números del eje no quedan alineados). Chart.js dibuja el texto
// de los ejes en el lienzo (no con HTML), así que el truco de las cajas por dígito se replica a mano con fillText. =====
const DIGIT_WIDTH_EM = 0.64;

const tabularWidth = (ctx, text, digitWidth) => {
    let width = 0;
    for (const ch of text) width += /\d/.test(ch) ? digitWidth : ctx.measureText(ch).width;

    return width;
};

const drawTabularText = (ctx, text, x, y, align, digitWidth) => {
    const total = tabularWidth(ctx, text, digitWidth);
    let cursor = align === 'center' ? x - total / 2 : align === 'right' ? x - total : x;
    for (const ch of text) {
        const isDigit = /\d/.test(ch);
        const width = isDigit ? digitWidth : ctx.measureText(ch).width;
        ctx.textAlign = isDigit ? 'center' : 'left';
        ctx.fillText(ch, isDigit ? cursor + width / 2 : cursor, y);
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
        const digitWidth = fontSize * DIGIT_WIDTH_EM;
        ctx.save();
        ctx.font = `400 ${fontSize}px ${chart.$tabularFont}`;
        ctx.fillStyle = chart.$tabularColor;
        ctx.textBaseline = 'middle';

        if (axes.x) {
            // scale.bottom es el borde exterior del lienzo (no deja lugar para el texto): el hueco para la
            // etiqueta empieza donde termina el área de dibujo, chartArea.bottom.
            const scale = chart.scales.x;
            const y = chart.chartArea.bottom + (scale.options.ticks.padding ?? 10) + fontSize / 2;
            scale.ticks.forEach((tick, i) => drawTabularText(ctx, String(tick.label ?? ''), scale.getPixelForTick(i), y, 'center', digitWidth));
        }
        if (axes.y) {
            // Igual en el otro eje: scale.left es el borde exterior; el hueco termina en chartArea.left.
            const scale = chart.scales.y;
            const x = chart.chartArea.left - (scale.options.ticks.padding ?? 10);
            scale.ticks.forEach((tick, i) => drawTabularText(ctx, String(tick.label ?? ''), x, scale.getPixelForTick(i), 'right', digitWidth));
        }

        ctx.restore();
    },
};

// Cada cifra en su propia caja (igual ancho), como en el resto del módulo: la letra Poppins no es tabular.
const digitsHtml = (text) => String(text).replace(/\d/g, (d) => `<span class="prod-dg">${d}</span>`);

// Tolerancia de 6 minutos (ManagerControl guarda décimas de hora): una diferencia menor se considera redondeo, no un problema real.
const TOLERANCE_SECONDS = 360;

const CLOCK_STATE_MESSAGES = {
    no_user: 'Este colaborador no tiene un usuario del CRM asignado, por eso no hay marcas del reloj checador que comparar.',
    no_clock_id: 'El usuario del CRM de este colaborador no tiene ID de checador asignado.',
    error: 'No se pudo leer el reloj checador. Inténtalo de nuevo.',
};

// ===== Globo (tooltip) con el mismo diseño que el resto de la app =====

const buildTooltipEl = () => {
    const el = document.createElement('div');
    el.setAttribute('role', 'tooltip');
    el.setAttribute('data-gc-tooltip', '');
    // El globo va pegado al <body> (fuera del panel), así que necesita declararse su propio [data-panel]: si no,
    // --panel-surface/--panel-border/--panel-text no existen ahí y el fondo sale transparente (deja ver la gráfica
    // detrás) en vez de blanco u oscuro según el tema. El «Comparación con el reloj checador» de Manager Control
    // hace lo mismo.
    el.setAttribute('data-panel', '');
    el.setAttribute('data-productivity', '');
    // Mismas clases que el globo «Comparación con el reloj checador» de Manager Control: ya están compiladas en el CSS.
    el.className = 'app-zoom-overlay panel-surface panel-border panel-text pointer-events-none fixed z-[200001] block w-[440px] max-w-[calc(100vw-40px)] rounded-xl border p-[20px] shadow-[0_18px_55px_rgba(0,0,0,0.30)] hidden';
    el.innerHTML = `
        <span class="panel-accent block leading-[30px]" data-slot="title"></span>
        <span class="mt-[15px] block leading-[30px]" data-slot="subtitle"></span>
        <div data-slot="rows"></div>
        <span class="mt-[15px] block text-justify leading-[30px]" data-slot="note" style="display:none"></span>
        <span class="panel-surface panel-border absolute right-[20px] h-[11px] w-[11px] rotate-45" data-slot="arrow"></span>
    `;
    document.body.appendChild(el);

    return el;
};

const setTooltipRows = (el, rows) => {
    el.querySelector('[data-slot="rows"]').innerHTML = rows.map((row, i) => (
        `<span class="${i === 0 ? 'mt-[15px] ' : ''}flex items-center justify-between gap-[20px] leading-[30px]"><span>${row.label}</span><span class="whitespace-nowrap tabular-nums">${row.valueHtml}</span></span>`
    )).join('');
};

const setTooltipNote = (el, text, isWarning) => {
    const note = el.querySelector('[data-slot="note"]');
    if (!text) {
        note.style.display = 'none';
        return;
    }
    note.style.display = '';
    note.style.color = isWarning ? 'var(--panel-danger)' : '';
    note.textContent = text;
};

// Rellena el globo con la comparación del día frente al reloj checador: mismo texto y lógica que Manager Control.
const fillDayTooltip = (el, config, index) => {
    el.querySelector('[data-slot="title"]').textContent = 'Comparación con el reloj checador';
    el.querySelector('[data-slot="subtitle"]').textContent = config.titles[index];

    if (config.clockState !== 'ok') {
        setTooltipRows(el, []);
        setTooltipNote(el, CLOCK_STATE_MESSAGES[config.clockState] || CLOCK_STATE_MESSAGES.error, false);

        return;
    }

    const sourceSeconds = config.values[index];
    const clockDay = config.clockDays[config.isoDates[index]] ?? null;
    const inSource = `En ${config.sourceLabel}`;
    const clockValueHtml = clockDay !== null && clockDay.review ? 'Marcas incompletas' : digitsHtml(hms(clockDay?.seconds ?? 0));
    const rows = [
        { label: 'Reloj checador', valueHtml: clockValueHtml },
        { label: config.sourceLabel, valueHtml: digitsHtml(hms(sourceSeconds)) },
    ];

    if (clockDay === null) {
        rows.push({ label: 'Diferencia', valueHtml: digitsHtml(signedHms(sourceSeconds)) });
        setTooltipRows(el, rows);
        setTooltipNote(
            el,
            sourceSeconds > 0
                ? `Advertencia: el reloj checador no tiene marcas ese día y ${inSource.toLowerCase()} hay tiempo capturado.`
                : 'El reloj checador no tiene marcas ese día.',
            sourceSeconds > 0,
        );

        return;
    }

    if (clockDay.review) {
        setTooltipRows(el, rows);
        setTooltipNote(el, `Advertencia: el reloj checador tiene un número impar de marcas ese día (${clockDay.marks}); hay que revisarlas para poder comparar.`, true);

        return;
    }

    const diff = sourceSeconds - clockDay.seconds;
    rows.push({ label: 'Diferencia', valueHtml: digitsHtml(signedHms(diff)) });
    setTooltipRows(el, rows);

    if (Math.abs(diff) > TOLERANCE_SECONDS) {
        setTooltipNote(el, `Advertencia: el tiempo del día no coincide. ${inSource} hay ${diff < 0 ? 'menos' : 'más'} tiempo que en el reloj checador.`, true);
    } else {
        setTooltipNote(el, 'Los tiempos coinciden.', false);
    }
};

// Globo simple de las gráficas por cliente/actividad: el tiempo y el porcentaje del total.
const fillRankTooltip = (el, config, index) => {
    el.querySelector('[data-slot="title"]').textContent = config.labels[index];
    el.querySelector('[data-slot="subtitle"]').style.display = 'none';
    const seconds = config.values[index];
    const pct = config.total > 0 ? Math.round((seconds * 100) / config.total) : 0;
    setTooltipRows(el, [
        { label: 'Tiempo', valueHtml: digitsHtml(hms(seconds)) },
        { label: 'Porcentaje', valueHtml: digitsHtml(`${pct}%`) },
    ]);
    setTooltipNote(el, null, false);
};

// Misma matemática de posición que el globo de Manager Control: pegado al punto, sin salirse de la pantalla y
// reescalado por --app-zoom (el globo está teleportado al body, fuera del contenedor con el zoom del apartado).
const positionTooltip = (el, point) => {
    const zoom = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--app-zoom')) || 1;
    const below = point.y < 360 * zoom;
    const width = Math.min(440 * zoom, window.innerWidth - 40);
    const right = Math.max(width + 20, Math.min(point.x, window.innerWidth - 20));
    const x = right / zoom;
    const y = point.y / zoom;
    const arrowOffset = right === point.x ? 20 : Math.max(20, Math.min(width / zoom - 31, (right - point.x) / zoom - 5.5));

    el.style.left = `${x}px`;
    el.style.top = `${y}px`;
    el.style.transform = `translate(-100%, ${below ? '20px' : 'calc(-100% - 20px)'})`;

    const arrow = el.querySelector('[data-slot="arrow"]');
    arrow.style.right = `${arrowOffset}px`;
    arrow.classList.toggle('-top-[6px]', below);
    arrow.classList.toggle('border-l', below);
    arrow.classList.toggle('border-t', below);
    arrow.classList.toggle('-bottom-[6px]', !below);
    arrow.classList.toggle('border-b', !below);
    arrow.classList.toggle('border-r', !below);
};

// ===== Qué barra/día está bajo el cursor =====
// El zoom del apartado (--app-zoom) usa la propiedad CSS `zoom`, que estira el lienzo: su tamaño «de verdad»
// (canvas.width) no es el mismo que ocupa en pantalla (getBoundingClientRect). El manejo de ratón que trae
// Chart.js no tiene esto en cuenta y señalaba un día o una barra distinta de la que se tocaba (siempre la
// misma cantidad de lugares de más). Por eso aquí se calcula a mano, con el mismo reescalado que usa el
// globo, en qué índice cae el cursor, y se le dice directamente al gráfico cuál mostrar. El globo se rellena
// y se posiciona aquí mismo (no por el plugin de tooltip de Chart.js, nunca se dispara con `events: []`).
//
// `tipHolder` es un objeto normal (no un dato reactivo de Alpine) donde se guarda el globo de este gráfico:
// Chart.js y su `chart` tienen referencias circulares y getters internos que, envueltos en el proxy reactivo
// de Alpine/Livewire, terminaban en un desbordamiento de pila al leerlos.
const attachManualHover = (chart, canvas, categoryAxisKey, tipHolder, config) => {
    const activate = (index) => {
        if (!tipHolder.el) tipHolder.el = buildTooltipEl();
        const el = tipHolder.el;

        if (index === null) {
            chart.setActiveElements([]);
            chart.update('none');
            el.classList.add('hidden');

            return;
        }

        // El globo no sigue a la barra: se queda siempre en la misma esquina del gráfico (arriba a la
        // derecha del área de dibujo), para que nunca tape ni se confunda con la barra de al lado.
        const x = chart.chartArea.right;
        const y = chart.chartArea.top;
        chart.setActiveElements([{ datasetIndex: 0, index }]);
        // update('none'): sin animación (si no, la barra tardaba los 250ms de la gráfica en resaltarse) y sin
        // rehacer el montaje completo (layout/leyenda), que es lo que rompía el gráfico con update() a secas.
        chart.update('none');

        if (config.kind === 'day') fillDayTooltip(el, config, index);
        else fillRankTooltip(el, config, index);

        const rect = canvas.getBoundingClientRect();
        const scaleX = rect.width / canvas.width;
        const scaleY = rect.height / canvas.height;
        positionTooltip(el, { x: rect.left + x * scaleX, y: rect.top + y * scaleY });
        el.classList.remove('hidden');
    };

    canvas.addEventListener('mousemove', (event) => {
        const rect = canvas.getBoundingClientRect();
        const scaleX = canvas.width / rect.width;
        const scaleY = canvas.height / rect.height;
        const localX = (event.clientX - rect.left) * scaleX;
        const localY = (event.clientY - rect.top) * scaleY;
        const scale = chart.scales[categoryAxisKey];
        const pixel = categoryAxisKey === 'x' ? localX : localY;
        // Fuera del área de la gráfica no hay barra que señalar.
        if (localX < chart.chartArea.left || localX > chart.chartArea.right || localY < chart.chartArea.top || localY > chart.chartArea.bottom) {
            activate(null);

            return;
        }
        let index = scale.getValueForPixel(pixel);
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
        const tipHolder = { el: null };
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
                attachManualHover(chart, canvas, 'x', tipHolder, config);
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
            attachManualHover(chart, canvas, 'y', tipHolder, config);
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
                tipHolder.el?.remove();
                tipHolder.el = null;
            },
        };
    });
});
