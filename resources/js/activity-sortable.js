const ROW_SELECTOR = '[data-activity-id]';
// Lo que no debe iniciar un arrastre: los controles de la fila siguen funcionando con un clic normal.
const INTERACTIVE_SELECTOR = 'button, a, input, select, textarea, label, [role="button"]';
// Distancia mínima (px) con el clic apretado para considerar que se arrastra la fila.
const DRAG_THRESHOLD = 6;

const animateReflow = (rows, previousPositions) => {
    rows.forEach((row) => {
        const distance = previousPositions.get(row) - row.getBoundingClientRect().top;
        if (!distance) return;

        row.getAnimations().forEach((animation) => animation.cancel());
        row.animate(
            [{ transform: `translateY(${distance}px)` }, { transform: 'translateY(0)' }],
            {
                duration: 380,
                easing: 'cubic-bezier(0.25, 0.8, 0.25, 1)',
                fill: 'both',
            },
        );
    });
};

const initializeActivitySortable = (container) => {
    if (container.dataset.activitySortableReady === 'true') return;
    container.dataset.activitySortableReady = 'true';

    container.addEventListener('pointerdown', (event) => {
        // Solo con el botón principal del ratón o lápiz; en pantallas táctiles la fila conserva su desplazamiento normal.
        if (event.button !== 0 || event.pointerType === 'touch') return;
        // Los botones con data-sortable-handle (filas que son un botón) también pueden arrastrarse; un clic simple sigue funcionando.
        const interactive = event.target.closest(INTERACTIVE_SELECTOR);
        if (interactive && !interactive.hasAttribute('data-sortable-handle')) return;

        const draggedRow = event.target.closest(ROW_SELECTOR);
        if (!draggedRow || draggedRow.parentNode !== container) return;

        const startX = event.clientX;
        const startY = event.clientY;
        let dragging = false;
        let finished = false;

        const startDragging = () => {
            dragging = true;
            window.getSelection()?.removeAllRanges();
            draggedRow.style.position = 'relative';
            draggedRow.style.zIndex = '30';
            draggedRow.style.pointerEvents = 'none';
            draggedRow.style.backgroundColor = 'var(--panel-surface)';
            draggedRow.style.boxShadow = '0 14px 35px rgba(0, 0, 0, 0.14)';
            draggedRow.style.cursor = 'grabbing';
            document.body.style.userSelect = 'none';
            document.body.style.cursor = 'grabbing';
        };

        const move = (moveEvent) => {
            if (moveEvent.pointerId !== event.pointerId) return;

            if (!dragging) {
                if (Math.hypot(moveEvent.clientX - startX, moveEvent.clientY - startY) < DRAG_THRESHOLD) return;
                startDragging();
            }

            moveEvent.preventDefault();
            if (moveEvent.clientY < 70) window.scrollBy(0, -12);
            if (moveEvent.clientY > window.innerHeight - 70) window.scrollBy(0, 12);

            const target = document.elementFromPoint(moveEvent.clientX, moveEvent.clientY)?.closest(ROW_SELECTOR);
            if (!target || target === draggedRow || target.parentNode !== container) return;

            const bounds = target.getBoundingClientRect();
            const placeBefore = moveEvent.clientY < bounds.top + bounds.height / 2;
            const alreadyPlaced = placeBefore
                ? draggedRow.nextElementSibling === target
                : target.nextElementSibling === draggedRow;

            if (alreadyPlaced) return;

            const rows = [...container.querySelectorAll(ROW_SELECTOR)];
            const previousPositions = new Map(rows.map((row) => [row, row.getBoundingClientRect().top]));
            container.insertBefore(draggedRow, placeBefore ? target : target.nextSibling);
            animateReflow(rows, previousPositions);
        };

        const finish = async (finishEvent) => {
            if (finished || finishEvent.pointerId !== event.pointerId) return;
            finished = true;

            window.removeEventListener('pointermove', move);
            window.removeEventListener('pointerup', finish);
            window.removeEventListener('pointercancel', finish);

            // Un clic sin desplazamiento no reordena nada.
            if (!dragging) return;

            draggedRow.style.position = '';
            draggedRow.style.zIndex = '';
            draggedRow.style.pointerEvents = '';
            draggedRow.style.backgroundColor = '';
            draggedRow.style.boxShadow = '';
            draggedRow.style.cursor = '';
            document.body.style.userSelect = '';
            document.body.style.cursor = '';

            // Tras arrastrar un botón, el clic que dispara el navegador no debe abrir ni cerrar la fila.
            const swallowClick = (clickEvent) => clickEvent.stopPropagation();
            window.addEventListener('click', swallowClick, { capture: true, once: true });
            setTimeout(() => window.removeEventListener('click', swallowClick, { capture: true }), 0);

            // Orden solo visual (sin guardar): por ejemplo las distribuciones y el detalle de Productividad.
            if (container.hasAttribute('data-sortable-local')) return;

            const order = [...container.querySelectorAll(ROW_SELECTOR)]
                .map((row, index) => ({ order: index + 1, value: row.dataset.activityId }));
            const componentId = container.closest('[wire\\:id]')?.getAttribute('wire:id');
            const component = componentId ? window.Livewire?.find(componentId) : null;

            if (!component) {
                window.location.reload();
                return;
            }

            await component.call('updateActivityOrder', order);
        };

        window.addEventListener('pointermove', move, { passive: false });
        window.addEventListener('pointerup', finish);
        window.addEventListener('pointercancel', finish);
    });
};

const initializeActivitySortables = (root = document) => {
    if (root.matches?.('[data-activity-sortable]')) initializeActivitySortable(root);
    root.querySelectorAll?.('[data-activity-sortable]').forEach(initializeActivitySortable);
};

document.addEventListener('DOMContentLoaded', () => initializeActivitySortables());
document.addEventListener('livewire:navigated', () => initializeActivitySortables());
document.addEventListener('livewire:init', () => {
    initializeActivitySortables();
    window.Livewire.hook('morph.updated', ({ el }) => initializeActivitySortables(el));
});
