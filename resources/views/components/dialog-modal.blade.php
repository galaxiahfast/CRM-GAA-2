@props(['id' => null, 'maxWidth' => null, 'maxHeight' => null])

<x-modal :id="$id" :maxWidth="$maxWidth" {{ $attributes }}>
    <div class="overflow-y-scroll px-6 py-4" style="max-height: {{ $maxHeight }}px;">
        <div class="text-lg font-medium text-gray-900">
            <h2 class="mb-4 text-lg font-bold">
                {{ $title }}
            </h2>
        </div>

        <div class="mt-4 text-sm text-gray-600">
            {{ $content }}
        </div>
    </div>

    <div class="flex flex-row justify-end gap-2 bg-gray-100 px-6 py-4 text-end">
        {{ $footer }}
    </div>
</x-modal>
