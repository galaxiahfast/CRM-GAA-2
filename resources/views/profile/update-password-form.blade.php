<section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-[0_8px_24px_rgba(15,35,66,0.06)]">
    <header class="flex items-center gap-3 border-b border-gray-200 px-6 py-5">
        <span class="grid h-10 w-10 place-items-center rounded-xl bg-[#1A3A6B] text-white">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 11V8a5 5 0 0110 0v3m-11 0h12a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2v-6a2 2 0 012-2z" /></svg>
        </span>
        <div><h2 class="font-semibold text-gray-800">Contraseña</h2><p class="mt-0.5 text-[12px] text-gray-500">Protege tu cuenta con una contraseña segura.</p></div>
    </header>

    <form wire:submit="updatePassword" class="space-y-5 p-6">
        <div>
            <label for="current_password" class="mb-2 block text-[12px] font-semibold uppercase tracking-[0.06em] text-gray-500">Contraseña actual</label>
            <input id="current_password" type="password" wire:model="state.current_password" autocomplete="current-password" class="h-11 w-full rounded-xl border-gray-300 text-[15px] focus:border-[#1A3A6B] focus:ring-[#1A3A6B]/20">
            <x-input-error for="current_password" class="mt-2" />
        </div>
        <div>
            <label for="password" class="mb-2 block text-[12px] font-semibold uppercase tracking-[0.06em] text-gray-500">Nueva contraseña</label>
            <input id="password" type="password" wire:model="state.password" autocomplete="new-password" class="h-11 w-full rounded-xl border-gray-300 text-[15px] focus:border-[#1A3A6B] focus:ring-[#1A3A6B]/20">
            <x-input-error for="password" class="mt-2" />
        </div>
        <div>
            <label for="password_confirmation" class="mb-2 block text-[12px] font-semibold uppercase tracking-[0.06em] text-gray-500">Confirmar contraseña</label>
            <input id="password_confirmation" type="password" wire:model="state.password_confirmation" autocomplete="new-password" class="h-11 w-full rounded-xl border-gray-300 text-[15px] focus:border-[#1A3A6B] focus:ring-[#1A3A6B]/20">
            <x-input-error for="password_confirmation" class="mt-2" />
        </div>
        <footer class="flex items-center justify-end gap-4 border-t border-gray-100 pt-5">
            <x-action-message on="saved" class="text-[12px] font-medium text-emerald-600">Contraseña actualizada.</x-action-message>
            <button type="submit" class="inline-flex h-11 items-center justify-center rounded-xl bg-[#1A3A6B] px-5 font-semibold text-white transition hover:bg-[#142f58]">Actualizar contraseña</button>
        </footer>
    </form>
</section>
