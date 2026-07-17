@props(['node', 'depth' => 0])

<li class="relative pl-0 {{ $depth > 0 ? 'mt-3' : '' }}">
    @if ($depth > 0)
        <span class="absolute -left-4 top-5 h-px w-4 bg-gray-300" aria-hidden="true"></span>
        <span class="absolute -left-4 top-0 bottom-5 w-px bg-gray-300" aria-hidden="true"></span>
    @endif

    <button type="button" wire:key="org-node-{{ $node['id'] }}" wire:click="selectUser({{ (int) $node['id'] }})"
        class="inline-flex min-w-[220px] flex-col rounded-xl border border-gray-200 bg-white px-4 py-3 text-left shadow-sm transition hover:border-blue-200 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        <div class="flex items-start justify-between gap-2">
            <div>
                <p class="text-sm font-semibold text-gray-800">{{ $node['name'] }}</p>
                <p class="text-xs text-gray-500">{{ $node['email'] }}</p>
            </div>
            @if (($node['superior_count'] ?? 0) > 1)
                <span class="shrink-0 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-medium text-amber-700"
                    title="Reporta a múltiples superiores">
                    {{ $node['superior_count'] }} jefes
                </span>
            @endif
        </div>

        <div class="mt-2 flex flex-wrap gap-1">
            @if (! empty($node['role']))
                <span class="rounded-md bg-blue-50 px-2 py-0.5 text-[10px] font-medium text-blue-700">{{ $node['role'] }}</span>
            @endif
            @if (! empty($node['job_position']))
                <span class="rounded-md bg-emerald-50 px-2 py-0.5 text-[10px] font-medium text-emerald-700">{{ $node['job_position'] }}</span>
            @endif
            @if (! empty($node['physical_area']))
                <span class="rounded-md bg-violet-50 px-2 py-0.5 text-[10px] font-medium text-violet-700">{{ $node['physical_area'] }}</span>
            @endif
        </div>

        @if (($node['subordinate_count'] ?? 0) > 0)
            <p class="mt-2 text-[10px] text-gray-400">{{ $node['subordinate_count'] }} subordinado(s) directo(s)</p>
        @endif
    </button>

    @if (! empty($node['children']))
        <ul class="relative ml-6 mt-2 space-y-2 border-l border-gray-200 pl-6">
            @foreach ($node['children'] as $child)
                <x-administracion.organigrama-node :node="$child" :depth="$depth + 1" />
            @endforeach
        </ul>
    @endif
</li>
