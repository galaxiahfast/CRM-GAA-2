import './bootstrap';
import 'flowbite';
import './auth-particle-network';
import './activity-sortable';
import './clock-particle-network';

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
const SESSION_RECOVERY_KEY = 'session-recovery-in-progress';
let keepAliveRequest = null;
let keepAliveController = null;
let sessionIsClosing = false;

const recoverExpiredSession = () => {
    const loginUrl = document.body?.dataset.loginUrl || '/login';

    // Evita un ciclo infinito si la recarga tampoco logra renovar el token.
    if (sessionStorage.getItem(SESSION_RECOVERY_KEY) === '1') {
        sessionStorage.removeItem(SESSION_RECOVERY_KEY);
        window.location.assign(loginUrl);
        return;
    }

    sessionStorage.setItem(SESSION_RECOVERY_KEY, '1');
    window.location.reload();
};

const keepSessionAlive = async () => {
    const url = document.body?.dataset.sessionKeepAliveUrl;

    if (!url || !navigator.onLine || sessionIsClosing || keepAliveRequest) {
        return;
    }

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
            recoverExpiredSession();
        }
    } catch {
        // Una caída de red no debe bloquear la interfaz ni el cronómetro.
    } finally {
        keepAliveRequest = null;
        keepAliveController = null;
    }
};

document.addEventListener('submit', (event) => {
    if (!event.target.closest?.('[data-session-logout]')) {
        return;
    }

    sessionIsClosing = true;
    keepAliveController?.abort();
}, { capture: true });

document.addEventListener('livewire:init', () => {
    window.Livewire.hook('request', ({ fail }) => {
        fail(({ status, preventDefault }) => {
            if (status !== 419) {
                return;
            }

            preventDefault();
            recoverExpiredSession();
        });
    });

    // Conserva brevemente la marca para impedir un ciclo si la primera
    // petición posterior a la recarga vuelve a responder 419.
    window.setTimeout(() => {
        sessionStorage.removeItem(SESSION_RECOVERY_KEY);
    }, 15_000);
}, { once: true });

window.setInterval(keepSessionAlive, SESSION_KEEP_ALIVE_INTERVAL);
window.addEventListener('online', keepSessionAlive);
document.addEventListener('visibilitychange', () => {
    if (!document.hidden) {
        keepSessionAlive();
    }
});
