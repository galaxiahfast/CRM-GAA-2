@php
    // Preguntas frecuentes del asistente de acceso (las respuestas son HTML propio, no contenido del usuario).
    $faq = [
        [
            'q' => 'Olvidé mi contraseña',
            'a' => Route::has('password.request')
                ? 'Puedes restablecerla aquí: <a href="'.e(route('password.request')).'">recuperar contraseña</a>. Te enviaremos un enlace a tu correo.'
                : 'Solicita el restablecimiento al área de sistemas de tu oficina.',
        ],
        [
            'q' => '¿Cómo creo una cuenta?',
            'a' => Route::has('register')
                ? 'Abre la pestaña <strong>Registrarse</strong>, llena tus datos y confirma tu correo. Tu cuenta inicia con el rol de Becario.'
                : 'Las cuentas las crea el área de sistemas. Pide la tuya a tu responsable.',
        ],
        [
            'q' => 'No me llega el correo de verificación',
            'a' => 'Revisa la carpeta de spam o correo no deseado. Si no aparece en unos minutos, inicia sesión y pide que se reenvíe el enlace.',
        ],
        [
            'q' => 'Entrar con Google o GitHub',
            'a' => 'Usa los botones bajo «O continúa con». Si aparecen deshabilitados, ese acceso todavía no está activo; entra con tu correo y contraseña.',
        ],
        [
            'q' => 'Mi cuenta está bloqueada',
            'a' => 'Tras varios intentos fallidos el acceso se pausa unos minutos. Espera un momento y vuelve a intentarlo; si continúa, contacta a soporte técnico.',
        ],
        [
            'q' => 'Hablar con soporte',
            'a' => 'Comunícate con el área de soporte técnico de González Alonzo y Asociados indicando tu nombre y el correo con el que intentas entrar.',
        ],
    ];
@endphp

{{-- Botones flotantes: la flecha despliega el cambio de tema y el asistente de ayuda. --}}
<div x-data="{
        menu: true,
        chat: false,
        typing: false,
        faq: @js($faq),
        messages: [{ from: 'bot', text: 'Hola, soy el asistente de acceso. ¿En qué te ayudo?' }],
        ask(item) {
            if (this.typing) return;
            this.messages.push({ from: 'user', text: item.q });
            this.typing = true;
            this.scroll();
            setTimeout(() => {
                this.typing = false;
                this.messages.push({ from: 'bot', text: item.a });
                this.scroll();
            }, 550);
        },
        scroll() { this.$nextTick(() => { const body = this.$refs.body; if (body) body.scrollTop = body.scrollHeight; }); },
        toggleMenu() { this.menu = !this.menu; if (!this.menu) this.chat = false; },
    }"
    @keydown.escape.window="chat ? chat = false : menu = false">

    <div class="auth-chat" x-show="chat" x-cloak role="dialog" aria-label="Asistente de acceso"
        @click.outside="if (!$event.target.closest('.auth-fab')) chat = false"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2 scale-[0.98]" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 translate-y-2">
        <div class="auth-chat-head">
            <div>
                <strong>Asistente de acceso</strong>
                <span>Respuestas rápidas</span>
            </div>
            <button type="button" class="auth-chat-close" @click="chat = false" aria-label="Cerrar asistente">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
        </div>
        <div class="auth-chat-body" x-ref="body" aria-live="polite">
            <template x-for="(message, index) in messages" :key="index">
                <div class="auth-msg-row" :class="message.from === 'bot' ? 'is-bot' : 'is-user'">
                    <span class="auth-avatar" :class="message.from === 'bot' ? 'is-bot' : 'is-user'" aria-hidden="true">
                        <span x-show="message.from === 'bot'"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="5" y="8" width="14" height="11" rx="3"/><path d="M12 8V5"/><circle cx="12" cy="4" r="1"/>
                        <circle cx="9.5" cy="13" r="0.9" fill="currentColor" stroke="none"/><circle cx="14.5" cy="13" r="0.9" fill="currentColor" stroke="none"/><path d="M9.5 16.2h5"/>
                    </svg></span>
                        <b x-show="message.from !== 'bot'">Yo</b>
                    </span>
                    <div class="auth-msg" :class="message.from === 'bot' ? 'auth-msg-bot' : 'auth-msg-user'" x-html="message.text"></div>
                </div>
            </template>
            <div class="auth-msg-row is-bot" x-show="typing" aria-label="Escribiendo">
                <span class="auth-avatar is-bot" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="5" y="8" width="14" height="11" rx="3"/><path d="M12 8V5"/><circle cx="12" cy="4" r="1"/>
                        <circle cx="9.5" cy="13" r="0.9" fill="currentColor" stroke="none"/><circle cx="14.5" cy="13" r="0.9" fill="currentColor" stroke="none"/><path d="M9.5 16.2h5"/>
                    </svg></span>
                <div class="auth-msg auth-msg-bot"><span class="auth-typing"><i></i><i></i><i></i></span></div>
            </div>
        </div>
        <div class="auth-chat-options">
            <template x-for="item in faq" :key="item.q">
                <button type="button" class="auth-chip" @click="ask(item)" x-text="item.q"></button>
            </template>
        </div>
    </div>

    <div class="auth-fab">
        <div class="auth-fab-items" x-show="menu" x-cloak
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 translate-y-3">
            <button type="button" class="auth-fab-btn auth-fab-tip" :class="{ 'is-on': chat }" data-tip="Ayuda" @click="chat = !chat; if (chat) scroll()" aria-label="Abrir asistente de ayuda">
                <svg fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 9.5h9M7.5 13h5.5M20 12a8 8 0 0 1-11.7 7.1L4 20l.9-4.3A8 8 0 1 1 20 12Z" />
                </svg>
            </button>
            <button type="button" class="auth-fab-btn auth-fab-tip" data-auth-theme-toggle data-tip="Cambiar tema" aria-label="Cambiar tema (negro / azul)">
                <svg class="auth-icon-to-blue" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z" />
                </svg>
                <svg class="auth-icon-to-dark" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="12" cy="12" r="4" />
                    <path stroke-linecap="round" d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" />
                </svg>
            </button>
        </div>
        <button type="button" class="auth-fab-btn auth-fab-main" @click="toggleMenu()" :aria-expanded="menu.toString()" aria-label="Opciones: tema y ayuda">
            <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m6 15 6-6 6 6"/></svg>
        </button>
    </div>
</div>
