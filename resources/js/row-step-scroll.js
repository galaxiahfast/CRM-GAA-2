// Desplazamiento fila por fila con la rueda del ratón: cada giro mueve exactamente una fila, con una animación corta, así las
// listas con "7 filas exactas" nunca quedan a medias y no hace falta corregir la posición después (esa corrección se veía como un salto).
// Se activa con data-row-step="<selector de las filas>" en el contenedor con scroll.
const WHEEL_THRESHOLD = 30; // píxeles de rueda acumulados para mover una fila
const DURATION_MS = 110; // duración de la animación de cada paso

const rowTops = (scroller, selector) => [...scroller.querySelectorAll(selector)].map((row) => row.offsetTop);

// Anima el scroll hasta "to"; si llega otro paso mientras tanto, continúa desde la posición actual hacia la nueva fila.
const animateTo = (scroller, to) => {
    cancelAnimationFrame(scroller._rowStepFrame);
    const from = scroller.scrollTop;
    const start = performance.now();
    const tick = (now) => {
        const progress = Math.min(1, (now - start) / DURATION_MS);
        const eased = 1 - Math.pow(1 - progress, 3);
        scroller.scrollTop = from + (to - from) * eased;
        if (progress < 1) {
            scroller._rowStepFrame = requestAnimationFrame(tick);
        } else {
            scroller.scrollTop = to;
            scroller._rowStepTarget = undefined;
        }
    };
    scroller._rowStepFrame = requestAnimationFrame(tick);
};

document.addEventListener('wheel', (event) => {
    if (event.ctrlKey || event.deltaY === 0) return;

    const scroller = event.target.closest?.('[data-row-step]');
    if (!scroller) return;

    const max = scroller.scrollHeight - scroller.clientHeight;
    if (max <= 2) return;

    const tops = rowTops(scroller, scroller.dataset.rowStep);
    if (! tops.length) return;

    const direction = Math.sign(event.deltaY);
    const current = scroller._rowStepTarget ?? scroller.scrollTop;
    const atEdge = direction > 0 ? current >= max - 2 : current <= 2;

    // En los extremos se deja desplazar la página normalmente.
    if (atEdge) return;

    event.preventDefault();

    // Un giro en sentido contrario descarta lo acumulado.
    if (scroller._rowStepAcc && Math.sign(scroller._rowStepAcc) !== direction) scroller._rowStepAcc = 0;
    scroller._rowStepAcc = (scroller._rowStepAcc || 0) + (event.deltaMode === 1 ? event.deltaY * 16 : event.deltaY);
    if (Math.abs(scroller._rowStepAcc) < WHEEL_THRESHOLD) return;
    scroller._rowStepAcc = 0;

    // Siguiente fila en la dirección del giro (si ya está alineada a una fila, la contigua; si quedó a medias, la más cercana).
    const candidates = direction > 0 ? tops.filter((top) => top > current + 2) : tops.filter((top) => top < current - 2).reverse();
    if (! candidates.length) return;
    const target = Math.min(max, Math.max(0, candidates[0]));

    scroller._rowStepTarget = target;
    animateTo(scroller, target);
}, { passive: false });
