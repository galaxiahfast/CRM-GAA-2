{{-- Cronómetro y productividad en una sola pantalla: el cronómetro primero y los bloques de productividad debajo.
     Cada bloque conserva su lógica; aquí solo se unen y se iguala la separación entre ellos (20, 30 y 50px como Control Gerencial). --}}
<div data-activity-hub class="flex w-full min-w-0 flex-col">
    <style>
        [data-activity-hub] > [data-time-clock][data-with-productivity] > main { padding-bottom: 0; }
        [data-activity-hub] > [data-productivity] > main { padding-top: 20px; }
        @media (min-width: 640px) { [data-activity-hub] > [data-productivity] > main { padding-top: 30px; } }
        @media (min-width: 1280px) { [data-activity-hub] > [data-productivity] > main { padding-top: 50px; } }
    </style>

    <livewire:time-control.registro-actividades :embedded="true" :with-productivity="$showProductivity" wire:key="hub-clock" />

    @if ($showProductivity)
        <div id="productividad" class="scroll-mt-[143px]"></div>
        <livewire:time-control.my-productivity :embedded="true" wire:key="hub-productivity" />
    @endif
</div>
