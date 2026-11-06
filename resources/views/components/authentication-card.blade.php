@props(['compact' => false])

{{-- Marco compartido de las pantallas de acceso: logo, contenido, pie legal y botones flotantes (tema / asistente). --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
{{-- La tipografía se pide sin bloquear: la página se dibuja al instante con la fuente del sistema y cambia a Geist al llegar. --}}
<link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
<noscript><link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600&display=swap" rel="stylesheet"></noscript>

<div class="auth-shell relative isolate w-full" data-auth-particle-network data-auth-theme="blue">
    <script>
        (function (root) {
            // Siempre inicia en azul; el botón de tema solo cambia la vista actual.
            root.dataset.authTheme = 'blue';

            document.addEventListener('click', function (event) {
                if (!event.target.closest('[data-auth-theme-toggle]')) return;
                var next = root.dataset.authTheme === 'dark' ? 'blue' : 'dark';
                root.dataset.authTheme = next;
            });
        })(document.currentScript.parentElement);
    </script>

    {{-- Fondo: red de partículas tenue que reacciona al puntero. --}}
    <canvas class="auth-canvas pointer-events-none absolute inset-0 -z-10 h-full w-full" data-auth-network-canvas aria-hidden="true"></canvas>

    {{--
        Todo el contenido vive en un "mundo" que se escala con un zoom común (como el zoom de la página):
        formulario, telaraña, derechos reservados, botones y chat. Las esquinas son las de la página, no las
        de la pantalla: si ni al zoom mínimo cabe, la página se desplaza y hay que bajar para verlas.
    --}}
    <div class="auth-world" data-auth-world>
        {{-- Detalle de temporada: telaraña muy tenue en la esquina superior izquierda. --}}
        <svg class="auth-web" viewBox="0 0 120 120" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" aria-hidden="true">
            <path d="M0 0 120 0M0 0 110.9 45.9M0 0 84.8 84.8M0 0 45.9 110.9M0 0 0 120"/>
            <path d="M30 0Q25.9 5.1 27.7 11.5Q21.9 14.7 21.2 21.2Q14.7 21.9 11.5 27.7Q5.1 25.9 0 30"/>
            <path d="M55 0Q47.5 9.4 50.8 21.1Q40.2 26.9 38.9 38.9Q26.9 40.2 21.1 50.8Q9.4 47.5 0 55"/>
            <path d="M80 0Q69.1 13.7 73.9 30.6Q58.5 39.1 56.6 56.6Q39.1 58.5 30.6 73.9Q13.7 69.1 0 80"/>
            <path d="M105 0Q90.6 18 97 40.2Q76.8 51.4 74.2 74.2Q51.4 76.8 40.2 97Q18 90.6 0 105"/>
        </svg>

        {{-- Bloque central: conserva su forma (412px de ancho). --}}
        <div class="auth-stage" data-auth-fit>
            <div class="auth-rise">
                <div class="auth-card">
                    <a href="/" class="auth-brand">
                        <img src="/img/static/logo-principal_.png" alt="González Alonzo y Asociados" class="auth-logo">
                        {{-- Detalle de temporada: una arañita que cuelga de un hilo junto al logo. --}}
                        <span class="auth-spider" aria-hidden="true">
                            <span class="auth-spider-swing">
                                <i class="auth-thread"></i>
                                <svg viewBox="0 0 24 22" fill="none" stroke="currentColor" stroke-width="1.1" stroke-linecap="round">
                                    <path d="M8.4 12Q3.5 7.5 1 9.5M8 14Q2.5 12.5 0.5 17M8.6 16Q4 18 2.5 21.5M9.8 17.6Q7 19.5 6.5 21.8M15.6 12Q20.5 7.5 23 9.5M16 14Q21.5 12.5 23.5 17M15.4 16Q20 18 21.5 21.5M14.2 17.6Q17 19.5 17.5 21.8"/>
                                    <ellipse cx="12" cy="14.2" rx="4.1" ry="4.6" fill="currentColor" stroke="none"/>
                                    <circle cx="12" cy="8.2" r="2.6" fill="currentColor" stroke="none"/>
                                    <circle cx="11" cy="7.9" r="0.65" fill="#f87171" stroke="none"/>
                                    <circle cx="13" cy="7.9" r="0.65" fill="#f87171" stroke="none"/>
                                </svg>
                            </span>
                        </span>
                    </a>

                    {{ $slot }}

                    @isset($footer)
                        <div class="auth-card-footer">{{ $footer }}</div>
                    @endisset
                </div>
            </div>
        </div>

        <footer class="auth-legal" data-auth-fit-footer>
            <span class="auth-legal-bottom">&copy; {{ now()->year }} Todos los derechos reservados</span>
        </footer>

        <x-auth-assistant />
    </div>
</div>
