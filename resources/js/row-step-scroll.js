// Desplazamiento fila por fila con la rueda del ratón: cada giro mueve exactamente una fila (animado), así las listas con
// "7 filas exactas" nunca quedan a medias y no hace falta corregir la posición después (esa corrección se veía como un salto).
// Se activa con data-row-step="<selector de las filas>" en el contenedor con scroll.
const WHEEL_THRESHOLD = 30;
const LOCK_MS = 230;

const rowTops = (scroller, selector) => [...scroller.querySelectorAll(selector)].map((row) => row.offsetTop);

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

    if (scroller._rowStepLock) return;

    scroller._rowStepAcc = (scroller._rowStepAcc || 0) + (event.deltaMode === 1 ? event.deltaY * 16 : event.deltaY);
    if (Math.abs(scroller._rowStepAcc) < WHEEL_THRESHOLD) return;
    scroller._rowStepAcc = 0;

    // Siguiente fila en la dirección del giro (si ya está alineada a una fila, la contigua; si quedó a medias, la más cercana).
    const candidates = direction > 0 ? tops.filter((top) => top > current + 2) : tops.filter((top) => top < current - 2).reverse();
    if (! candidates.length) return;
    const target = Math.min(max, Math.max(0, candidates[0]));

    scroller._rowStepTarget = target;
    scroller._rowStepLock = true;
    scroller.scrollTo({ top: target, behavior: 'smooth' });
    setTimeout(() => {
        scroller._rowStepLock = false;
        scroller._rowStepTarget = undefined;
    }, LOCK_MS);
}, { passive: false });
