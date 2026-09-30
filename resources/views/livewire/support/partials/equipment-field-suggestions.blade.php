@if ($show)
    <div class="absolute z-40 mt-[6px] max-h-[240px] w-full overflow-y-auto rounded-xl border border-zinc-200 bg-white p-[5px] shadow-[0_12px_30px_rgba(0,0,0,0.14)]" role="listbox">
        @forelse ($suggestions as $index => $suggestion)
            <button
                data-field-option
                type="button"
                wire:key="delivery-{{ $field }}-suggestion-{{ md5($suggestion) }}"
                wire:click="selectEquipmentFieldSuggestion('{{ $field }}', {{ $index }})"
                :class="active === {{ $index }} ? 'bg-zinc-100' : ''"
                class="flex w-full items-center gap-[10px] rounded-lg px-[12px] py-[10px] text-left text-black transition hover:bg-zinc-100"
                role="option"
            >
                <svg class="h-4 w-4 shrink-0 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="m9 18 6-6-6-6"/></svg>
                <span class="truncate font-medium">{{ $suggestion }}</span>
            </button>
        @empty
            <p class="px-[12px] py-[15px] text-zinc-500">Sin coincidencias — puedes escribir un valor nuevo.</p>
        @endforelse
    </div>
@endif
