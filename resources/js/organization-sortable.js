const CONTAINER_SELECTOR = '[data-organization-sortable]';
const CARD_SELECTOR = '[data-organization-module]';
const STORAGE_KEY = 'organization-module-order';

const cards = (container) => [...container.querySelectorAll(`:scope > ${CARD_SELECTOR}`)];

const saveOrder = (container) => {
    localStorage.setItem(
        STORAGE_KEY,
        JSON.stringify(cards(container).map((card) => card.dataset.organizationModule)),
    );
};

const restoreOrder = (container) => {
    try {
        const savedOrder = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
        const availableCards = new Map(
            cards(container).map((card) => [card.dataset.organizationModule, card]),
        );

        savedOrder.forEach((key) => {
            const card = availableCards.get(key);
            if (card) container.appendChild(card);
        });
    } catch {
        localStorage.removeItem(STORAGE_KEY);
    }
};

const initializeOrganizationSortable = (container) => {
    if (container.dataset.organizationSortableReady === 'true') return;
    container.dataset.organizationSortableReady = 'true';
    restoreOrder(container);

    container.addEventListener('pointerdown', (event) => {
        if (event.button !== 0 || event.target.closest('button, a, input, select, textarea, [role="button"]')) return;

        const draggedCard = event.target.closest(CARD_SELECTOR);
        if (!draggedCard || draggedCard.parentElement !== container) return;

        const origin = { x: event.clientX, y: event.clientY };
        let dragging = false;

        const move = (moveEvent) => {
            if (moveEvent.pointerId !== event.pointerId) return;

            if (!dragging) {
                const distance = Math.hypot(moveEvent.clientX - origin.x, moveEvent.clientY - origin.y);
                if (distance < 7) return;
                dragging = true;
                document.body.style.userSelect = 'none';
                draggedCard.style.zIndex = '30';
                draggedCard.style.boxShadow = '0 18px 42px rgba(26, 58, 107, 0.24)';
                draggedCard.style.outline = '2px solid #1A3A6B';
            }

            moveEvent.preventDefault();
            const target = document.elementFromPoint(moveEvent.clientX, moveEvent.clientY)?.closest(CARD_SELECTOR);
            if (!target || target === draggedCard || target.parentElement !== container) return;

            const bounds = target.getBoundingClientRect();
            const placeBefore = moveEvent.clientY < bounds.top + bounds.height / 2
                || (moveEvent.clientY <= bounds.bottom && moveEvent.clientX < bounds.left + bounds.width / 2);

            container.insertBefore(draggedCard, placeBefore ? target : target.nextSibling);
        };

        const finish = (finishEvent) => {
            if (finishEvent.pointerId !== event.pointerId) return;
            window.removeEventListener('pointermove', move);
            window.removeEventListener('pointerup', finish);
            window.removeEventListener('pointercancel', finish);

            if (!dragging) return;
            draggedCard.style.zIndex = '';
            draggedCard.style.boxShadow = '';
            draggedCard.style.outline = '';
            document.body.style.userSelect = '';
            saveOrder(container);
        };

        window.addEventListener('pointermove', move, { passive: false });
        window.addEventListener('pointerup', finish);
        window.addEventListener('pointercancel', finish);
    });
};

const initializeOrganizationSortables = (root = document) => {
    if (root.matches?.(CONTAINER_SELECTOR)) initializeOrganizationSortable(root);
    root.querySelectorAll?.(CONTAINER_SELECTOR).forEach(initializeOrganizationSortable);
};

document.addEventListener('DOMContentLoaded', () => initializeOrganizationSortables());
document.addEventListener('livewire:navigated', () => initializeOrganizationSortables());
document.addEventListener('livewire:init', () => {
    initializeOrganizationSortables();
    window.Livewire.hook('morph.updated', ({ el }) => initializeOrganizationSortables(el));
});
