<div class="flex items-center justify-center w-[100px] relative group">
    @if ($photo)
        <!-- Contenedor de la imagen con hover -->
        <div class="relative w-[100px] h-[100px]">
            <!-- Imagen principal -->
            <img src="{{ $photo->temporaryUrl() }}" alt="Profile Photo" class="w-full h-full object-cover rounded-lg">

            <!-- Overlay que aparece al hacer hover -->
            <label for="dropzone-file"
                class="absolute inset-0 flex flex-col items-center justify-center 
                          bg-black bg-opacity-50 rounded-lg opacity-0 
                          group-hover:opacity-100 transition-opacity duration-300
                          cursor-pointer">
                <div class="text-white text-center p-2">
                    <svg class="w-6 h-6 mx-auto" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 20 16">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2" />
                    </svg>
                    <p class="text-xs mt-1">Cambiar foto</p>
                </div>
                <input id="dropzone-file" type="file" class="hidden" wire:model="photo">
            </label>
            <div class="absolute top-0 right-0 p-2">
                <button title="Eliminar foto" type="button" wire:click="clearPhoto"
                    class="group-hover:opacity-100 opacity-0 transition-opacity duration-300
                        bg-red-500 hover:bg-red-600 text-white rounded-full 
                        w-6 h-6 flex items-center justify-center text-xs">X</button>
            </div>
        </div>
    @elseif (isset($url) && $url)
        <div class="relative w-18 h-18 shadow-md rounded-lg">
            <img src="{{ asset('storage/photos/' . $url) }}" alt="Profile Photo"
                class="w-full h-full object-cover rounded-lg">

            <!-- Overlay para cambiar foto -->
            <label for="dropzone-file"
                class="absolute inset-0 flex flex-col items-center justify-center 
                      bg-black bg-opacity-50 rounded-lg opacity-0 
                      group-hover:opacity-100 transition-opacity duration-300
                      cursor-pointer">
                <div class="text-white text-center p-2">
                    <svg class="w-6 h-6 mx-auto" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 20 16">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2" />
                    </svg>
                    <p class="text-xs mt-1">Cambiar foto</p>
                </div>
                <input id="dropzone-file" type="file" class="hidden" wire:model="photo">
            </label>

            <div class="absolute top-0 right-0 p-2">
                <button title="Eliminar foto" type="button" wire:click="showPhotoModal"
                    class="group-hover:opacity-100 opacity-0 transition-opacity duration-300 bg-red-500 hover:bg-red-600 text-white rounded-full 
                           w-6 h-6 flex items-center justify-center text-xs">X</button>
            </div>
        </div>
    @else
        <!-- Estado sin foto (muestra solo el label) -->
        <label for="dropzone-file"
            class="flex flex-col items-center justify-center 
                      w-full h-24 border-2 border-gray-300 border-dashed 
                      rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100">
            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                <svg class="w-8 h-8 mb-2 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                    fill="none" viewBox="0 0 20 16">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2" />
                </svg>
                <p class="mb-2 text-sm text-gray-500"><span class="font-semibold">Subir foto</span></p>
            </div>
            <input id="dropzone-file" type="file" class="hidden" wire:model="photo">
        </label>
    @endif
</div>
