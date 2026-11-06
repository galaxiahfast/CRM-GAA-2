const initializeAuthParticleNetwork = () => {
    const root = document.querySelector('[data-auth-particle-network]');
    const canvas = root?.querySelector('[data-auth-network-canvas]');

    if (!root || !canvas || canvas.dataset.initialized === 'true') {
        return;
    }

    const context = canvas.getContext('2d');

    if (!context) {
        return;
    }

    canvas.dataset.initialized = 'true';

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    // clearance: distancia mínima absoluta entre una bolita y el puntero (ninguna puede acercarse más).
    const pointer = { x: 0, y: 0, lastX: 0, lastY: 0, lead: 0, active: false, radius: 120, clearance: 24 };
    let width = 0;
    let height = 0;
    let points = [];
    let animationFrame = null;
    let currentPixelRatio = 0;

    const targetDensity = () => Math.max(30, Math.min(120, Math.round((width * height) / 10000)));

    const createPoint = (x, y) => {
        const angle = Math.random() * Math.PI * 2;
        const driftSpeed = reduceMotion ? 0 : 0.07 + Math.random() * 0.1;

        return {
            x,
            y,
            vx: Math.cos(angle) * driftSpeed,
            vy: Math.sin(angle) * driftSpeed,
            driftAngle: angle,
            driftSpeed,
            turnDirection: Math.random() > 0.5 ? 1 : -1,
            size: 1.4,
        };
    };

    const createInitialPoints = () => {
        const density = targetDensity();
        const columns = Math.ceil(Math.sqrt((density * width) / height));
        const rows = Math.ceil(density / columns);
        const cellWidth = width / columns;
        const cellHeight = height / rows;

        points = Array.from({ length: density }, (_, index) => {
            const column = index % columns;
            const row = Math.floor(index / columns);

            return createPoint(
                (column + 0.18 + Math.random() * 0.64) * cellWidth,
                (row + 0.18 + Math.random() * 0.64) * cellHeight,
            );
        });
    };

    const addDistributedPoint = () => {
        let bestCandidate = null;
        let bestDistance = -1;

        for (let attempt = 0; attempt < 10; attempt += 1) {
            const candidate = {
                x: Math.random() * width,
                y: Math.random() * height,
            };
            const nearestDistance = points.reduce((nearest, point) => (
                Math.min(nearest, Math.hypot(candidate.x - point.x, candidate.y - point.y))
            ), Number.POSITIVE_INFINITY);

            if (nearestDistance > bestDistance) {
                bestCandidate = candidate;
                bestDistance = nearestDistance;
            }
        }

        points.push(createPoint(bestCandidate.x, bestCandidate.y));
    };

    const reconcileDensity = () => {
        const desiredCount = targetDensity();

        if (points.length > desiredCount) {
            points.splice(desiredCount);
        }

        while (points.length < desiredCount) {
            addDistributedPoint();
        }
    };

    const resize = () => {
        const bounds = root.getBoundingClientRect();
        const pixelRatio = Math.min(window.devicePixelRatio || 1, 2);
        const nextWidth = Math.max(1, Math.round(bounds.width));
        const nextHeight = Math.max(1, Math.round(bounds.height));

        if (nextWidth === width && nextHeight === height && pixelRatio === currentPixelRatio) {
            return;
        }

        const previousWidth = width;
        const previousHeight = height;

        width = nextWidth;
        height = nextHeight;
        currentPixelRatio = pixelRatio;

        if (points.length === 0) {
            createInitialPoints();
        } else {
            const scaleX = previousWidth > 0 ? width / previousWidth : 1;
            const scaleY = previousHeight > 0 ? height / previousHeight : 1;

            points.forEach((point) => {
                point.x *= scaleX;
                point.y *= scaleY;
            });

            reconcileDensity();
        }

        canvas.dataset.particleCount = String(points.length);

        canvas.width = Math.round(width * pixelRatio);
        canvas.height = Math.round(height * pixelRatio);
        canvas.style.width = `${width}px`;
        canvas.style.height = `${height}px`;
        context.setTransform(pixelRatio, 0, 0, pixelRatio, 0, 0);
        draw();
    };

    const movePoints = () => {
        const separationDistance = 30;

        for (let firstIndex = 0; firstIndex < points.length; firstIndex += 1) {
            for (let secondIndex = firstIndex + 1; secondIndex < points.length; secondIndex += 1) {
                const first = points[firstIndex];
                const second = points[secondIndex];
                const dx = first.x - second.x;
                const dy = first.y - second.y;
                const distance = Math.hypot(dx, dy) || 1;

                if (distance >= separationDistance) continue;

                const separation = (1 - distance / separationDistance) * 0.012;
                const forceX = (dx / distance) * separation;
                const forceY = (dy / distance) * separation;
                first.vx += forceX;
                first.vy += forceY;
                second.vx -= forceX;
                second.vy -= forceY;
            }
        }

        // Si el puntero se mueve rápido, el área de rechazo se amplía para que las bolitas se aparten antes de que llegue.
        if (pointer.active) {
            const travel = Math.hypot(pointer.x - pointer.lastX, pointer.y - pointer.lastY);
            pointer.lastX = pointer.x;
            pointer.lastY = pointer.y;
            pointer.lead += (Math.min(travel, 40) - pointer.lead) * 0.25;
        } else {
            pointer.lead = 0;
        }

        const reach = pointer.radius + pointer.lead * 1.0;

        points.forEach((point) => {
            point.driftAngle += point.turnDirection * 0.0012;
            const driftX = Math.cos(point.driftAngle) * point.driftSpeed;
            const driftY = Math.sin(point.driftAngle) * point.driftSpeed;
            point.vx += (driftX - point.vx) * 0.05;
            point.vy += (driftY - point.vy) * 0.05;

            if (pointer.active) {
                const dx = point.x - pointer.x;
                const dy = point.y - pointer.y;
                const distance = Math.hypot(dx, dy) || 1;

                if (distance < reach) {
                    // Rechazo inmediato y muy marcado cerca del puntero, con caída suave hacia el borde del radio.
                    const proximity = 1 - distance / reach;
                    const force = proximity * proximity * 0.9 + proximity * 0.25;
                    point.vx += (dx / distance) * force;
                    point.vy += (dy / distance) * force;
                }
            }

            const speed = Math.hypot(point.vx, point.vy);
            const maxSpeed = 2.5;

            if (speed > maxSpeed) {
                point.vx = (point.vx / speed) * maxSpeed;
                point.vy = (point.vy / speed) * maxSpeed;
            }

            point.x += point.vx;
            point.y += point.vy;

            if (point.x < -8) point.x = width + 8;
            if (point.x > width + 8) point.x = -8;
            if (point.y < -8) point.y = height + 8;
            if (point.y > height + 8) point.y = -8;

            if (pointer.active) {
                // Zona de exclusión dura: ninguna bolita puede quedar a menos de `clearance` del puntero.
                let dx = point.x - pointer.x;
                let dy = point.y - pointer.y;
                let distance = Math.hypot(dx, dy);

                if (distance < pointer.clearance) {
                    if (distance < 0.001) {
                        const angle = Math.random() * Math.PI * 2;
                        dx = Math.cos(angle);
                        dy = Math.sin(angle);
                        distance = 1;
                    }

                    const nx = dx / distance;
                    const ny = dy / distance;
                    const inward = point.vx * nx + point.vy * ny;

                    point.x = pointer.x + nx * pointer.clearance;
                    point.y = pointer.y + ny * pointer.clearance;

                    // Se anula la velocidad que la empujaba hacia el puntero y se le da un impulso de salida.
                    if (inward < 0) {
                        point.vx -= inward * nx;
                        point.vy -= inward * ny;
                    }

                    point.vx += nx * 0.5;
                    point.vy += ny * 0.5;
                }
            }
        });
    };

    // Colores según el tema activo (negro / azul), leídos en cada cuadro para que el cambio sea inmediato.
    let palette = { rgb: '212, 212, 216', line: 0.22, fill: 0.025, dot: 0.55 };

    const updatePalette = () => {
        palette = root.dataset.authTheme === 'blue'
            ? { rgb: '186, 214, 255', line: 0.22, fill: 0, dot: 0.55 }
            : { rgb: '212, 212, 216', line: 0.16, fill: 0, dot: 0.4 };
    };

    const drawConnections = () => {
        const connectionDistance = Math.min(120, Math.max(90, width / 13));

        for (let firstIndex = 0; firstIndex < points.length; firstIndex += 1) {
            const first = points[firstIndex];
            const nearby = [];

            for (let secondIndex = firstIndex + 1; secondIndex < points.length; secondIndex += 1) {
                const second = points[secondIndex];
                const distance = Math.hypot(first.x - second.x, first.y - second.y);

                if (distance > connectionDistance) continue;

                const opacity = (1 - distance / connectionDistance) * palette.line;
                context.beginPath();
                context.moveTo(first.x, first.y);
                context.lineTo(second.x, second.y);
                context.strokeStyle = `rgba(${palette.rgb}, ${opacity})`;
                context.lineWidth = 0.7;
                context.stroke();
                nearby.push({ point: second, distance });
            }

            if (nearby.length >= 2) {
                nearby.sort((a, b) => a.distance - b.distance);
                const second = nearby[0].point;
                const third = nearby[1].point;

                if (Math.hypot(second.x - third.x, second.y - third.y) < connectionDistance) {
                    context.beginPath();
                    context.moveTo(first.x, first.y);
                    context.lineTo(second.x, second.y);
                    context.lineTo(third.x, third.y);
                    context.closePath();
                    context.fillStyle = `rgba(${palette.rgb}, ${palette.fill})`;
                    context.fill();
                }
            }
        }
    };

    function draw() {
        context.clearRect(0, 0, width, height);
        updatePalette();
        drawConnections();

        points.forEach((point) => {
            context.beginPath();
            context.arc(point.x, point.y, point.size, 0, Math.PI * 2);
            context.fillStyle = `rgba(${palette.rgb}, ${palette.dot})`;
            context.fill();
        });

    }

    const animate = () => {
        if (document.hidden) {
            animationFrame = null;
            return;
        }
        movePoints();
        draw();
        animationFrame = window.requestAnimationFrame(animate);
    };

    root.addEventListener('pointermove', (event) => {
        const bounds = root.getBoundingClientRect();
        pointer.x = event.clientX - bounds.left;
        pointer.y = event.clientY - bounds.top;

        if (!pointer.active) {
            pointer.lastX = pointer.x;
            pointer.lastY = pointer.y;
            pointer.lead = 0;
        }

        pointer.active = true;

        if (reduceMotion) {
            draw();
        }
    });

    root.addEventListener('pointerleave', () => {
        pointer.active = false;

        if (reduceMotion) {
            draw();
        }
    });

    new MutationObserver(draw).observe(root, { attributes: true, attributeFilter: ['data-auth-theme'] });

    const resizeObserver = new ResizeObserver(resize);
    resizeObserver.observe(root);
    resize();

    if (!reduceMotion) {
        animate();
    }

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden && !reduceMotion && animationFrame === null) {
            animate();
        }
    });

    window.addEventListener('pagehide', () => {
        if (animationFrame) window.cancelAnimationFrame(animationFrame);
        resizeObserver.disconnect();
    }, { once: true });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeAuthParticleNetwork, { once: true });
} else {
    initializeAuthParticleNetwork();
}
