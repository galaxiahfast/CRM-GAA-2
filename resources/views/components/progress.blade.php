<div role="alert"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm transition-opacity duration-300">
    <div
        class="animate-fade-in flex flex-col items-center gap-4 rounded-3xl border border-blue-100 bg-white/95 px-10 py-8 shadow-2xl">

        <!-- Icono PDF animado -->
        <div class="relative flex h-16 w-16 items-center justify-center">
            <div class="absolute h-16 w-16 rounded-full border-4 border-blue-200"></div>
            <div
                class="absolute h-16 w-16 animate-spin rounded-full border-4 border-blue-600 border-t-transparent drop-shadow-md">
            </div>
            <svg class="relative h-8 w-8 animate-bounce text-red-600"
                xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path
                    d="M19 2H8C6.895 2 6 2.895 6 4v16c0 1.105.895 2 2 2h8c1.105 0 2-.895 2-2V6l-5-4zM8 20V4h9v5h5v11H8z" />
            </svg>
        </div>

        <!-- Texto elegante -->
        <span
            class="bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-center text-xl font-semibold tracking-wide text-transparent">
            Subiendo tu PDF...
        </span>

        <!-- Mensaje descriptivo -->
        <p class="max-w-[220px] text-center text-sm leading-relaxed text-gray-500">
            Estamos cargando tu archivo de manera segura. Por favor espera un momento.
        </p>
    </div>
</div>

<style>
    @keyframes fade-in {
        from {
            opacity: 0;
            transform: translateY(10px) scale(0.95);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .animate-fade-in {
        animation: fade-in 0.4s ease-out forwards;
    }

    @keyframes bounce {

        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-6px);
        }
    }

    .animate-bounce {
        animation: bounce 1s infinite;
    }
</style>
