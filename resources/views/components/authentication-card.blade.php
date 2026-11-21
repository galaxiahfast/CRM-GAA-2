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
                        {{-- Sombrero mexicano ladeado y colgado de la parte superior del logotipo. --}}
                        <svg class="auth-sombrero" viewBox="0 0 160 84" fill="none" aria-hidden="true">
                            <path d="M8 52C7 47 11 43 19 44L59 49C72 51 88 51 101 49L141 44C149 43 153 47 152 52C150 64 121 75 80 75C39 75 10 64 8 52Z" fill="#b5251c" stroke="#8e211b" stroke-width="2"/>
                            <path d="M9 50C20 59 45 66 80 66C115 66 140 59 151 50L150 57C137 67 112 72 80 72C48 72 23 67 10 57Z" fill="#15803d"/>
                            <path d="M10 46C18 43 44 49 59 51C73 53 87 53 101 51C116 49 142 43 150 46C151 55 121 64 80 64C39 64 9 55 10 46Z" fill="#d9b16d" stroke="#f4d7a0" stroke-width="2"/>
                            <path d="M12 49C29 57 50 60 80 60C110 60 131 57 148 49" stroke="#b5251c" stroke-width="3" stroke-linecap="round"/>
                            <path d="M59 52C64 38 68 18 75 9C78 5 82 5 85 9C92 18 96 38 101 52C89 55 71 55 59 52Z" fill="#d9b16d" stroke="#f4d7a0" stroke-width="2"/>
                            <path d="M70 20C72 15 74 11 77 8C79 6 81 6 83 8C86 11 88 15 90 20C83 18 77 18 70 20Z" fill="#15803d"/>
                            <path d="M62 44C72 47 88 47 98 44L102 53C89 57 71 57 58 53Z" fill="#b5251c"/>
                            <path d="M67 31C75 33 85 33 93 31M65 36C74 39 86 39 95 36" stroke="#f4d7a0" stroke-width="1.5" stroke-linecap="round"/>
                            <path d="M11 57C25 69 50 76 80 77C110 76 135 69 149 57" stroke="#e0a44a" stroke-width="2" stroke-linecap="round"/>
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
