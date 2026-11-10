import './bootstrap';
import 'flowbite';
import './auth-particle-network';
import './auth-fit';
import './activity-sortable';
import './organization-sortable';
import './clock-particle-network';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('backupOverlayScroll', () => ({
        visible: false,
        height: 32,
        top: 0,
        horizontalVisible: false,
        width: 32,
        left: 0,
        observer: null,
        stopHorizontalDrag: null,
        init() {
            this.$nextTick(() => {
                this.update();
                this.observer = new ResizeObserver(() => this.update());
                this.observer.observe(this.$refs.scroller);
            });
        },
        destroy() {
            this.observer?.disconnect();
            this.stopHorizontalDrag?.();
        },
        startHorizontalDrag(event) {
            const scroller = this.$refs.scroller;
            if (!scroller || !this.horizontalVisible) return;

            event.preventDefault();
            const startX = event.clientX;
            const startScrollLeft = scroller.scrollLeft;
            const rightOffset = Number(scroller.dataset.scrollRightOffset || 0);
            const trackTravel = Math.max(1, scroller.clientWidth - rightOffset - this.width);
            const scrollRange = Math.max(1, scroller.scrollWidth - scroller.clientWidth);
            const move = (moveEvent) => {
                scroller.scrollLeft = startScrollLeft + ((moveEvent.clientX - startX) / trackTravel) * scrollRange;
                this.update();
            };
            const stop = () => {
                window.removeEventListener('pointermove', move);
                window.removeEventListener('pointerup', stop);
                window.removeEventListener('pointercancel', stop);
                this.stopHorizontalDrag = null;
            };

            this.stopHorizontalDrag?.();
            this.stopHorizontalDrag = stop;
            window.addEventListener('pointermove', move);
            window.addEventListener('pointerup', stop);
            window.addEventListener('pointercancel', stop);
        },
        update() {
            const scroller = this.$refs.scroller;

            if (!scroller) {
                this.visible = false;
                this.horizontalVisible = false;
                return;
            }

            this.visible = scroller.clientHeight > 0 && scroller.scrollHeight > scroller.clientHeight;
            if (this.visible) {
                const offset = Number(scroller.dataset.scrollTopOffset || 0);
                const bottomOffset = Number(scroller.dataset.scrollBottomOffset || 0);
                const viewport = Math.max(1, scroller.clientHeight - offset - bottomOffset);
                const content = Math.max(viewport, scroller.scrollHeight - offset);
                this.height = Math.max(32, viewport * viewport / content);
                const travel = Math.max(0, viewport - this.height);
                const range = Math.max(1, scroller.scrollHeight - scroller.clientHeight);
                this.top = offset + (scroller.scrollTop / range) * travel;
            }

            this.horizontalVisible = scroller.clientWidth > 0 && scroller.scrollWidth > scroller.clientWidth;
            if (this.horizontalVisible) {
                const rightOffset = Number(scroller.dataset.scrollRightOffset || 0);
                const trackWidth = Math.max(1, scroller.clientWidth - rightOffset);
                this.width = Math.max(48, trackWidth * scroller.clientWidth / scroller.scrollWidth);
                const travel = Math.max(0, trackWidth - this.width);
                const range = Math.max(1, scroller.scrollWidth - scroller.clientWidth);
                this.left = (scroller.scrollLeft / range) * travel;
            }
        },
    }));

    window.Alpine.data('clockQuickTour', () => ({
        quickTourStep: 0,
        spotlightStyle: '',
        popoverStyle: '',
        maskTopStyle: '',
        maskLeftStyle: '',
        maskRightStyle: '',
        maskBottomStyle: '',
        previousBodyOverflow: '',
        quickTourSteps: [
            {
                target: 'clock-tour-client',
                title: '1. Elige un cliente',
                description: 'Busca y selecciona el cliente o departamento donde realizaste el trabajo.',
            },
            {
                target: 'clock-tour-activity',
                title: '2. Elige una actividad',
                description: 'Busca la actividad que vas a registrar y selecciónala de la lista.',
            },
            {
                target: 'clock-tour-start',
                title: '3. Inicia el registro',
                description: 'Con el cliente y la actividad elegidos, pulsa este botón. El recorrido no inicia ni guarda una actividad; solo te muestra dónde hacerlo.',
            },
        ],
        init() {
            this.quickTourViewportHandler = () => this.updateQuickTourPosition();
            this.quickTourKeyHandler = (event) => {
                if (! this.quickTourStep) return;
                if (event.key === 'Escape') {
                    event.preventDefault();
                    this.closeQuickTour();
                } else if (event.key === 'ArrowRight') {
                    event.preventDefault();
                    this.nextQuickTourStep();
                } else if (event.key === 'ArrowLeft') {
                    event.preventDefault();
                    this.previousQuickTourStep();
                } else if (event.key === 'Tab') {
                    const dialog = document.querySelector('[aria-labelledby="clock-tour-title"]');
                    const focusable = [...(dialog?.querySelectorAll('button:not([disabled])') ?? [])]
                        .filter((element) => element.getClientRects().length > 0);
                    const first = focusable[0];
                    const last = focusable[focusable.length - 1];

                    if (event.shiftKey && document.activeElement === first) {
                        event.preventDefault();
                        last?.focus();
                    } else if (! event.shiftKey && document.activeElement === last) {
                        event.preventDefault();
                        first?.focus();
                    }
                }
            };
        },
        destroy() {
            if (this.quickTourStep) {
                document.body.style.overflow = this.previousBodyOverflow;
            }
            this.removeQuickTourListeners();
        },
        startQuickTour() {
            this.quickTourStep = 1;
            this.previousBodyOverflow = document.body.style.overflow;
            document.body.style.overflow = 'hidden';
            window.addEventListener('resize', this.quickTourViewportHandler);
            window.addEventListener('scroll', this.quickTourViewportHandler, true);
            window.addEventListener('keydown', this.quickTourKeyHandler);
            this.$nextTick(() => {
                this.updateQuickTourPosition();
                this.$refs.quickTourNext?.focus();
            });
        },
        nextQuickTourStep() {
            if (this.quickTourStep >= this.quickTourSteps.length) {
                this.closeQuickTour();
                return;
            }

            this.quickTourStep++;
            this.$nextTick(() => {
                this.updateQuickTourPosition();
                this.$refs.quickTourNext?.focus();
            });
        },
        previousQuickTourStep() {
            if (this.quickTourStep <= 1) return;

            this.quickTourStep--;
            this.$nextTick(() => {
                this.updateQuickTourPosition();
                this.$refs.quickTourNext?.focus();
            });
        },
        closeQuickTour() {
            this.quickTourStep = 0;
            document.body.style.overflow = this.previousBodyOverflow;
            this.removeQuickTourListeners();
        },
        removeQuickTourListeners() {
            window.removeEventListener('resize', this.quickTourViewportHandler);
            window.removeEventListener('scroll', this.quickTourViewportHandler, true);
            window.removeEventListener('keydown', this.quickTourKeyHandler);
        },
        updateQuickTourPosition() {
            if (! this.quickTourStep) return;

            const target = document.getElementById(this.quickTourSteps[this.quickTourStep - 1]?.target);
            if (! target) return;

            const rect = target.getBoundingClientRect();
            const padding = 7;
            const left = Math.max(8, rect.left - padding);
            const top = Math.max(8, rect.top - padding);
            const right = Math.min(window.innerWidth - 8, rect.right + padding);
            const bottom = Math.min(window.innerHeight - 8, rect.bottom + padding);
            const width = Math.max(0, right - left);
            const height = Math.max(0, bottom - top);
            const mask = 'position:fixed;background:rgba(10,12,18,.68);pointer-events:auto;';

            this.spotlightStyle = `position:fixed;left:${left}px;top:${top}px;width:${width}px;height:${height}px;`;
            this.maskTopStyle = `${mask}left:0;top:0;width:100vw;height:${top}px;`;
            this.maskLeftStyle = `${mask}left:0;top:${top}px;width:${left}px;height:${height}px;`;
            this.maskRightStyle = `${mask}left:${right}px;top:${top}px;width:${Math.max(0, window.innerWidth - right)}px;height:${height}px;`;
            this.maskBottomStyle = `${mask}left:0;top:${bottom}px;width:100vw;height:${Math.max(0, window.innerHeight - bottom)}px;`;

            const popoverWidth = Math.min(380, window.innerWidth - 32);
            const popoverHeight = window.innerWidth < 500 ? 260 : 240;
            const popoverLeft = Math.min(window.innerWidth - popoverWidth - 16, Math.max(16, rect.left + rect.width / 2 - popoverWidth / 2));
            const below = rect.bottom + 16 + popoverHeight <= window.innerHeight;
            const popoverTop = below ? rect.bottom + 16 : Math.max(16, rect.top - popoverHeight - 16);
            this.popoverStyle = `position:fixed;left:${popoverLeft}px;top:${popoverTop}px;width:${popoverWidth}px;`;
        },
    }));
});

// Este plugin debe registrarse después de que Livewire 3 expone window.Livewire.
// Cargarlo antes produce un error JavaScript y rompe las peticiones wire:click.
const registerLivewireSortable = () => import('livewire-sortable');

if (window.Livewire) {
    registerLivewireSortable();
} else {
    document.addEventListener('livewire:init', registerLivewireSortable, { once: true });
}

// Además de mantener la sesión, este pulso permite reflejar presencia real:
// mientras el navegador autenticado siga abierto actualizará last_activity.
const SESSION_KEEP_ALIVE_INTERVAL = 30 * 1000;
let keepAliveRequest = null;
let keepAliveController = null;
let keepAliveTask = null;
let sessionIsClosing = false;
let sessionReady = false;
let queuedInitialAction = null;
let lastLivewireAction = null;
let retryingLivewireAction = false;

const goToLogin = () => {
    const loginUrl = document.body?.dataset.loginUrl || '/login';
    window.location.assign(loginUrl);
};

const keepSessionAlive = () => {
    const url = document.body?.dataset.sessionKeepAliveUrl;

    if (!url || !navigator.onLine || sessionIsClosing) {
        return Promise.resolve(false);
    }

    if (keepAliveTask) return keepAliveTask;

    keepAliveTask = (async () => {
        keepAliveController = new AbortController();
        keepAliveRequest = fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store',
            signal: keepAliveController.signal,
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        try {
            const response = await keepAliveRequest;
            if (response.status === 401 || response.status === 419) {
                goToLogin();
                return false;
            }

            if (!response.ok) return false;

            const data = await response.json();
            const csrfToken = data?.csrf_token;
            if (csrfToken) {
                document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', csrfToken);
                window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
            }

            return true;
        } catch {
            // Una caída de red no debe bloquear la interfaz ni el cronómetro.
            return false;
        } finally {
            keepAliveRequest = null;
            keepAliveController = null;
            keepAliveTask = null;
        }
    })();

    return keepAliveTask;
};

document.addEventListener('submit', (event) => {
    if (!event.target.closest?.('[data-session-logout]')) {
        return;
    }

    sessionIsClosing = true;
    keepAliveController?.abort();
}, { capture: true });

// Una pestaña restaurada por el navegador puede conservar HTML con un token
// anterior aunque la cookie ya pertenezca a una sesión nueva. Sincronizarlo
// antes del primer wire:click evita que Livewire reciba un 419 y recargue la
// página justo al abrir Crear, Editar o Eliminar.
const initialSessionSync = keepSessionAlive().finally(() => {
    sessionReady = true;
    const action = queuedInitialAction;
    queuedInitialAction = null;
    if (action?.isConnected) action.click();
});

document.addEventListener('click', (event) => {
    const action = event.target.closest?.('button[wire\\:click], a[wire\\:click]');
    if (!action) return;

    lastLivewireAction = action;
    if (sessionReady) return;

    event.preventDefault();
    event.stopImmediatePropagation();
    queuedInitialAction = action;
    void initialSessionSync;
}, { capture: true });

document.addEventListener('livewire:init', () => {
    window.Livewire.hook('request', ({ succeed, fail }) => {
        succeed(() => {
            lastLivewireAction = null;
            retryingLivewireAction = false;
        });

        fail(({ status, preventDefault }) => {
            if (status !== 419) {
                return;
            }

            preventDefault();
            const action = lastLivewireAction;
            if (retryingLivewireAction) {
                goToLogin();
                return;
            }

            retryingLivewireAction = true;
            keepSessionAlive().then((renewed) => {
                if (renewed && action?.isConnected) {
                    action.click();
                    return;
                }

                retryingLivewireAction = false;
            });
        });
    });
}, { once: true });

window.setInterval(keepSessionAlive, SESSION_KEEP_ALIVE_INTERVAL);
window.addEventListener('online', keepSessionAlive);
document.addEventListener('visibilitychange', () => {
    if (!document.hidden) {
        keepSessionAlive();
    }
});
