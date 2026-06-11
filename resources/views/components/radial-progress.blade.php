@props(['serviceId', 'customerId', 'percentageKey'])
<div x-data="{
    percentage: @entangle('percentages.' . $percentageKey),
    circumference: 2 * Math.PI * 46,
    get offset() {
        return this.circumference * (1 - (this.percentage / 100));
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
        <circle :stroke="color" :stroke-dasharray="circumference" :stroke-dashoffset="offset"
            cx="50" cy="50" r="46" stroke-width="8" fill="none"
            stroke-linecap="round" class="transition-all duration-500" />
    </svg>
    <span>
    </span>
    <span class="percentage-text absolute text-sm font-bold text-black"
        x-text="percentage + '%'"></span>
</div>
