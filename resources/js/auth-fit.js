// Pantallas de acceso: el formulario conserva su forma y su ancho; si la ventana es más pequeña,
// se reduce completo de forma proporcional para que nunca aparezca scroll horizontal ni vertical.
const initializeAuthFit = () => {
    const stage = document.querySelector('[data-auth-fit]');
    const shell = stage?.closest('.auth-shell');
    const footer = shell?.querySelector('[data-auth-fit-footer]');

    if (!stage || !shell || stage.dataset.initialized === 'true') {
        return;
    }

    stage.dataset.initialized = 'true';

    // Límite de reducción por falta de alto: por debajo de esta escala el formulario deja de achicarse
    // y se permite desplazar. El ancho nunca se limita, para que no haya scroll horizontal.
    const minScale = 0.75;
    let frame = null;

    const fit = () => {
        frame = null;

        // Medida natural (sin reducir y sin espacio reservado).
        shell.style.setProperty('--auth-reserve', '0px');
        stage.style.zoom = '1';
        if (footer) footer.style.zoom = '1';

        const style = getComputedStyle(shell);
        const paddingLeft = parseFloat(style.paddingLeft);
        const availableWidth = shell.clientWidth - paddingLeft - parseFloat(style.paddingRight);
        const baseHeight = shell.clientHeight - parseFloat(style.paddingTop) - parseFloat(style.paddingBottom) - 1;

        // En pantallas pequeñas el pie va dentro del flujo y se reduce junto con el formulario;
        // en pantallas grandes va en las esquinas izquierdas (arriba y abajo) y no forma parte de la medida.
        const footerInFlow = Boolean(footer) && getComputedStyle(footer).display !== 'contents';
        const margin = parseFloat(getComputedStyle(stage).marginTop) || 0;
        const stageWidth = stage.offsetWidth;
        const naturalWidth = Math.max(stageWidth, footerInFlow ? footer.offsetWidth : 0);
        const naturalHeight = stage.offsetHeight + margin + (footerInFlow ? footer.offsetHeight : 0);

        if (!naturalWidth || !naturalHeight) {
            stage.style.zoom = '';
            if (footer) footer.style.zoom = '';
            return;
        }

        const solve = (height) => Math.min(1, availableWidth / naturalWidth, Math.max(minScale, height / naturalHeight));
        let scale = solve(baseHeight);
        let reserve = 0;

        if (footer && !footerInFlow) {
            // Si los textos legales quedarían debajo del formulario (ventana angosta), se reserva su alto arriba y abajo
            // por igual: así el formulario sigue centrado y nunca lo pisa.
            const boxes = [...footer.querySelectorAll('.auth-legal-bottom')].map((item) => item.getBoundingClientRect());
            const footerRight = Math.max(0, ...boxes.map((box) => box.right));
            const footerHeight = Math.max(0, ...boxes.map((box) => box.height));
            const stageLeft = paddingLeft + (availableWidth - scale * stageWidth) / 2;

            if (stageLeft < footerRight + 24) {
                reserve = Math.ceil(footerHeight + 16);
                scale = solve(baseHeight - 2 * reserve);
            }
        }

        shell.style.setProperty('--auth-reserve', `${reserve}px`);

        const zoom = scale < 1 ? String(Math.floor(scale * 1000) / 1000) : '';
        stage.style.zoom = zoom;
        if (footer && footerInFlow) footer.style.zoom = zoom;
        else if (footer) footer.style.zoom = '';

        // Solo si ni siquiera a la escala mínima cabe, se permite desplazar verticalmente.
        shell.style.overflowY = scale * naturalHeight > baseHeight - 2 * reserve + 1 ? 'auto' : '';
    };

    const schedule = () => {
        if (frame === null) {
            frame = window.requestAnimationFrame(fit);
        }
    };

    const resizeObserver = new ResizeObserver(schedule);
    resizeObserver.observe(stage);
    resizeObserver.observe(shell);
    if (footer) resizeObserver.observe(footer);
    window.addEventListener('resize', schedule);
    document.fonts?.ready.then(schedule);
    fit();

    window.addEventListener('pagehide', () => {
        if (frame !== null) window.cancelAnimationFrame(frame);
        resizeObserver.disconnect();
        window.removeEventListener('resize', schedule);
    }, { once: true });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeAuthFit, { once: true });
} else {
    initializeAuthFit();
}
