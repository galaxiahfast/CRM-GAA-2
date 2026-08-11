<section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-[0_8px_24px_rgba(15,35,66,0.06)]">
    <header class="flex items-center gap-3 border-b border-gray-200 px-6 py-5">
        <span class="grid h-10 w-10 place-items-center rounded-xl bg-[#1A3A6B] text-white">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.2 5.2a4.5 4.5 0 11-6.4 6.4 4.5 4.5 0 016.4-6.4zM4 21a8 8 0 0116 0" /></svg>
        </span>
        <div>
            <h2 class="font-semibold text-gray-800">Información personal</h2>
            <p class="mt-0.5 text-[12px] text-gray-500">Actualiza los datos visibles de tu cuenta.</p>
        </div>
    </header>

    <form wire:submit="updateProfileInformation" class="p-6">
        @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
            <div
                x-data="{ photoPreview: null, dragging: false,
                    preview(file) {
                        if (!file) return;
                        const reader = new FileReader(); reader.onload = e => this.photoPreview = e.target.result; reader.readAsDataURL(file);
                    },
                    drop(file) {
                        if (!file) return;
                        const transfer = new DataTransfer(); transfer.items.add(file);
                        this.$refs.photo.files = transfer.files;
                        this.preview(file);
                        this.$refs.photo.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }"
                class="mb-6"
            >
                <input x-ref="photo" id="photo" type="file" accept="image/png,image/jpeg" class="hidden" wire:model.live="photo" @change="preview($event.target.files[0])">
                <div
                    @dragenter.prevent="dragging = true" @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false"
                    @drop.prevent="dragging = false; drop($event.dataTransfer.files[0])"
                    @click="$refs.photo.click()" @keydown.enter.prevent="$refs.photo.click()" @keydown.space.prevent="$refs.photo.click()"
                    role="button" tabindex="0"
                    class="flex cursor-pointer items-center gap-5 rounded-xl border-2 border-dashed p-5 transition"
                    :class="dragging ? 'border-[#1A3A6B] bg-[#eef4fc]' : 'border-gray-200 hover:border-[#b9c9e2] hover:bg-gray-50'"
                >
                    <div class="relative h-20 w-20 shrink-0">
                        <template x-if="photoPreview"><img :src="photoPreview" alt="Vista previa" class="h-20 w-20 rounded-full object-cover"></template>
                        <div x-show="!photoPreview">
                            @if ($this->user->profile_photo_path)
                                <img src="{{ $this->user->profile_photo_url }}" alt="{{ $this->user->name }}" class="h-20 w-20 rounded-full object-cover">
                            @else
                                <span class="grid h-20 w-20 place-items-center rounded-full bg-[#eef4fc] text-xl font-bold text-[#1A3A6B]">{{ mb_strtoupper(mb_substr($this->user->name, 0, 1).mb_substr($this->user->last_name ?? '', 0, 1)) }}</span>
                            @endif
                        </div>
                        <span class="absolute bottom-0 right-0 grid h-7 w-7 place-items-center rounded-full border-2 border-white bg-[#1A3A6B] text-white">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m-7-7h14" /></svg>
                        </span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-gray-800">Foto de perfil</p>
                        <p class="mt-1 leading-6 text-gray-500">Arrastra una imagen aquí o haz clic para seleccionarla.</p>
                        <p class="mt-1 text-[12px] text-gray-400">JPG o PNG, máximo 1 MB.</p>
                    </div>
                    <span wire:loading wire:target="photo" class="text-[12px] font-medium text-[#1A3A6B]">Cargando…</span>
                </div>
                <div class="mt-2 flex items-center justify-between gap-3">
                    <x-input-error for="photo" />
                    @if ($this->user->profile_photo_path)
                        <button type="button" wire:click="deleteProfilePhoto" class="text-[12px] font-medium text-red-600 hover:text-red-700">Eliminar foto actual</button>
                    @endif
                </div>
            </div>
        @endif

        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <label for="name" class="mb-2 block text-[12px] font-semibold uppercase tracking-[0.06em] text-gray-500">Nombre</label>
                <input id="name" type="text" wire:model="state.name" required autocomplete="given-name" class="h-11 w-full rounded-xl border-gray-300 text-[15px] focus:border-[#1A3A6B] focus:ring-[#1A3A6B]/20">
                <x-input-error for="name" class="mt-2" />
            </div>
            <div>
                <label for="last_name" class="mb-2 block text-[12px] font-semibold uppercase tracking-[0.06em] text-gray-500">Apellido</label>
                <input id="last_name" type="text" wire:model="state.last_name" autocomplete="family-name" class="h-11 w-full rounded-xl border-gray-300 text-[15px] focus:border-[#1A3A6B] focus:ring-[#1A3A6B]/20">
                <x-input-error for="last_name" class="mt-2" />
            </div>
            <div class="md:col-span-2">
                <label for="email" class="mb-2 block text-[12px] font-semibold uppercase tracking-[0.06em] text-gray-500">Correo electrónico</label>
                <input id="email" type="email" wire:model="state.email" required autocomplete="username" class="h-11 w-full rounded-xl border-gray-300 text-[15px] focus:border-[#1A3A6B] focus:ring-[#1A3A6B]/20">
                <x-input-error for="email" class="mt-2" />
            </div>
        </div>

        <footer class="mt-6 flex items-center justify-end gap-4 border-t border-gray-100 pt-5">
            <x-action-message on="saved" class="text-[12px] font-medium text-emerald-600">Cambios guardados.</x-action-message>
            <button type="submit" wire:loading.attr="disabled" wire:target="photo" class="inline-flex h-11 items-center justify-center rounded-xl bg-[#1A3A6B] px-5 font-semibold text-white transition hover:bg-[#142f58] disabled:opacity-60">Guardar cambios</button>
        </footer>
    </form>
</section>
