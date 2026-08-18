const ROW_SELECTOR = '[data-activity-id]';
const HANDLE_SELECTOR = '[data-activity-drag-handle]';

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
        const handle = event.target.closest(HANDLE_SELECTOR);
        if (!handle || event.button !== 0) return;

        const draggedRow = handle.closest(ROW_SELECTOR);
        if (!draggedRow) return;

        event.preventDefault();
        handle.setPointerCapture?.(event.pointerId);

        let finished = false;

        draggedRow.style.position = 'relative';
        draggedRow.style.zIndex = '30';
        draggedRow.style.pointerEvents = 'none';
        draggedRow.style.backgroundColor = '#ffffff';
        draggedRow.style.boxShadow = '0 14px 35px rgba(0, 0, 0, 0.14)';
        document.body.style.userSelect = 'none';

        const move = (moveEvent) => {
            if (moveEvent.pointerId !== event.pointerId) return;

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

            draggedRow.style.position = '';
            draggedRow.style.zIndex = '';
            draggedRow.style.pointerEvents = '';
            draggedRow.style.backgroundColor = '';
            draggedRow.style.boxShadow = '';
            document.body.style.userSelect = '';

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
