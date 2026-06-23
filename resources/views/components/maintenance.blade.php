<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>En mantenimiento</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body
    class="flex min-h-screen flex-col items-center justify-center bg-gradient-to-br from-blue-800 to-indigo-900 text-white">

    <div class="px-6 text-center">
        <div class="relative mb-8 inline-block">
            <div
                class="flex h-24 w-24 animate-bounce items-center justify-center rounded-full bg-blue-600 shadow-lg">
                @svg('feathericon-settings')
            </div>
        </div>

        <h1 class="mb-4 text-4xl font-extrabold">¡Estamos en mantenimiento!</h1>
        <p class="mb-6 text-lg opacity-90">
            Estamos trabajando en este módulo. 🚀
        </p>

        <a href="{{ url('/dashboard') }}"
            class="rounded-xl bg-white px-6 py-2 font-semibold text-blue-700 shadow transition hover:bg-purple-100">
            Volver al inicio
        </a>
    </div>

    <footer class="absolute bottom-4 text-sm opacity-70">
        <p>© {{ date('Y') }} Datamid — En desarrollo</p>
    </footer>
</body>

</html>
