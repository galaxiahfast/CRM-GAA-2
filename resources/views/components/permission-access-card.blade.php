@props([
    'permission',
    'selected' => false,
    'editable' => false,
])

@php
    $key = (string) $permission->key;
    $configuredVisual = collect(config('access-permissions.catalog', []))->firstWhere('key', $key)['visual'] ?? null;
    $visual = $configuredVisual ?: match (true) {
        str_contains($key, 'organization') => 'organization',
        str_contains($key, 'users') => 'users',
        str_contains($key, 'roles') => 'roles',
        str_contains($key, 'permissions') => 'permissions',
        str_contains($key, 'assignments') => 'assignments',
        str_contains($key, 'customers') => 'customers',
        str_contains($key, 'activities') => 'activities',
        str_contains($key, 'clock') => 'clock',
        str_contains($key, 'productivity') || str_contains($key, 'supervision') => 'analytics',
        default => 'module',
    };
    $inputId = 'permission-access-'.$permission->id.'-'.($editable ? 'editable' : 'preview');
@endphp

<article
    {{ $attributes }}
    x-data="{
        previewId: @js($key.'-'.$permission->id),
        previewOpen: false,
        previewPinned: false,
        previewStyle: '',
        positionPreview() {
            const bounds = this.$refs.previewTrigger.getBoundingClientRect();
            const width = Math.min(320, window.innerWidth - 24);
            let left = bounds.right + 12;
            if (left + width > window.innerWidth - 12) left = Math.max(12, bounds.left - width - 12);
            const top = Math.min(Math.max(12, bounds.top), Math.max(12, window.innerHeight - 230));
            this.previewStyle = `left: ${left}px; top: ${top}px; width: ${width}px;`;
        },
        showPreview(pin = false) {
            this.previewPinned = pin || this.previewPinned;
            this.previewOpen = true;
            this.$dispatch('permission-preview-open', this.previewId);
            this.$nextTick(() => this.positionPreview());
        },
        togglePreview() {
            if (this.previewPinned) {
                this.previewPinned = false;
                this.previewOpen = false;
                return;
            }
            this.showPreview(true);
        },
    }"
    x-ref="previewTrigger"
    @mouseenter="showPreview(false)"
    @mouseleave="if (! previewPinned) previewOpen = false"
    @keydown.escape="previewPinned = false; previewOpen = false"
    @permission-preview-open.window="if ($event.detail !== previewId) { previewPinned = false; previewOpen = false }"
    @scroll.window="if (previewOpen) positionPreview()"
    @resize.window="if (previewOpen) positionPreview()"
    @class([
        'relative min-w-0 rounded-xl border p-3.5 transition-colors',
        'border-[#1A3A6B] bg-[#EAF2FC]' => $selected,
        'border-[#D7E2F0] bg-white/65' => ! $selected,
        'has-[:checked]:border-[#1A3A6B] has-[:checked]:bg-[#EAF2FC]' => $editable,
        'opacity-45' => ! $selected && ! $editable,
    ])
>
    <div class="flex min-w-0 items-start gap-3">
        @if ($editable)
            <input id="{{ $inputId }}" type="checkbox" value="{{ $permission->id }}" wire:model="permissionIds" class="mt-1 h-4 w-4 shrink-0 rounded border-[#9CB9DC] text-[#1A3A6B] focus:ring-0">
        @else
            <span class="mt-1 flex h-4 w-4 shrink-0 items-center justify-center rounded border {{ $selected ? 'border-[#1A3A6B] bg-[#1A3A6B] text-white' : 'border-[#B7C5D8] bg-white text-transparent' }}">
                <svg class="h-3 w-3" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m5 10 3 3 7-7" /></svg>
            </span>
        @endif

        <label for="{{ $inputId }}" class="flex min-w-0 flex-1 cursor-pointer items-start gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-[#B7CEEA] bg-[#EEF5FF] text-[#1A3A6B]">
                @if ($visual === 'users')
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m7-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8m13 10v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" /></svg>
                @elseif ($visual === 'roles')
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3 5 6v5c0 4.4 2.8 8.3 7 10 4.2-1.7 7-5.6 7-10V6l-7-3Zm-3 8 2 2 4-4" /></svg>
                @elseif ($visual === 'permissions')
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 11V8a5 5 0 0 1 10 0v3m-9 0h8a2 2 0 0 1 2 2v7H6v-7a2 2 0 0 1 2-2Z" /></svg>
                @elseif ($visual === 'organization')
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 21V8l8-4 8 4v13M8 12h.01M12 12h.01M16 12h.01M8 16h.01M12 16h.01M16 16h.01" /></svg>
                @elseif ($visual === 'assignments')
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 7h10M7 17h10M5 5a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm14 10a2 2 0 1 0 0 4 2 2 0 0 0 0-4" /></svg>
                @elseif ($visual === 'customers')
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2m7.5-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8M18 8v6m3-3h-6" /></svg>
                @elseif ($visual === 'activities')
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5h6m-7-2h8a2 2 0 0 1 2 2v16H6V5a2 2 0 0 1 2-2Zm1 7h6m-6 4h6m-6 4h4" /></svg>
                @elseif ($visual === 'clock')
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 7v5l3 2m6-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                @elseif ($visual === 'analytics')
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 19V9m5 10V5m5 14v-7m5 7V3" /></svg>
                @else
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 4h6v6H4V4Zm10 0h6v6h-6V4ZM4 14h6v6H4v-6Zm10 0h6v6h-6v-6Z" /></svg>
                @endif
            </span>

            <span class="min-w-0 flex-1">
                <strong class="block truncate text-[13px] font-semibold uppercase text-[#102A52]" title="{{ $permission->name }}">{{ $permission->name }}</strong>
                <span class="mt-1 block text-[11px] leading-[1.45] text-[#55749D]">{{ $permission->description ?: $permission->key }}</span>
            </span>
        </label>

        <button type="button" @click.stop="togglePreview()" :aria-expanded="previewOpen" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-[#55749D] hover:bg-[#DCE9F8] hover:text-[#1A3A6B] focus:outline-none" aria-label="Vista previa de {{ $permission->name }}">
            <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" /><circle cx="12" cy="12" r="2.5" stroke-width="1.8" /></svg>
        </button>
    </div>

    <template x-teleport="body">
    <div x-cloak x-show="previewOpen" :style="previewStyle" class="fixed z-[10050] rounded-xl border border-[#AFC7E3] bg-[#F8FBFF] p-3 shadow-[0_18px_45px_rgba(16,42,82,0.2)]" aria-label="Miniatura de {{ $permission->name }}">
        <div class="mb-2 flex items-center justify-between gap-2"><span class="text-[9px] font-bold uppercase tracking-[.12em] text-[#1A3A6B]">Vista previa</span><span class="h-1.5 w-12 rounded-full bg-[#D6E4F4]"></span></div>

        @if (in_array($visual, ['users', 'customers', 'roles'], true))
            <div class="mb-2 h-5 rounded-md border border-[#D7E2F0] bg-white px-2 text-[8px] leading-5 text-[#7890AF]">Buscar {{ $visual === 'roles' ? 'rol' : 'persona' }}...</div>
            <div class="space-y-1.5">
                @foreach ([['AM', 'ADMINISTRADOR'], ['CO', 'COORDINADOR'], ['AU', 'AUXILIAR']] as [$initials, $label])
                    <div class="flex items-center gap-2"><span class="flex h-5 w-5 items-center justify-center rounded-full bg-[#DCE9F8] text-[7px] font-bold text-[#1A3A6B]">{{ $initials }}</span><span class="h-1.5 flex-1 rounded-full bg-[#D8E4F2]"></span><span class="text-[8px] font-semibold text-[#55749D]">{{ $label }}</span></div>
                @endforeach
            </div>
        @elseif ($visual === 'organization')
            <div class="flex flex-col items-center"><span class="rounded-md bg-[#1A3A6B] px-3 py-1 text-[8px] font-semibold text-white">DIRECCIÓN</span><span class="h-3 w-px bg-[#9DB8D8]"></span><div class="flex items-start gap-5"><span class="rounded-md bg-[#DCE9F8] px-2 py-1 text-[8px] text-[#1A3A6B]">ÁREA 01</span><span class="rounded-md bg-[#DCE9F8] px-2 py-1 text-[8px] text-[#1A3A6B]">ÁREA 02</span></div></div>
        @elseif ($visual === 'assignments')
            <div class="flex items-center"><span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#1A3A6B] text-[9px] font-bold text-white">11</span><span class="h-px flex-1 bg-[#AFC7E3]"></span><span class="flex h-5 w-5 items-center justify-center rounded-full border border-[#AFC7E3] bg-white text-[10px] text-[#1A3A6B]">↔</span><span class="h-px flex-1 bg-[#AFC7E3]"></span><span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#DCE9F8] text-[9px] font-bold text-[#1A3A6B]">4</span></div>
        @elseif ($visual === 'permissions')
            <div class="grid grid-cols-2 gap-1.5">@foreach (['USUARIOS', 'CLIENTES', 'HORAS', 'REPORTES'] as $label)<span class="flex items-center gap-1.5 rounded-md bg-white px-2 py-1.5 text-[8px] font-semibold text-[#55749D]"><span class="flex h-3 w-3 items-center justify-center rounded bg-[#1A3A6B] text-[7px] text-white">✓</span>{{ $label }}</span>@endforeach</div>
        @elseif ($visual === 'activities')
            <div class="space-y-1.5 border-l border-[#9DB8D8] pl-3">@foreach (['CONTABILIDAD', 'DECLARACIONES', 'AUDITORÍA'] as $label)<div class="relative text-[8px] font-semibold text-[#55749D] before:absolute before:-left-[15px] before:top-1 before:h-1.5 before:w-1.5 before:rounded-full before:bg-[#4C78B2]">{{ $label }}</div>@endforeach</div>
        @elseif ($visual === 'clock')
            <div class="flex items-center justify-center gap-3"><span class="flex h-11 w-11 items-center justify-center rounded-full border-4 border-[#DCE9F8] text-[10px] font-bold text-[#1A3A6B]">08:42</span><div class="space-y-1.5"><span class="block h-2 w-20 rounded-full bg-[#1A3A6B]"></span><span class="block h-1.5 w-14 rounded-full bg-[#D6E4F4]"></span></div></div>
        @else
            <div class="flex h-14 items-end gap-2">@foreach ([45, 75, 55, 90, 68] as $height)<span class="flex-1 rounded-t bg-[#4C78B2]" style="height: {{ $height }}%"></span>@endforeach</div>
        @endif
    </div>
    </template>
</article>
