const CONTAINER_SELECTOR = '[data-organization-sortable]';
const CARD_SELECTOR = '[data-organization-module]';
const CAROUSEL_INTERVAL = 10000;
const CARD_WIDTH = 390;
const CARD_GAP = 20;
const CAROUSEL_SAFE_INSET = 98;
const CAROUSEL_REAR_INSET = 64;
const carouselAnimations = new WeakMap();
const carouselSettleFrames = new WeakMap();
const carouselMemory = window.organizationCarouselMemory ||= { order: [], centeredModule: '' };

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
    const container = orderedCards[0]?.parentElement;
    if (container?.matches(CONTAINER_SELECTOR)) {
        const order = orderedCards.map((card) => card.dataset.organizationModule);
        container.dataset.carouselOrder = JSON.stringify(order);
        carouselMemory.order = order;
    }
};

const restoreCarouselOrder = (container) => {
    const availableCards = new Map(
        domCards(container).map((card) => [card.dataset.organizationModule, card]),
    );
    let storedOrder = [];
    try {
        storedOrder = JSON.parse(container.dataset.carouselOrder || JSON.stringify(carouselMemory.order));
    } catch {
        storedOrder = [];
    }
    const orderedCards = storedOrder.map((module) => availableCards.get(module)).filter(Boolean);
    const restoredCards = new Set(orderedCards);
    domCards(container).forEach((card) => {
        if (!restoredCards.has(card)) orderedCards.push(card);
    });
    applyOrder(orderedCards);
    return orderedCards;
};

const settleCarouselAfterMorph = (container) => {
    const runningAnimation = carouselAnimations.get(container);
    if (runningAnimation) cancelAnimationFrame(runningAnimation);
    carouselAnimations.delete(container);
    container.dataset.carouselMoving = 'false';
    container.dataset.carouselQueue = '0';

    const orderedCards = restoreCarouselOrder(container);
    const activeModule = container.dataset.carouselCenteredModule || carouselMemory.centeredModule;
    const current = orderedCards.find((card) => card.dataset.organizationModule === activeModule)
        || orderedCards[0];
    if (!current) return;

    orderedCards.forEach((card) => { card.style.transition = 'none'; });
    balanceCardsAround(container, current);
    centerOnCard(container, current, 'auto');
    updateCarouselVisibility(container);
    requestAnimationFrame(() => requestAnimationFrame(() => {
        if (!container.isConnected) return;
        domCards(container).forEach((card) => { card.style.transition = ''; });
        updateCarouselVisibility(container);
    }));
};

const scheduleCarouselSettle = (container) => {
    const pendingFrame = carouselSettleFrames.get(container);
    if (pendingFrame) cancelAnimationFrame(pendingFrame);
    carouselSettleFrames.set(container, requestAnimationFrame(() => {
        carouselSettleFrames.delete(container);
        if (container.isConnected) settleCarouselAfterMorph(container);
    }));
};

const frontCardCount = (container) => Math.max(
    1,
    Math.min(
        Math.max(1, cards(container).length - 2),
        Math.floor((container.clientWidth - CAROUSEL_SAFE_INSET * 2 + CARD_GAP) / (CARD_WIDTH + CARD_GAP)),
    ),
);

const centeredCard = (container, orderedCards = cards(container)) => {
    const center = container.scrollLeft + container.clientWidth / 2;
    return orderedCards.reduce((nearest, card) => {
        const distance = Math.abs(card.offsetLeft + card.offsetWidth / 2 - center);
        return !nearest || distance < nearest.distance ? { card, distance } : nearest;
    }, null)?.card;
};

const centerOnCard = (container, card, behavior = 'smooth', duration = 720, continuous = false) => {
    if (!card) return;
    const visibleCount = frontCardCount(container);
    const groupWidth = visibleCount * CARD_WIDTH + Math.max(0, visibleCount - 1) * CARD_GAP;
    const destination = card.offsetLeft + groupWidth / 2 - container.clientWidth / 2;
    const runningAnimation = carouselAnimations.get(container);
    if (runningAnimation) cancelAnimationFrame(runningAnimation);

    if (behavior === 'auto') {
        container.scrollLeft = destination;
        updateCarouselVisibility(container);
        return;
    }

    const origin = container.scrollLeft;
    const distance = destination - origin;
    const startedAt = performance.now();
    const animate = (time) => {
        if (!container.isConnected) return;
        const progress = Math.min(1, (time - startedAt) / duration);
        const eased = continuous ? progress : 1 - Math.pow(1 - progress, 4);
        container.scrollLeft = origin + distance * eased;
        updateCarouselVisibility(container);
        if (progress < 1) {
            carouselAnimations.set(container, requestAnimationFrame(animate));
        } else {
            carouselAnimations.delete(container);
            container.scrollLeft = destination;
            updateCarouselVisibility(container);
        }
    };
    carouselAnimations.set(container, requestAnimationFrame(animate));
};

const balanceCardsAround = (container, centered) => {
    if (!centered) return cards(container);

    let orderedCards = cards(container);
    const beforeNeeded = orderedCards.length > 2 ? 1 : 0;
    const afterNeeded = Math.min(orderedCards.length - beforeNeeded - 1, frontCardCount(container));
    let centeredIndex = orderedCards.indexOf(centered);

    while (centeredIndex < beforeNeeded) {
        const previousLeft = centered.offsetLeft;
        orderedCards = [orderedCards.at(-1), ...orderedCards.slice(0, -1)];
        applyOrder(orderedCards);
        container.scrollLeft += centered.offsetLeft - previousLeft;
        centeredIndex += 1;
    }

    while (orderedCards.length - centeredIndex - 1 < afterNeeded) {
        const previousLeft = centered.offsetLeft;
        orderedCards = [...orderedCards.slice(1), orderedCards[0]];
        applyOrder(orderedCards);
        container.scrollLeft += centered.offsetLeft - previousLeft;
        centeredIndex -= 1;
    }

    return orderedCards;
};

const moveCyclicCarousel = (container, direction, automatic = false, fast = false) => {
    if (!container) return;
    if (container.dataset.carouselMoving === 'true') {
        if (!automatic) {
            const queued = Number(container.dataset.carouselQueue || 0);
            container.dataset.carouselQueue = String(Math.max(-12, Math.min(12, queued + direction)));
        }
        return;
    }
    let orderedCards = cards(container);
    if (orderedCards.length < 2) return;

    const activeModule = container.dataset.carouselCenteredModule;
    const current = orderedCards.find((card) => card.dataset.organizationModule === activeModule)
        || centeredCard(container, orderedCards)
        || orderedCards[0];
    orderedCards = balanceCardsAround(container, current);
    const currentIndex = orderedCards.indexOf(current);
    let target = orderedCards[currentIndex + direction];

    if (!target) {
        const previousLeft = current.offsetLeft;
        const rotated = direction > 0
            ? [...orderedCards.slice(1), orderedCards[0]]
            : [orderedCards.at(-1), ...orderedCards.slice(0, -1)];
        applyOrder(rotated);

        const positionDelta = current.offsetLeft - previousLeft;
        container.scrollLeft += positionDelta;
        target = direction > 0 ? rotated.at(-1) : rotated[0];
    }

    // Recycle cards outside the visible area before moving. This keeps the
    // next cards physically adjacent at both ends instead of teleporting a
    // distant card across the viewport when the circular order wraps.
    container.dataset.carouselRebalancing = 'true';
    balanceCardsAround(container, target);
    container.dataset.carouselRebalancing = 'false';
    container.dataset.carouselMoving = 'true';
    container.dataset.carouselNextAt = String(performance.now() + CAROUSEL_INTERVAL);
    container.dataset.carouselCenteredModule = target.dataset.organizationModule;
    carouselMemory.centeredModule = target.dataset.organizationModule;
    centerOnCard(container, target, 'smooth', fast ? 300 : 720, fast);
    window.setTimeout(() => {
        if (!container.isConnected) return;
        container.dataset.carouselMoving = 'false';
        const queued = Number(container.dataset.carouselQueue || 0);
        if (queued !== 0) {
            const queuedDirection = Math.sign(queued);
            container.dataset.carouselQueue = String(queued - queuedDirection);
            moveCyclicCarousel(container, queuedDirection, false, true);
        }
    }, fast ? 320 : 760);
};

window.organizationCarouselMove = (track, direction) => moveCyclicCarousel(track, direction, false);

const updateCarouselVisibility = (container) => {
    if (container.dataset.carouselRebalancing === 'true') return;
    const viewport = container.getBoundingClientRect();
    const safeLeft = viewport.left + CAROUSEL_SAFE_INSET;
    const safeRight = viewport.right - CAROUSEL_SAFE_INSET;
    const orderedCards = cards(container);
    const activeModule = container.dataset.carouselCenteredModule;
    const anchor = orderedCards.find((card) => card.dataset.organizationModule === activeModule)
        || centeredCard(container, orderedCards)
        || orderedCards[0];
    const anchorIndex = Math.max(0, orderedCards.indexOf(anchor));
    const visibleCount = frontCardCount(container);
    const frontCards = new Set(
        Array.from({ length: visibleCount }, (_, index) => orderedCards[(anchorIndex + index) % orderedCards.length]),
    );
    const leftEdgeCard = orderedCards[(anchorIndex - 1 + orderedCards.length) % orderedCards.length];
    const rightEdgeCard = orderedCards[(anchorIndex + visibleCount) % orderedCards.length];
    const measurements = domCards(container).map((card) => {
        const width = card.offsetWidth;
        const untransformedLeft = viewport.left + card.offsetLeft - container.scrollLeft;
        const untransformedRight = untransformedLeft + width;
        return { card, left: untransformedLeft, right: untransformedRight };
    });
    const viewportCenter = viewport.left + viewport.width / 2;
    const clamp = (value, minimum, maximum) => Math.min(maximum, Math.max(minimum, value));
    measurements.forEach((measurement) => {
        const { card, left, right } = measurement;
        const isFrontCard = frontCards.has(card);
        const isLeftEdge = card === leftEdgeCard;
        const isRightEdge = card === rightEdgeCard;
        const participates = isFrontCard || isLeftEdge || isRightEdge;
        const width = Math.max(card.offsetWidth, 1);
        const cardCenter = left + width / 2;
        const side = cardCenter < viewportCenter ? -1 : 1;
        let progress = 0;

        if (participates) {
            if (left < safeLeft) {
                progress = clamp((right - safeLeft) / width, 0, 1);
            } else if (right > safeRight) {
                progress = clamp((safeRight - left) / width, 0, 1);
            } else {
                progress = 1;
            }
        }

        const scale = participates ? 0.86 + 0.14 * progress : 0.86;
        const opacity = participates ? 0.58 + 0.42 * progress : 0;
        const depth = participates ? -220 * (1 - progress) : -220;
        const rotation = participates ? side * -4.5 * (1 - progress) : 0;
        let edgeShift = 0;
        const zIndex = participates ? Math.round(10 + 40 * progress) : 0;

        if (participates && progress < 1) {
            const rearScaleInset = width * (1 - 0.86) / 2;
            const rearShift = side < 0
                ? viewport.left + CAROUSEL_REAR_INSET - left - rearScaleInset
                : viewport.right - CAROUSEL_REAR_INSET - right + rearScaleInset;
            edgeShift = rearShift * (1 - progress);
        }

        card.style.setProperty('--carousel-scale', String(scale));
        card.style.setProperty('--carousel-opacity', String(opacity));
        card.style.setProperty('--carousel-depth', `${depth}px`);
        card.style.setProperty('--carousel-rotation', `${rotation}deg`);
        card.style.setProperty('--carousel-edge-shift', `${Math.round(edgeShift)}px`);
        card.style.zIndex = String(zIndex);
        // Una tarjeta marcada al frente debe ser interactiva completa. Exigir
        // que estuviera centrada al 99.5 % anulaba clics legítimos cerca de
        // los laterales o justo después de terminar una transición.
        const interactive = isFrontCard && progress > 0;
        card.style.pointerEvents = interactive ? '' : 'none';
        card.setAttribute('aria-hidden', interactive ? 'false' : 'true');
    });
};

const initializeCarouselVisibility = (container) => {
    if (container.dataset.carouselVisibilityReady === 'true') {
        updateCarouselVisibility(container);
        return;
    }

    container.dataset.carouselVisibilityReady = 'true';
    let animationFrame = null;
    const scheduleUpdate = () => {
        if (animationFrame) return;
        animationFrame = requestAnimationFrame(() => {
            animationFrame = null;
            updateCarouselVisibility(container);
        });
    };

    container.addEventListener('scroll', scheduleUpdate, { passive: true });
    new ResizeObserver(scheduleUpdate).observe(container);
    scheduleUpdate();
};

const initializeAutoCarousel = (container) => {
    if (container.dataset.carouselAutoReady === 'true') return;
    container.dataset.carouselAutoReady = 'true';
    container.dataset.carouselNextAt = String(performance.now() + CAROUSEL_INTERVAL);

    requestAnimationFrame(() => {
        if (!container.dataset.carouselOrder && carouselMemory.order.length) {
            container.dataset.carouselOrder = JSON.stringify(carouselMemory.order);
            restoreCarouselOrder(container);
        }
        const initialCard = cards(container).find(
            (card) => card.dataset.organizationModule === carouselMemory.centeredModule,
        ) || cards(container)[0];
        container.dataset.carouselCenteredModule = initialCard?.dataset.organizationModule || '';
        carouselMemory.centeredModule = container.dataset.carouselCenteredModule;
        centerOnCard(container, initialCard, 'auto');
        balanceCardsAround(container, initialCard);
        centerOnCard(container, initialCard, 'auto');
        updateCarouselVisibility(container);
    });

    let wasModalOpen = false;
    const schedule = () => {
        if (!container.isConnected) {
            responsiveObserver?.disconnect();
            responsiveAbortController.abort();
            return;
        }
        const now = performance.now();
        const modalOpen = [...document.querySelectorAll('[data-administration-modal], [role="dialog"][aria-modal="true"]')]
            .some((modal) => modal.getClientRects().length > 0);
        if (modalOpen) {
            const runningAnimation = carouselAnimations.get(container);
            if (runningAnimation) cancelAnimationFrame(runningAnimation);
            carouselAnimations.delete(container);
            container.dataset.carouselMoving = 'false';
            container.dataset.carouselQueue = '0';
            wasModalOpen = true;
            container.dataset.carouselNextAt = String(now + CAROUSEL_INTERVAL);
        } else if (wasModalOpen) {
            settleCarouselAfterMorph(container);
            container.dataset.carouselNextAt = String(now + CAROUSEL_INTERVAL);
            wasModalOpen = false;
        } else if (!document.hidden && now >= Number(container.dataset.carouselNextAt || 0)) {
            moveCyclicCarousel(container, 1, true);
        }
        window.setTimeout(schedule, 250);
    };

    const postpone = () => {
        container.dataset.carouselNextAt = String(performance.now() + CAROUSEL_INTERVAL);
    };
    container.addEventListener('pointerdown', postpone, { passive: true });
    container.addEventListener('wheel', postpone, { passive: true });
    container.addEventListener('touchstart', postpone, { passive: true });
    container.addEventListener('focusin', postpone);
    let resizeTimer = null;
    const refreshResponsiveLayout = () => {
        const runningAnimation = carouselAnimations.get(container);
        if (runningAnimation) cancelAnimationFrame(runningAnimation);
        carouselAnimations.delete(container);
        container.dataset.carouselMoving = 'false';
        window.clearTimeout(resizeTimer);
        resizeTimer = window.setTimeout(() => {
            if (!container.isConnected) return;
            const centeredModule = container.dataset.carouselCenteredModule;
            const current = cards(container).find((card) => card.dataset.organizationModule === centeredModule)
                || centeredCard(container);
            cards(container).forEach((card) => { card.style.transition = 'none'; });
            balanceCardsAround(container, current);
            centerOnCard(container, current, 'auto');
            updateCarouselVisibility(container);
            postpone();
            requestAnimationFrame(() => requestAnimationFrame(() => {
                if (!container.isConnected) return;
                cards(container).forEach((card) => { card.style.transition = ''; });
                updateCarouselVisibility(container);
            }));
        }, 160);
    };
    const responsiveAbortController = new AbortController();
    const responsiveObserver = new ResizeObserver(refreshResponsiveLayout);
    responsiveObserver.observe(container);
    if (container.parentElement) responsiveObserver.observe(container.parentElement);
    window.addEventListener('resize', refreshResponsiveLayout, { passive: true, signal: responsiveAbortController.signal });
    schedule();
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
        const carouselTransform = 'translate3d(var(--carousel-edge-shift, 0px), 0, var(--carousel-depth, 0px)) scale(var(--carousel-scale, 1)) rotateY(var(--carousel-rotation, 0deg))';

        card.getAnimations().forEach((animation) => animation.cancel());
        card.animate(
            [
                { transform: `translate(${offsetX}px, ${offsetY}px) ${carouselTransform}` },
                { transform: carouselTransform },
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
    initializeCarouselVisibility(container);
    initializeAutoCarousel(container);
    if (container.dataset.organizationSortableReady === 'true') {
        scheduleCarouselSettle(container);
        return;
    }
    container.dataset.organizationSortableReady = 'true';
    applyOrder(domCards(container));
    localStorage.removeItem('organization-module-order');

    container.addEventListener('click', (event) => {
        if (performance.now() < Number(container.dataset.suppressCarouselClickUntil || 0)) {
            event.preventDefault();
            event.stopPropagation();
        }
    }, true);

    // El carrusel se navega únicamente con las flechas o el avance automático.
    // No registrar gestos de arrastre evita movimientos accidentales sobre las tarjetas.
    return;

    container.addEventListener('pointerdown', (event) => {
        if (event.button !== 0 || event.target.closest('button, a, input, select, textarea, [role="button"]')) return;

        const originX = event.clientX;
        const originY = event.clientY;
        const startedAt = performance.now();
        let draggingCarousel = false;
        let lastX = originX;

        const moveCarousel = (moveEvent) => {
            if (moveEvent.pointerId !== event.pointerId) return;
            const deltaX = moveEvent.clientX - originX;
            const deltaY = moveEvent.clientY - originY;
            if (!draggingCarousel) {
                if (Math.hypot(deltaX, deltaY) < 7 || Math.abs(deltaX) <= Math.abs(deltaY)) return;
                draggingCarousel = true;
                document.body.style.userSelect = 'none';
                container.style.cursor = 'grabbing';
            }
            moveEvent.preventDefault();
            lastX = moveEvent.clientX;
        };

        const finishCarousel = (finishEvent) => {
            if (finishEvent.pointerId !== event.pointerId) return;
            window.removeEventListener('pointermove', moveCarousel);
            window.removeEventListener('pointerup', finishCarousel);
            window.removeEventListener('pointercancel', finishCarousel);
            if (!draggingCarousel) return;

            document.body.style.userSelect = '';
            container.style.cursor = '';
            container.dataset.suppressCarouselClickUntil = String(performance.now() + 250);
            const distance = lastX - originX;
            if (Math.abs(distance) < 45) return;
            const elapsed = Math.max(performance.now() - startedAt, 1);
            const velocity = Math.abs(distance) / elapsed;
            const direction = distance < 0 ? 1 : -1;
            moveCyclicCarousel(container, direction, false, velocity > 0.8);
        };

        window.addEventListener('pointermove', moveCarousel, { passive: false });
        window.addEventListener('pointerup', finishCarousel);
        window.addEventListener('pointercancel', finishCarousel);
    });

    return;

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
    const parentContainer = root.closest?.(CONTAINER_SELECTOR);
    if (parentContainer) initializeOrganizationSortable(parentContainer);
    root.querySelectorAll?.(CONTAINER_SELECTOR).forEach(initializeOrganizationSortable);
};

document.addEventListener('DOMContentLoaded', () => initializeOrganizationSortables());
document.addEventListener('livewire:navigated', () => initializeOrganizationSortables());
document.addEventListener('livewire:init', () => {
    initializeOrganizationSortables();
    window.Livewire.hook('morph.updated', ({ el }) => initializeOrganizationSortables(el));
});
