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
                        {{-- Sombrero charro apoyado sobre la parte superior del logotipo. --}}
                        <svg class="auth-sombrero" viewBox="0 0 120 56" fill="none" aria-hidden="true">
                            <path d="M33 37C38 32 39 17 44 10C48 5 72 5 76 10C81 17 82 32 87 37Z" fill="#a76e35" stroke="#f5d69a" stroke-width="2"/>
                            <path d="M44 13C49 8 71 8 76 13M43 19C49 16 71 16 77 19" stroke="#d9aa67" stroke-width="1.5" stroke-linecap="round"/>
                            <path d="M37 32C50 36 70 36 83 32L87 38C70 43 50 43 33 38Z" fill="#51321d"/>
                            <path d="M40 35L45 38L50 35L55 39L60 36L65 39L70 35L75 38L80 35" stroke="#f0c66e" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M8 39C17 35 29 36 36 38C49 42 71 42 84 38C91 36 103 35 112 39C113 45 95 50 60 50C25 50 7 45 8 39Z" fill="#a76e35" stroke="#f5d69a" stroke-width="2"/>
                            <path d="M15 42C35 49 85 49 105 42" stroke="#6f4724" stroke-width="2" stroke-linecap="round"/>
                            <path d="M23 45L27 47M35 47L39 48M48 48L52 49M68 49L72 48M81 48L85 47M93 47L97 45" stroke="#f0c66e" stroke-width="1.3" stroke-linecap="round"/>
                        </svg>
                    </a>

                    {{ $slot }}

                    @isset($footer)
                        <div class="auth-card-footer">{{ $footer }}</div>
                    @endisset
                </div>
            </div>
        </div>

        {{-- Detalle de temporada: dos cactus plantados en su montículo de arena, con sombra y piedras. Se mecen,
             rebotan al tocarlos con el puntero y levantan polvo al caer. --}}
        <div class="auth-oasis auth-oasis-left" aria-hidden="true">
            <i class="auth-shadow"></i>
            <div class="auth-cactus">
                <svg viewBox="0 0 60 96" fill="none">
                    <path d="M24 94V22a6 6 0 0 1 12 0V94Z" fill="#16a34a"/>
                    <path d="M24 62H15a5 5 0 0 1-5-5V40a4.5 4.5 0 0 1 9 0V52h5Z" fill="#16a34a"/>
                    <path d="M36 52h5V32a4.5 4.5 0 0 1 9 0V47a5 5 0 0 1-5 5h-9Z" fill="#16a34a"/>
                    <path d="M30 14V90M19 44V56M45.5 36V46" stroke="#14532d" stroke-width="1.4" stroke-linecap="round" opacity="0.55"/>
                    <circle cx="30" cy="9" r="3" fill="#dc2626"/>
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
            <div class="auth-cactus">
                <svg viewBox="0 0 60 96" fill="none">
                    <path d="M24 94V22a6 6 0 0 1 12 0V94Z" fill="#16a34a"/>
                    <path d="M24 62H15a5 5 0 0 1-5-5V40a4.5 4.5 0 0 1 9 0V52h5Z" fill="#16a34a"/>
                    <path d="M36 52h5V32a4.5 4.5 0 0 1 9 0V47a5 5 0 0 1-5 5h-9Z" fill="#16a34a"/>
                    <path d="M30 14V90M19 44V56M45.5 36V46" stroke="#14532d" stroke-width="1.4" stroke-linecap="round" opacity="0.55"/>
                    <circle cx="30" cy="9" r="3" fill="#dc2626"/>
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

    <script>
        (function () {
            // Los cactus se estiran como una liga: hay que hacer clic sobre ellos y arrastrar (pasar el puntero no hace nada).
            // Al soltar vuelven a su sitio con un resorte que rebota y levantan polvo de su montículo.
            var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            document.querySelectorAll('.auth-cactus').forEach(function (cactus) {
                var state = { sx: 1, sy: 1, skew: 0 }, vel = { sx: 0, sy: 0, skew: 0 };
                var dragging = false, raf = null, startX = 0, startY = 0, scale = 1, height = 86;

                // El siguiente paso se pide con requestAnimationFrame y, por si este se detiene (pestaña en segundo plano), también con un temporizador:
                // corre el que llegue primero.
                function nextFrame(fn) {
                    var finished = false, handle = {};
                    function run(stamp) {
                        if (finished) return;
                        finished = true;
                        cancelAnimationFrame(handle.a);
                        clearTimeout(handle.b);
                        fn(typeof stamp === 'number' ? stamp : performance.now());
                    }
                    handle.a = requestAnimationFrame(run);
                    handle.b = setTimeout(run, 40);
                    return handle;
                }
                function stopFrame() {
                    if (raf) { cancelAnimationFrame(raf.a); clearTimeout(raf.b); }
                    raf = null;
                }

                function apply() {
                    cactus.style.transform = 'skewX(' + (-state.skew) + 'deg) scale(' + state.sx + ',' + state.sy + ')';
                }
                function clamp(v, lo, hi) { return Math.min(hi, Math.max(lo, v)); }

                function dust(power) {
                    var mound = cactus.parentElement.querySelector('.auth-mound');
                    if (!mound) return;
                    var count = Math.round(3 + power * 6);
                    for (var i = 0; i < count; i++) {
                        var puff = document.createElement('i');
                        puff.className = 'auth-dust';
                        puff.style.left = (22 + Math.random() * 66) + 'px';
                        mound.appendChild(puff);
                        var side = (i % 2 ? 1 : -1) * (10 + Math.random() * 30 * (0.5 + power));
                        puff.animate([
                            { transform: 'translate(0,0) scale(.4)', opacity: .55 },
                            { transform: 'translate(' + side + 'px,' + (-10 - Math.random() * 16) + 'px) scale(1.5)', opacity: 0 }
                        ], { duration: 650 + Math.random() * 250, easing: 'ease-out' }).onfinish = (function (n) { return function () { n.remove(); }; })(puff);
                    }
                }

                // Resorte amortiguado: vuelve a (1, 1, 0) pasándose un poco y oscilando hasta quedar quieto.
                function release() {
                    var last = performance.now();
                    var K = 150, C = 7.5;
                    function step(now) {
                        var dt = Math.min((now - last) / 1000, 1 / 30);
                        last = now;
                        var rest = { sx: 1, sy: 1, skew: 0 }, still = true;
                        ['sx', 'sy', 'skew'].forEach(function (key) {
                            var force = -K * (state[key] - rest[key]) - C * vel[key];
                            vel[key] += force * dt;
                            state[key] += vel[key] * dt;
                            if (Math.abs(state[key] - rest[key]) > 0.002 || Math.abs(vel[key]) > 0.02) still = false;
                        });
                        apply();
                        if (still) {
                            state = { sx: 1, sy: 1, skew: 0 };
                            cactus.style.transform = '';
                            cactus.classList.remove('is-grabbed');
                            return;
                        }
                        raf = nextFrame(step);
                    }
                    raf = nextFrame(step);
                }

                cactus.addEventListener('pointerdown', function (event) {
                    event.preventDefault();
                    stopFrame();
                    dragging = true;
                    vel = { sx: 0, sy: 0, skew: 0 };
                    cactus.classList.add('is-grabbed');
                    var rect = cactus.getBoundingClientRect();
                    height = cactus.offsetHeight || 86;
                    scale = rect.height / (height * state.sy) || 1;
                    startX = event.clientX;
                    startY = event.clientY;
                    try { cactus.setPointerCapture(event.pointerId); } catch (error) {}
                });
                cactus.addEventListener('pointermove', function (event) {
                    if (!dragging) return;
                    var dx = (event.clientX - startX) / scale;
                    var up = (startY - event.clientY) / scale;     // positivo = se jala hacia arriba (se estira); negativo = se aplasta
                    state.sy = clamp(1 + up / height, 0.5, 2.3);
                    state.sx = clamp(1 / Math.sqrt(state.sy), 0.62, 1.35);
                    state.skew = clamp(Math.atan2(dx, height * state.sy) * 180 / Math.PI, -40, 40);
                    apply();
                });
                function drop() {
                    if (!dragging) return;
                    dragging = false;
                    var power = Math.min(1, Math.abs(state.sy - 1) + Math.abs(state.skew) / 40);
                    if (power > 0.12) dust(power);
                    if (reduced) { state = { sx: 1, sy: 1, skew: 0 }; cactus.style.transform = ''; cactus.classList.remove('is-grabbed'); return; }
                    vel = { sx: 0, sy: 0, skew: 0 };
                    release();
                }
                cactus.addEventListener('pointerup', drop);
                cactus.addEventListener('pointercancel', drop);
            });
        })();
    </script>
</div>
