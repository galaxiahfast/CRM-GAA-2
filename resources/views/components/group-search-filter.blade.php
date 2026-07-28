@props(['label', 'options' => [], 'selectMethod' => null, 'emptyMessage' => 'Sin coincidencias'])

<div
    data-group-search
    data-options='@json($options)'
    data-select-method='@json($selectMethod)'
    data-empty-message='@json($emptyMessage)'
    style="position: relative; width: 200px; flex-shrink: 0;"
>
    <input
        type="text"
        placeholder="{{ $label }}"
        data-group-search-input
        class="border border-gray-300 rounded-lg text-[15px] bg-white/80 backdrop-blur-sm shadow-sm"
        style="height: 40px; width: 100%; padding: 0 16px; outline: 0 !important; box-shadow: none !important; transition: none; position: relative; z-index: 30; background-color: rgba(255, 255, 255, 0.5);"
        autocomplete="off"
    >

    <div
        data-group-search-menu
        class="group-filter-scrollbar"
        style="display: none; position: absolute; top: calc(100% + 4px); left: 0; right: 0; background: rgba(255,255,255,0.98); backdrop-filter: blur(8px); border: 1px solid #d1d5db; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.12); max-height: 250px; overflow-y: auto; z-index: 100;"
    >
    </div>
</div>