// Desplazamiento fluido con la rueda para las listas de "7 filas exactas".
//
// - Mientras giras la rueda, la lista sigue tu movimiento de forma continua y suave (sin saltos de una fila a otra).
// - Cuando dejas de girar, se desliza suavemente hasta la fila más cercana, así nunca queda una fila a medias
//   y no hay una "corrección" brusca al final (es la misma animación continua, no un salto).
// Se activa con data-row-step="<selector de las filas>" en el contenedor con scroll.
const SMOOTHING = 0.2; // fracción de la distancia que se recorre en cada cuadro (0.2 = fluido, sin lentitud)
const IDLE_MS = 110; // tiempo sin girar la rueda para alinear a la fila más cercana
const SETTLE_MS = 450; // respaldo: si el navegador pausa la animación, la lista igualmente termina alineada
const LINE_PX = 16; // píxeles que vale una "línea" cuando la rueda reporta líneas

const rowTops = (scroller) => [...scroller.querySelectorAll(scroller.dataset.rowStep)].map((row) => row.offsetTop);

const nearestTop = (tops, value) => tops.reduce((best, top) => (Math.abs(top - value) < Math.abs(best - value) ? top : best), tops[0]);

const step = (scroller) => {
    const diff = scroller._rsTarget - scroller.scrollTop;

    if (Math.abs(diff) < 0.5) {
        scroller.scrollTop = scroller._rsTarget;
        scroller._rsFrame = null;
        return;
    }

    scroller.scrollTop += diff * SMOOTHING;
    scroller._rsFrame = requestAnimationFrame(() => step(scroller));
};

const animate = (scroller) => {
    if (! scroller._rsFrame) {
        scroller._rsFrame = requestAnimationFrame(() => step(scroller));
    }
};

document.addEventListener('wheel', (event) => {
    if (event.ctrlKey || event.deltaY === 0) return;

    const scroller = event.target.closest?.('[data-row-step]');
    if (! scroller) return;

    const max = scroller.scrollHeight - scroller.clientHeight;
    if (max <= 2) return;

    const current = scroller._rsTarget ?? scroller.scrollTop;
    const direction = Math.sign(event.deltaY);
    const atEdge = direction > 0 ? current >= max - 1 : current <= 1;

    // En los extremos se deja desplazar la página normalmente.
    if (atEdge) return;

    event.preventDefault();

    const delta = event.deltaMode === 1 ? event.deltaY * LINE_PX : event.deltaY;
    scroller._rsTarget = Math.min(max, Math.max(0, current + delta));
    animate(scroller);

    // Al dejar de girar, el destino pasa a la fila más cercana y la misma animación continua termina de deslizar la lista.
    clearTimeout(scroller._rsIdle);
    scroller._rsIdle = setTimeout(() => {
        const tops = rowTops(scroller);
        if (! tops.length) return;

        scroller._rsTarget = Math.min(max, Math.max(0, nearestTop(tops, scroller._rsTarget)));
        animate(scroller);

        clearTimeout(scroller._rsSettle);
        scroller._rsSettle = setTimeout(() => {
            cancelAnimationFrame(scroller._rsFrame);
            scroller._rsFrame = null;
            scroller.scrollTop = scroller._rsTarget;
            scroller._rsTarget = undefined;
        }, SETTLE_MS);
    }, IDLE_MS);
}, { passive: false });

// Si el usuario mueve la lista por otro medio (teclado, arrastre del indicador, táctil), se descarta el destino pendiente.
document.addEventListener('pointerdown', (event) => {
    const scroller = event.target.closest?.('[data-row-step]');
    if (scroller) scroller._rsTarget = undefined;
}, true);
