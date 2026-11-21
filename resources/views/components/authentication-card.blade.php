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

    {{-- Temporada de Navidad: copos de nieve que caen despacio (solo CSS; se detienen con «reducir movimiento»). --}}
    <div class="auth-snow pointer-events-none" aria-hidden="true">
            <i style="--x: 4%; --s: 4px; --d: 0s; --t: 14s; --o: 0.7;"></i>
            <i style="--x: 11%; --s: 6px; --d: 3s; --t: 18s; --o: 0.55;"></i>
            <i style="--x: 18%; --s: 3px; --d: 7s; --t: 12s; --o: 0.8;"></i>
            <i style="--x: 26%; --s: 5px; --d: 1.5s; --t: 16s; --o: 0.6;"></i>
            <i style="--x: 33%; --s: 4px; --d: 9s; --t: 15s; --o: 0.7;"></i>
            <i style="--x: 41%; --s: 6px; --d: 4s; --t: 19s; --o: 0.5;"></i>
            <i style="--x: 48%; --s: 3px; --d: 11s; --t: 13s; --o: 0.8;"></i>
            <i style="--x: 56%; --s: 5px; --d: 2.5s; --t: 17s; --o: 0.6;"></i>
            <i style="--x: 63%; --s: 4px; --d: 6s; --t: 14s; --o: 0.7;"></i>
            <i style="--x: 70%; --s: 6px; --d: 8.5s; --t: 20s; --o: 0.5;"></i>
            <i style="--x: 77%; --s: 3px; --d: 0.5s; --t: 12s; --o: 0.8;"></i>
            <i style="--x: 84%; --s: 5px; --d: 5.5s; --t: 16s; --o: 0.6;"></i>
            <i style="--x: 91%; --s: 4px; --d: 10s; --t: 15s; --o: 0.7;"></i>
            <i style="--x: 97%; --s: 6px; --d: 12.5s; --t: 18s; --o: 0.5;"></i>
            <i style="--x: 7%; --s: 3px; --d: 13s; --t: 13s; --o: 0.75;"></i>
            <i style="--x: 37%; --s: 3px; --d: 14.5s; --t: 14s; --o: 0.75;"></i>
            <i style="--x: 59%; --s: 3px; --d: 15.5s; --t: 13s; --o: 0.75;"></i>
            <i style="--x: 88%; --s: 3px; --d: 16.5s; --t: 14s; --o: 0.75;"></i>
    </div>

    {{-- Fondo: red de partículas tenue que reacciona al puntero. --}}
    <canvas class="auth-canvas pointer-events-none absolute inset-0 -z-10 h-full w-full" data-auth-network-canvas aria-hidden="true"></canvas>

    {{--
        Todo el contenido vive en un "mundo" que se escala con un zoom común (como el zoom de la página):
        formulario, detalle de temporada, derechos reservados, botones y chat. Las esquinas son las de la página, no las
        de la pantalla: si ni al zoom mínimo cabe, la página se desplaza y hay que bajar para verlas.
    --}}
    <div class="auth-world" data-auth-world>
        {{-- Bloque central: conserva su forma (412px de ancho). --}}
        <div class="auth-stage" data-auth-fit>
            <div class="auth-rise">
                <div class="auth-card">
                    <a href="/" class="auth-brand">
                        <img src="/img/static/logo-principal_.png" alt="González Alonzo y Asociados" class="auth-logo">
                        {{-- Temporada de Navidad: gorro puesto sobre la «g». El dibujo usa las mismas coordenadas que la imagen del logo
                             (228 × 350): el ala abraza la parte de arriba de la panza de la g, ladeado 13°, y la punta cae a la derecha. --}}
                        <svg class="auth-xmas-hat" viewBox="0 -80 300 160" fill="none" aria-hidden="true">
                            <g transform="translate(166 39) rotate(13)">
                                <path d="M-54 -8C-56 -50 -34 -96 10 -104C48 -110 84 -86 98 -52C102 -42 103 -32 100 -24C92 -36 80 -44 68 -44C60 -32 56 -20 54 -8Z" fill="#c62828"/>
                                <path d="M10 -104C48 -110 84 -86 98 -52C102 -42 103 -32 100 -24C92 -36 80 -44 68 -44C74 -66 54 -96 10 -104Z" fill="#a51d1d"/>
                                <path d="M-40 -22C-37 -58 -16 -84 12 -92" stroke="#e85d5d" stroke-width="5" stroke-linecap="round" opacity=".5"/>
                                <path d="M-54 0Q0 12 54 0" stroke="#ffffff" stroke-width="28" stroke-linecap="round"/>
                                <path d="M-46 4Q0 15 46 4" stroke="#e5e7eb" stroke-width="3" stroke-linecap="round"/>
                                <circle cx="100" cy="-20" r="16" fill="#ffffff"/>
                                <circle cx="95" cy="-25" r="5" fill="#f3f4f6"/>
                            </g>
                        </svg>
                    </a>

                    {{ $slot }}

                    @isset($footer)
                        <div class="auth-card-footer">{{ $footer }}</div>
                    @endisset
                </div>
            </div>
        </div>

        {{-- Detalle de temporada (Navidad): dos pinos adornados sobre montículos de nieve y copos que caen. --}}
        <div class="auth-oasis auth-oasis-left" aria-hidden="true">
            <i class="auth-shadow"></i>
            <div class="auth-tree">
                <svg viewBox="0 0 70 100" fill="none">
                    <rect x="31" y="84" width="8" height="12" rx="2" fill="#7c4a1e"/>
                    <path d="M35 8 60 44H48L64 66H50L66 86H4L20 66H6L22 44H10Z" fill="#15803d"/>
                    <path d="M35 8 60 44H48L64 66H50L66 86H35Z" fill="#166534"/>
                    <path d="M14 44C24 48 38 47 56 40M10 66C24 71 42 70 60 62M6 86C22 89 44 89 64 84" stroke="#ffffff" stroke-width="3" stroke-linecap="round" opacity=".9"/>
                    <circle cx="26" cy="54" r="3.2" fill="#dc2626"/>
                    <circle cx="45" cy="58" r="3.2" fill="#facc15"/>
                    <circle cx="33" cy="74" r="3.2" fill="#3b82f6"/>
                    <circle cx="52" cy="78" r="3.2" fill="#dc2626"/>
                    <circle cx="18" cy="78" r="3.2" fill="#facc15"/>
                    <path d="M35 0l2.6 5.4 5.9.9-4.3 4.1 1 5.9-5.2-2.8-5.2 2.8 1-5.9-4.3-4.1 5.9-.9Z" fill="#facc15"/>
                </svg>
            </div>
            <span class="auth-mound">
                <i class="auth-rock auth-rock-a"></i>
                <i class="auth-rock auth-rock-b"></i>
                <i class="auth-rock auth-rock-c"></i>
            </span>
        </div>
        <div class="auth-oasis auth-oasis-right" aria-hidden="true">
            <i class="auth-shadow"></i>
            <div class="auth-tree">
                <svg viewBox="0 0 70 100" fill="none">
                    <rect x="31" y="84" width="8" height="12" rx="2" fill="#7c4a1e"/>
                    <path d="M35 8 60 44H48L64 66H50L66 86H4L20 66H6L22 44H10Z" fill="#15803d"/>
                    <path d="M35 8 60 44H48L64 66H50L66 86H35Z" fill="#166534"/>
                    <path d="M14 44C24 48 38 47 56 40M10 66C24 71 42 70 60 62M6 86C22 89 44 89 64 84" stroke="#ffffff" stroke-width="3" stroke-linecap="round" opacity=".9"/>
                    <circle cx="26" cy="54" r="3.2" fill="#dc2626"/>
                    <circle cx="45" cy="58" r="3.2" fill="#facc15"/>
                    <circle cx="33" cy="74" r="3.2" fill="#3b82f6"/>
                    <circle cx="52" cy="78" r="3.2" fill="#dc2626"/>
                    <circle cx="18" cy="78" r="3.2" fill="#facc15"/>
                    <path d="M35 0l2.6 5.4 5.9.9-4.3 4.1 1 5.9-5.2-2.8-5.2 2.8 1-5.9-4.3-4.1 5.9-.9Z" fill="#facc15"/>
                </svg>
            </div>
            <span class="auth-mound">
                <i class="auth-rock auth-rock-a"></i>
                <i class="auth-rock auth-rock-b"></i>
                <i class="auth-rock auth-rock-c"></i>
            </span>
        </div>

        <footer class="auth-legal" data-auth-fit-footer>
            <span class="auth-legal-bottom">&copy; {{ now()->year }} Todos los derechos reservados</span>
        </footer>

        <x-auth-assistant />
    </div>

</div>
