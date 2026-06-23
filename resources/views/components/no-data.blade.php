@props([
    'title' => '',
    'subTitle' => '',
    'titleButton' => '',
    'click' => '',
])
<div class="mt-6 flex h-96 items-center rounded-lg border text-center">
    <div class="mx-auto flex w-full max-w-sm flex-col px-4">
        <div class="mx-auto rounded-full bg-blue-100 p-3 text-blue-500">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            </svg>
        </div>
        <h1 class="mt-3 text-lg text-gray-800">{{ $title }}</h1>
        <p class="mt-2 text-gray-500">{{ $subTitle }}</p>
        <div class="mt-4 flex items-center gap-x-3 sm:mx-auto">

            <button wire:click='{{ $click }}'
                class="flex w-1/2 shrink-0 items-center justify-center gap-x-2 rounded-lg bg-blue-500 px-5 py-2 text-sm tracking-wide text-white transition-colors duration-200 hover:bg-blue-600 sm:w-auto">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                    stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>

                <span>{{ $titleButton }}</span>
            </button>
        </div>
    </div>
</div>
