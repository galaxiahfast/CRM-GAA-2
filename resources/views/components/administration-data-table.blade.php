{{-- Tabla de las listas de Administración: contenedor con borde, encabezado gris y filas separadas. --}}
<div class="overflow-hidden rounded-xl border border-zinc-200 bg-[#ffffff]">
    <div class="overflow-x-auto">
        <table class="w-full md:min-w-[640px] text-left text-[15px]">
            <thead class="border-b border-zinc-200 bg-[#F3F3F3] text-black">
                <tr>
                    {{ $head }}
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 text-black">
                {{ $body }}
            </tbody>
        </table>
    </div>
</div>
