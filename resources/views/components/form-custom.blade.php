@props(['submit'])
<div class="mt-5 flex justify-center">
    <div class="mt-5 md:mt-0 md:col-span-2 shadow-lg">
        <form wire:submit.prevent="{{ $submit }}">
            <div class="px-4 py-5 bg-white sm:p-6 shadow}}">
                <div class="mb-2 text-xl font-bold text-gray-900">
                    {{ $title }}
                </div>
                <div class="grid grid-cols-6 gap-6">
                    {{ $form }}
                </div>
            </div>
            @if (isset($actions))
                <div class="flex items-center justify-end px-4 py-3 bg-gray-50 text-end sm:px-6 space-x-2">
                    {{ $actions }}
                </div>
            @endif
        </form>

    </div>
</div>
