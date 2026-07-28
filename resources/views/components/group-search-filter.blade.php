@props(['label', 'options' => [], 'selectMethod', 'emptyMessage' => 'Sin coincidencias'])

<div
    x-data="{
        open: false,
        query: '',
        options: @js($options),
        selectMethod: @js($selectMethod),
        get filteredOptions() {
            const term = this.query.trim().toLocaleLowerCase();
            return term === '' ? this.options : this.options.filter(option => option.name.toLocaleLowerCase().includes(term));
        },
        choose(option) {
            this.query = option.name;
            this.open = false;
            $wire.call(this.selectMethod, option.id);
        }
    }"
    @click.away="open = false"
    style="position: relative; width: 200px; flex-shrink: 0;"
>
    <input
        type="text"
        placeholder="{{ $label }}"
        x-model="query"
        @focus="open = true"
        @click="open = true"
        @keydown.escape.prevent="open = false"
        class="rounded-lg text-[15px] border-gray-300 bg-white/80 backdrop-blur-sm shadow-sm"
        style="height: 40px; width: 100%; padding: 20px 20px; border: 1px solid #d1d5db; outline: 0 !important; box-shadow: none !important; transition: none; position: relative; z-index: 30; background-color: rgba(255, 255, 255, 0.5);"
        autocomplete="off"
    >

    <div
        x-show="open"
        x-cloak
        class="group-filter-scrollbar"
        style="position: absolute; top: calc(100% + 4px); left: 0; right: 0; background: rgba(255,255,255,0.98); backdrop-filter: blur(8px); border: 1px solid #d1d5db; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.12); max-height: 200px; overflow-y: auto; z-index: 100;"
    >
        <template x-for="option in filteredOptions" :key="option.id">
            <button type="button" @click="choose(option)" style="display: block; width: 100%; padding: 20px; cursor: pointer; font-size: 14px; color: #374151; border: 0; border-bottom: 1px solid #f3f4f6; background: transparent; text-align: left;" onmouseover="this.style.backgroundColor='#f3f4f6'" onmouseout="this.style.backgroundColor='transparent'">
                <span x-text="option.name + (option.count ? ' (' + option.count + ')' : '')"></span>
            </button>
        </template>
        <p x-show="filteredOptions.length === 0" style="padding: 10px 16px; color: #6b7280; font-size: 14px;" x-text='@js($emptyMessage)'></p>
    </div>
</div>
