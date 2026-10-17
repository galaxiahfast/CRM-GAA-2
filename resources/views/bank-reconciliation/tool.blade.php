<x-app-layout>
    <section class="h-[calc(100dvh-100px)] min-h-[640px] overflow-hidden bg-white" aria-label="{{ $title }}">
        <iframe
            src="{{ $contentUrl }}"
            title="{{ $title }}"
            class="h-full w-full border-0 bg-white"
            loading="eager"
            referrerpolicy="same-origin"
        ></iframe>
    </section>
</x-app-layout>
