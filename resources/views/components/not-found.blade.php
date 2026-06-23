<div> <!-- ROOT ELEMENTO ÚNICO -->

    <div class="flex min-h-[70vh] items-center justify-center p-6">
        <div class="relative max-w-lg rounded-3xl p-10 text-center">

            <!-- Ícono animado tipo “alerta premium” -->
            <div
                class="mx-auto mb-6 flex h-20 w-20 animate-bounce items-center justify-center rounded-full bg-blue-500 text-white shadow-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            <!-- Título dramático -->
            <h2
                class="animate-fade-in mb-3 text-3xl font-extrabold tracking-wide text-blue-700 drop-shadow-md">
                Cliente no encontrado
            </h2>

            <!-- Mensaje -->
            <p class="animate-fade-in mb-8 text-lg font-medium text-blue-600 delay-150">
                El cliente que buscas no existe o fue eliminado.
            </p>

            <!-- Botón -->
            <a href="{{ route('customers.index') }}"
                class="inline-flex items-center gap-3 rounded-full bg-blue-600 px-6 py-3 text-lg font-bold text-white shadow-md transition-all hover:scale-105 hover:bg-blue-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 19l-7-7 7-7" />
                </svg>
                Volver al listado
            </a>

        </div>
    </div>


    <style>
        @keyframes fade-in {
            0% {
                opacity: 0;
                transform: translateY(-10px);
            }

            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fade-in 0.8s ease forwards;
        }

        .animate-fade-in.delay-150 {
            animation-delay: 0.15s;
        }
    </style>
</div> <!-- FIN ROOT -->
