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
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet"></noscript>

    {{-- Tema: por defecto claro. Si la persona lo cambia se guarda y se aplica desde el primer pintado, así al recargar no parpadea ni vuelve al claro. --}}
    <script>
        try { document.documentElement.classList.toggle('module-dark-theme', localStorage.getItem('activities-support-theme') === 'dark'); } catch (error) {}
    </script>
    {{-- Desplegables del menú lateral: se recuerdan al recargar o cambiar de pantalla (por pestaña); se reinician al volver al login. --}}
    <script>
        window.sidebarOpenGet = function (key) { try { return sessionStorage.getItem('sidebar-open-' + key) === '1'; } catch (error) { return false; } };
        window.sidebarOpenSet = function (key, value) { try { sessionStorage.setItem('sidebar-open-' + key, value ? '1' : '0'); } catch (error) {} };
    </script>
    @include('layouts.partials.auto-scale')

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
    @stack('styles')
</head>

<body
    class="font-sans antialiased"
    data-session-keep-alive-url="{{ route('session.keep-alive') }}"
    data-login-url="{{ route('login') }}"
>

    <x-banner />

    <!-- ========================================= -->
    <!-- LAYOUT GLOBAL                             -->
    <!-- ========================================= -->
    <div
        x-data="{
            collapsed: window.innerWidth / window.appAutoScale() < 1024 || localStorage.getItem('sidebarCollapsed') === 'true',
        }"

        x-init="
            $watch('collapsed', value => {
                if (window.innerWidth / window.appAutoScale() >= 1024) localStorage.setItem('sidebarCollapsed', value);
            });
        "

        class="min-h-screen bg-gray-100"
    >

        <!-- Navbar + Sidebar -->
        <x-navbar-custom />

        <!-- CONTENIDO -->
        <main
            id="main-content"
            class="pt-[123px]"
        >

            <div id="app-zoom">
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
