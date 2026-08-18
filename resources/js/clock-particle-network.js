const initializeClockParticleNetwork = (root) => {
    const canvas = root.querySelector('[data-clock-network-canvas]');
    if (!canvas || canvas.dataset.initialized === 'true') return;

    const context = canvas.getContext('2d');
    if (!context) return;

    canvas.dataset.initialized = 'true';
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let width = 0;
    let height = 0;
    let points = [];
    let frame = null;

    const pointCount = () => Math.max(22, Math.min(58, Math.round((width * height) / 28000)));

    const createPoints = () => {
        points = Array.from({ length: pointCount() }, () => {
            const angle = Math.random() * Math.PI * 2;
            const speed = reduceMotion ? 0 : 0.07 + Math.random() * 0.09;

            return {
                x: Math.random() * width,
                y: Math.random() * height,
                vx: Math.cos(angle) * speed,
                vy: Math.sin(angle) * speed,
            };
        });
    };

    const resize = () => {
        const bounds = root.getBoundingClientRect();
        const ratio = Math.min(window.devicePixelRatio || 1, 2);
        width = Math.max(1, Math.round(bounds.width));
        height = Math.max(1, Math.round(bounds.height));
        canvas.width = Math.round(width * ratio);
        canvas.height = Math.round(height * ratio);
        canvas.style.width = `${width}px`;
        canvas.style.height = `${height}px`;
        context.setTransform(ratio, 0, 0, ratio, 0, 0);
        createPoints();
        draw();
    };

    const move = () => {
        points.forEach((point) => {
            point.x += point.vx;
            point.y += point.vy;

            if (point.x < -6) point.x = width + 6;
            if (point.x > width + 6) point.x = -6;
            if (point.y < -6) point.y = height + 6;
            if (point.y > height + 6) point.y = -6;
        });
    };

    function draw() {
        context.clearRect(0, 0, width, height);
        const connectionDistance = Math.min(175, Math.max(125, width / 9));

        points.forEach((first, firstIndex) => {
            const nearby = [];

            for (let secondIndex = firstIndex + 1; secondIndex < points.length; secondIndex += 1) {
                const second = points[secondIndex];
                const distance = Math.hypot(first.x - second.x, first.y - second.y);
                if (distance > connectionDistance) continue;

                const opacity = (1 - distance / connectionDistance) * 0.16;
                context.beginPath();
                context.moveTo(first.x, first.y);
                context.lineTo(second.x, second.y);
                context.strokeStyle = `rgba(24, 24, 27, ${opacity})`;
                context.lineWidth = 0.7;
                context.stroke();
                nearby.push({ point: second, distance });
            }

            if (nearby.length >= 2) {
                nearby.sort((a, b) => a.distance - b.distance);
                context.beginPath();
                context.moveTo(first.x, first.y);
                context.lineTo(nearby[0].point.x, nearby[0].point.y);
                context.lineTo(nearby[1].point.x, nearby[1].point.y);
                context.closePath();
                context.fillStyle = 'rgba(24, 24, 27, 0.025)';
                context.fill();
            }

            context.beginPath();
            context.arc(first.x, first.y, 1.7, 0, Math.PI * 2);
            context.fillStyle = 'rgba(24, 24, 27, 0.32)';
            context.fill();
        });
    }

    const animate = () => {
        move();
        draw();
        frame = window.requestAnimationFrame(animate);
    };

    const observer = new ResizeObserver(resize);
    observer.observe(root);
    resize();
    if (!reduceMotion) animate();

    window.addEventListener('pagehide', () => {
        if (frame) window.cancelAnimationFrame(frame);
        observer.disconnect();
    }, { once: true });
};

const initializeClockParticleNetworks = () => {
    document.querySelectorAll('[data-clock-particle-network]').forEach(initializeClockParticleNetwork);
};

document.addEventListener('DOMContentLoaded', initializeClockParticleNetworks);
document.addEventListener('livewire:navigated', initializeClockParticleNetworks);

