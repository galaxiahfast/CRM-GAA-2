<div class="flex-col gap-4">
    <div class="flex items-center justify-between">
        <x-label for="phone" value="Teléfono" />
        <p id="phoneCount" class="text-xs text-gray-400">0/10 caracteres</p>
    </div>
    <div class="flex items-center">
        <button id="dropdown-phone-button" data-dropdown-toggle="dropdown-phone"
            class="z-10 inline-flex shrink-0 items-center rounded-s-lg border border-gray-300 bg-white-full px-4 py-2.5 text-center text-sm font-medium text-gray-900 shadow-sm hover:bg-gray-200"
            type="button">
            <img src="{{ $selectedCountry['flag'] }}" class="me-2 h-3 w-5">
            +{{ $selectedCountry['countryCode'] }}
        </button>
        <div id="dropdown-phone"
            class="z-10 hidden w-52 divide-y divide-gray-100 rounded-lg bg-white-full shadow-sm">
            <ul class="h-64 overflow-y-auto py-2 text-sm text-gray-700"
                aria-labelledby="dropdown-phone-button">
                @foreach ($countries as $country)
                    <li class="flex items-center justify-start">
                        <button wire:click="selectCountry({{ $country['countryCode'] }})"
                            type="button"
                            class="flex items-center rounded-sm p-2 hover:bg-gray-100">
                            <img src="{{ $country['flag'] }}" class="ms-2 h-3 w-5">
                            <label for="checkbox-item-{{ $country['countryCode'] }}"
                                class="ms-2 w-full rounded-sm text-sm font-medium text-gray-900 dark:text-gray-300">{{ $country['country'] }}</label>
                            <label for="checkbox-item-{{ $country['countryCode'] }}"
                                class="ms-2 w-full rounded-sm text-sm font-medium text-gray-900 dark:text-gray-300">+{{ $country['countryCode'] }}</label>
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="relative w-full">

            <input maxlength="12" id="phone" wire:model.defer="phone" oninput="formatPhone()"
                class="z-20 block w-full rounded-e-lg border border-s-0 border-gray-300 bg-white-full p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500"
                placeholder="123-456-7890" />
        </div>
    </div>
    <script>
        function formatPhone() {
            const inputField = document.getElementById('phone');
            let value = inputField.value.replace(/[^0-9]/g, '');
            if (value.length > 10) value = value.slice(0, 10);

            if (value.length === 10) {
                inputField.value = value.replace(/(\d{3})(\d{3})(\d{4})/, '$1-$2-$3');
            } else {
                inputField.value = value;
            }

            const charCount = document.getElementById('phoneCount');
            charCount.textContent = `${value.length} / 10 caracteres`;
        }
    </script>
</div>
