// Pantallas de acceso: todo el contenido (formulario, telaraña, derechos reservados, botones y chat) vive en
// un "mundo" que se escala con un zoom común, como el zoom de la página. La escala es la misma que la del marco
// de la app (window.appAutoScale: lienzo de 1800x950, mínimo 75%), así todo se ve igual en cualquier pantalla.
// El mundo ocupa exactamente la ventana (o su tamaño mínimo): las esquinas son las de la página, no las de la
// pantalla. Si la ventana es más pequeña de lo que cabe a la escala mínima, no se encoge más: la página se
// desplaza en horizontal y en vertical.
const initializeAuthFit = () => {
    const world = document.querySelector('[data-auth-world]');
    const stage = world?.querySelector('[data-auth-fit]');
    const shell = world?.closest('.auth-shell');
    const legal = world?.querySelector('[data-auth-fit-footer]');

    if (!world || !stage || !shell || world.dataset.initialized === 'true') {
        return;
    }

    world.dataset.initialized = 'true';

    // Por debajo de esta escala el contenido sería ilegible: a partir de ahí la página se desplaza.
    const minScale = 0.75;
    const chatHeight = 540;
    let frame = null;

    const fit = () => {
        frame = null;

        const mobile = window.matchMedia('(max-width: 640px)').matches;
        const pad = mobile ? 20 : 40;
        const padBottom = mobile ? 80 : 40; // en móvil el último espacio es para la fila de botones

        // Medida natural: el mundo a escala 1 (el bloque central mide siempre 412px de ancho).
        world.style.zoom = '1';

        const viewportWidth = document.documentElement.clientWidth;
        const viewportHeight = window.innerHeight;
        const legalItem = legal?.querySelector('.auth-legal-bottom');
        const legalInFlow = Boolean(legal) && getComputedStyle(legal).display !== 'contents';
        const stageWidth = stage.offsetWidth;
        const stageHeight = stage.offsetHeight;
        const flowWidth = legalInFlow ? legal.offsetWidth : 0;
        const flowHeight = legalInFlow ? legal.offsetHeight : 0;

        if (!stageWidth || !stageHeight || !viewportWidth) {
            world.style.zoom = '';
            return;
        }

        const requiredWidth = Math.max(stageWidth, flowWidth) + 2 * pad;
        // El chat (380x540) necesita 540px más sus márgenes; el bloque central, su alto más los márgenes.
        const requiredHeight = (reserve) => Math.max(
            stageHeight + flowHeight + pad + padBottom + 2 * reserve,
            mobile ? 0 : chatHeight + 2 * pad,
        );
        // Escritorio: la escala del lienzo (igual que la app). Celular: se ajusta al ancho y al alto, sin bajar del mínimo.
        const canvasScale = window.appAutoScale ? window.appAutoScale() : 1;
        const solve = (reserve) => (mobile
            ? Math.max(minScale, Math.min(1, window.innerWidth / requiredWidth, viewportHeight / requiredHeight(reserve)))
            : canvasScale);

        let scale = solve(0);
        let reserve = 0;

        if (!mobile && legalItem) {
            // Si el texto legal quedaría debajo del formulario (ventana angosta), se reserva su alto arriba y
            // abajo por igual: así el formulario sigue centrado y nunca lo pisa.
            const stageLeft = (Math.max(viewportWidth / scale, requiredWidth) - stageWidth) / 2;

            if (stageLeft < pad + legalItem.offsetWidth + 20) {
                reserve = Math.ceil(legalItem.offsetHeight + 15);
                scale = solve(reserve);
            }
        }

        scale = Math.floor(scale * 1000) / 1000;

        // El mundo nunca es más pequeño de lo que necesita su contenido: si la ventana es menor, sobra y hay scroll.
        const worldWidth = Math.max(viewportWidth / scale, requiredWidth);
        const worldHeight = Math.max(viewportHeight / scale, requiredHeight(reserve));
        const visualWidth = worldWidth * scale;
        const visualHeight = worldHeight * scale;
        const scrollsX = visualWidth > viewportWidth + 1;
        const scrollsY = visualHeight > viewportHeight + 1;

        world.style.width = `${worldWidth}px`;
        world.style.height = `${worldHeight}px`;
        world.style.setProperty('--auth-pad', `${pad}px`);
        world.style.setProperty('--auth-pad-top', `${pad + reserve}px`);
        world.style.setProperty('--auth-pad-bottom', `${padBottom + reserve}px`);
        world.style.zoom = scale < 1 ? String(scale) : '';

        // La página crece solo si el mundo no cabe en la ventana: entonces el scroll es el de la página.
        shell.style.width = scrollsX ? `${Math.ceil(visualWidth)}px` : '';
        shell.style.height = scrollsY ? `${Math.ceil(visualHeight)}px` : '';

        if (scrollsX) {
            shell.dataset.authScrollX = '';
        } else {
            delete shell.dataset.authScrollX;
        }

        if (scrollsX || scrollsY) {
            shell.dataset.authScroll = '';
        } else {
            delete shell.dataset.authScroll;
        }
    };

    const schedule = () => {
        if (frame === null) {
            frame = window.requestAnimationFrame(fit);
        }
    };

    const resizeObserver = new ResizeObserver(schedule);
    resizeObserver.observe(stage);
    resizeObserver.observe(shell);
    window.addEventListener('resize', schedule);
    document.fonts?.ready.then(schedule);
    // La tipografía llega sin bloquear la página: al terminar de cargar cambian las medidas del texto.
    document.fonts?.addEventListener?.('loadingdone', schedule);
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
