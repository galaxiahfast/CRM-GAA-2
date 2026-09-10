const CONTAINER_SELECTOR = '[data-organization-sortable]';
const CARD_SELECTOR = '[data-organization-module]';
const STORAGE_KEY = 'organization-module-order';

const domCards = (container) => [...container.querySelectorAll(`:scope > ${CARD_SELECTOR}`)];

// Never move these nodes with appendChild/insertBefore. Some cards own nested
// Livewire components and removing/reinserting them makes Livewire initialize
// an existing component a second time without its server snapshot.
const cards = (container) => domCards(container).sort((first, second) => {
    const firstOrder = Number.parseInt(first.style.order || '0', 10);
    const secondOrder = Number.parseInt(second.style.order || '0', 10);
    return firstOrder - secondOrder;
});

const applyOrder = (orderedCards) => {
    orderedCards.forEach((card, index) => {
        card.style.order = String(index);
    });
};

const createDragPreview = (card, bounds) => {
    const preview = card.cloneNode(true);
    preview.querySelectorAll('*').forEach((element) => {
        element.removeAttribute('id');
        [...element.attributes].forEach((attribute) => {
            if (
                attribute.name.startsWith('wire:')
                || attribute.name.startsWith('x-')
                || attribute.name.startsWith('@')
            ) {
                element.removeAttribute(attribute.name);
            }
        });
    });
    preview.removeAttribute('id');
    [...preview.attributes].forEach((attribute) => {
        if (
            attribute.name.startsWith('wire:')
            || attribute.name.startsWith('x-')
            || attribute.name.startsWith('@')
        ) {
            preview.removeAttribute(attribute.name);
        }
    });

    preview.removeAttribute('data-organization-module');
    preview.setAttribute('aria-hidden', 'true');
    preview.inert = true;
    Object.assign(preview.style, {
        position: 'fixed',
        left: `${bounds.left}px`,
        top: `${bounds.top}px`,
        width: `${bounds.width}px`,
        height: `${bounds.height}px`,
        margin: '0',
        opacity: '1',
        pointerEvents: 'none',
        transform: 'none',
        transition: 'none',
        zIndex: '10000',
        boxShadow: 'none',
        outline: 'none',
        filter: 'none',
        willChange: 'left, top',
    });
    document.body.appendChild(preview);

    return preview;
};

const cardPositions = (container) => new Map(
    cards(container).map((card) => [card, card.getBoundingClientRect()]),
);

const animateReorder = (container, previousPositions) => {
    cards(container).forEach((card) => {
        const previous = previousPositions.get(card);
        if (!previous) return;

        const current = card.getBoundingClientRect();
        const offsetX = previous.left - current.left;
        const offsetY = previous.top - current.top;
        if (!offsetX && !offsetY) return;

        card.getAnimations().forEach((animation) => animation.cancel());
        card.animate(
            [
                { transform: `translate(${offsetX}px, ${offsetY}px)` },
                { transform: 'translate(0, 0)' },
            ],
            {
                duration: 280,
                easing: 'cubic-bezier(0.22, 1, 0.36, 1)',
            },
        );
    });
};

const nearestCard = (container, draggedCard, pointerX, pointerY) => {
    return cards(container)
        .filter((card) => card !== draggedCard)
        .map((card) => {
            const bounds = card.getBoundingClientRect();
            const centerX = bounds.left + bounds.width / 2;
            const centerY = bounds.top + bounds.height / 2;
            const horizontalDistance = (pointerX - centerX) / Math.max(bounds.width, 1);
            const verticalDistance = (pointerY - centerY) / Math.max(bounds.height, 1);

            return {
                card,
                bounds,
                distance: Math.hypot(horizontalDistance, verticalDistance),
            };
        })
        .sort((first, second) => first.distance - second.distance)[0];
};

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
            domCards(container).map((card) => [card.dataset.organizationModule, card]),
        );
        const orderedCards = savedOrder
            .map((key) => availableCards.get(key))
            .filter(Boolean);
        const alreadyOrdered = new Set(orderedCards);
        domCards(container).forEach((card) => {
            if (!alreadyOrdered.has(card)) orderedCards.push(card);
        });
        applyOrder(orderedCards);
    } catch {
        localStorage.removeItem(STORAGE_KEY);
        applyOrder(domCards(container));
    }
};

const initializeOrganizationSortable = (container) => {
    if (container.dataset.organizationSortableReady === 'true') {
        restoreOrder(container);
        return;
    }
    container.dataset.organizationSortableReady = 'true';
    restoreOrder(container);

    container.addEventListener('pointerdown', (event) => {
        if (event.button !== 0 || event.target.closest('button, a, input, select, textarea, [role="button"]')) return;

        const draggedCard = event.target.closest(CARD_SELECTOR);
        if (!draggedCard || draggedCard.parentElement !== container) return;

        const origin = { x: event.clientX, y: event.clientY };
        let dragging = false;
        let dragPreview = null;
        let previewBounds = null;
        let lastReorderAt = 0;
        let lastTargetKey = null;

        const move = (moveEvent) => {
            if (moveEvent.pointerId !== event.pointerId) return;

            if (!dragging) {
                const distance = Math.hypot(moveEvent.clientX - origin.x, moveEvent.clientY - origin.y);
                if (distance < 7) return;
                dragging = true;
                document.body.style.userSelect = 'none';
                previewBounds = draggedCard.getBoundingClientRect();
                dragPreview = createDragPreview(draggedCard, previewBounds);
                draggedCard.style.opacity = '0.18';
            }

            moveEvent.preventDefault();
            if (dragPreview) {
                const deltaX = Math.round(moveEvent.clientX - origin.x);
                const deltaY = Math.round(moveEvent.clientY - origin.y);
                dragPreview.style.left = `${Math.round(previewBounds.left + deltaX)}px`;
                dragPreview.style.top = `${Math.round(previewBounds.top + deltaY)}px`;
            }

            // Wait until the previous FLIP transition is mostly settled. Reading
            // animated rectangles continuously near a row edge made the target
            // alternate between the last card and the first card of the next row.
            const now = performance.now();
            if (now - lastReorderAt < 170) return;

            const nearest = nearestCard(container, draggedCard, moveEvent.clientX, moveEvent.clientY);
            if (!nearest) return;

            const rowTolerance = Math.min(80, nearest.bounds.height * 0.25);
            const aboveRow = moveEvent.clientY < nearest.bounds.top - rowTolerance;
            const belowRow = moveEvent.clientY > nearest.bounds.bottom + rowTolerance;
            const placeBefore = aboveRow
                || (!belowRow && moveEvent.clientX < nearest.bounds.left + nearest.bounds.width / 2);
            const targetKey = `${nearest.card.dataset.organizationModule}:${placeBefore ? 'before' : 'after'}`;

            // Require the pointer to move beyond a small neutral band around a
            // card's centre before reversing the last placement decision.
            const horizontalOffset = Math.abs(moveEvent.clientX - (nearest.bounds.left + nearest.bounds.width / 2));
            if (targetKey !== lastTargetKey && horizontalOffset < 18 && !aboveRow && !belowRow) return;

            const currentOrder = cards(container);
            const draggedIndex = currentOrder.indexOf(draggedCard);
            const nearestIndex = currentOrder.indexOf(nearest.card);
            const targetIndex = placeBefore ? nearestIndex : nearestIndex + 1;
            const normalizedTargetIndex = targetIndex > draggedIndex ? targetIndex - 1 : targetIndex;
            if (normalizedTargetIndex === draggedIndex) return;

            const previousPositions = cardPositions(container);
            currentOrder.splice(draggedIndex, 1);
            currentOrder.splice(normalizedTargetIndex, 0, draggedCard);
            applyOrder(currentOrder);
            animateReorder(container, previousPositions);
            lastReorderAt = now;
            lastTargetKey = targetKey;
        };

        const finish = (finishEvent) => {
            if (finishEvent.pointerId !== event.pointerId) return;
            window.removeEventListener('pointermove', move);
            window.removeEventListener('pointerup', finish);
            window.removeEventListener('pointercancel', finish);

            if (!dragging) return;
            document.body.style.userSelect = '';
            saveOrder(container);

            const destination = draggedCard.getBoundingClientRect();
            if (dragPreview && previewBounds) {
                dragPreview.style.transition = 'left 220ms cubic-bezier(0.22, 1, 0.36, 1), top 220ms cubic-bezier(0.22, 1, 0.36, 1), opacity 180ms ease';
                dragPreview.style.left = `${Math.round(destination.left)}px`;
                dragPreview.style.top = `${Math.round(destination.top)}px`;
                dragPreview.style.opacity = '0';
                window.setTimeout(() => dragPreview?.remove(), 230);
            }

            window.setTimeout(() => {
                draggedCard.style.opacity = '';
            }, 180);
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
