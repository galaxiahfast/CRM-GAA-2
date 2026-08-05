<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Operación no disponible</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 text-[15px] text-gray-800">
    <main class="flex min-h-screen items-center justify-center p-6">
        <section class="w-full max-w-lg rounded-xl border border-gray-200 bg-white p-8 text-center shadow-lg">
            <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-xl bg-red-50 text-red-600">
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <h1 class="text-xl font-semibold text-gray-900">No pudimos completar la operación</h1>
            <p class="mt-3 leading-6 text-gray-500">El incidente fue registrado automáticamente. Puedes volver a intentar sin perder el acceso al resto del sistema.</p>
            <p class="mt-4 rounded-lg bg-gray-50 px-3 py-2 text-[12px] text-gray-500">Referencia: <strong>{{ $reference }}</strong></p>
            <div class="mt-6 flex justify-center gap-3">
                <button type="button" onclick="history.back()" class="rounded-xl border border-[#1a3a6b] px-4 py-2.5 font-medium text-[#1a3a6b] hover:bg-blue-50">Volver</button>
                <a href="{{ route('dashboard') }}" class="rounded-xl bg-[#1a3a6b] px-4 py-2.5 font-medium text-white hover:bg-[#16325d]">Ir al inicio</a>
            </div>
        </section>
    </main>
</body>
</html>
