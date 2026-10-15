import './bootstrap';
import 'flowbite';
import './auth-particle-network';
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
            const trackTravel = Math.max(1, scroller.clientWidth - this.width);
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
                const viewport = Math.max(1, scroller.clientHeight - offset);
                const content = Math.max(viewport, scroller.scrollHeight - offset);
                this.height = Math.max(32, viewport * viewport / content);
                const travel = Math.max(0, viewport - this.height);
                const range = Math.max(1, scroller.scrollHeight - scroller.clientHeight);
                this.top = offset + (scroller.scrollTop / range) * travel;
            }

            this.horizontalVisible = scroller.clientWidth > 0 && scroller.scrollWidth > scroller.clientWidth;
            if (this.horizontalVisible) {
                const viewport = scroller.clientWidth;
                this.width = Math.max(48, viewport * viewport / scroller.scrollWidth);
                const travel = Math.max(0, viewport - this.width);
                const range = Math.max(1, scroller.scrollWidth - viewport);
                this.left = (scroller.scrollLeft / range) * travel;
            }
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
