<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'González Alonzo y Asociados') }}</title>

        <!-- Scripts -->
        {{-- Al llegar al login se reinician los desplegables recordados del menú lateral. --}}
    <script>try { ['bank', 'time', 'reports', 'help'].forEach(function (key) { sessionStorage.removeItem('sidebar-open-' + key); }); } catch (error) {}</script>
    @include('layouts.partials.auto-scale')
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles
    </head>
    <body class="bg-[#EEF1F5]">
        <div class="font-sans text-gray-900 antialiased">
            {{ $slot }}
        </div>

        @livewireScripts
    </body>
</html>
