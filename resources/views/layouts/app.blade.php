<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
</head>

<body class="font-sans antialiased">

    <x-banner />

    <!-- ========================================= -->
    <!-- LAYOUT GLOBAL                             -->
    <!-- ========================================= -->
    <div
        x-data="{
            collapsed: localStorage.getItem('sidebarCollapsed') === 'true',
        }"

        x-init="
            $watch('collapsed', value => {
                localStorage.setItem('sidebarCollapsed', value);
            });
        "

        class="min-h-screen bg-gray-100"
    >

        <!-- Navbar + Sidebar -->
        <x-navbar-custom />

        <!-- CONTENIDO -->
        <main
            id="main-content"
            class="pt-[90px]"
        >

            <div>
                {{ $slot }}
            </div>

        </main>

    </div>

    @stack('modals')

    @livewireScripts

    <!-- ⬇️⬇️⬇️ AÑADE ESTO ⬇️⬇️⬇️ -->
    @stack('scripts')
    <!-- ⬆️⬆️⬆️ AÑADE ESTO ⬆️⬆️⬆️ -->

</body>

</html>
