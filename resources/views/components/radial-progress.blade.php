@props(['serviceId', 'customerId', 'percentageKey'])
<div x-data="{
    percentage: @entangle('percentages.' . $percentageKey),
    shown: 0,
    frame: null,
    circumference: 2 * Math.PI * 46,
    init() {
        // Los aros arrancan vacíos y solo los que tienen avance se van llenando poco a poco.
        this.$nextTick(() => this.fill());
        this.$watch('percentage', () => this.fill());
    },
    destroy() { if (this.frame) cancelAnimationFrame(this.frame); },
    fill() {
        const target = Math.max(0, Math.min(100, Number(this.percentage) || 0));
        const from = this.shown;
        if (this.frame) cancelAnimationFrame(this.frame);
        if (target === from) return;
        const duration = 800 + target * 16;
        const start = performance.now();
        const step = (now) => {
            const progress = Math.min(1, (now - start) / duration);
            const eased = 1 - Math.pow(1 - progress, 3);
            this.shown = from + (target - from) * eased;
            this.frame = progress < 1 ? requestAnimationFrame(step) : null;
        };
        this.frame = requestAnimationFrame(step);
    },
    get offset() {
        return this.circumference * (1 - (this.shown / 100));
    },
    get color() {
        if (this.percentage < 30) return 'red';
        if (this.percentage < 50) return 'orange';
        if (this.percentage < 80) return 'yellow';
        if (this.percentage < 100) return '#32CD32';
        return 'green';
    }
}" class="radial-progress relative flex items-center justify-center">
    <svg width="75" height="75" viewBox="0 0 100 100" class="-rotate-90 transform">
        <circle cx="50" cy="50" r="46" stroke="#e5e7eb" stroke-width="8" fill="none" />
        <circle :stroke="color" :stroke-dasharray="circumference" :stroke-dashoffset="offset" :stroke-opacity="shown > 0.5 ? 1 : 0"
            cx="50" cy="50" r="46" stroke-width="8" fill="none"
            stroke-linecap="round" />
    </svg>
    <span>
    </span>
    <span class="percentage-text absolute text-sm font-bold text-black"
        x-text="Math.round(shown) + '%'"></span>
</div>
