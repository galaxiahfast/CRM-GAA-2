import './bootstrap';
import 'flowbite';
import './auth-particle-network';

// Este plugin debe registrarse después de que Livewire 3 expone window.Livewire.
// Cargarlo antes produce un error JavaScript y rompe las peticiones wire:click.
const registerLivewireSortable = () => import('livewire-sortable');

if (window.Livewire) {
    registerLivewireSortable();
} else {
    document.addEventListener('livewire:init', registerLivewireSortable, { once: true });
}

const SESSION_KEEP_ALIVE_INTERVAL = 5 * 60 * 1000;
const SESSION_RECOVERY_KEY = 'session-recovery-in-progress';

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

    if (!url || !navigator.onLine) {
        return;
    }

    try {
        const response = await fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (response.status === 401 || response.status === 419) {
            recoverExpiredSession();
        }
    } catch {
        // Una caída de red no debe bloquear la interfaz ni el cronómetro.
    }
};

const submitLogoutSafely = async (event) => {
    const form = event.target.closest('form[data-session-logout]');

    if (!form || form.dataset.sessionLogoutReady === '1') {
        return;
    }

    event.preventDefault();

    const keepAliveUrl = document.body?.dataset.sessionKeepAliveUrl;
    const loginUrl = document.body?.dataset.loginUrl || '/login';

    if (!keepAliveUrl) {
        form.submit();
        return;
    }

    try {
        const response = await fetch(keepAliveUrl, {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            window.location.assign(loginUrl);
            return;
        }

        form.dataset.sessionLogoutReady = '1';
        form.requestSubmit();
    } catch {
        // Sin conexión no hay una sesión remota que cerrar. Evita dejar al
        // usuario atrapado en una confirmación de expiración del navegador.
        window.location.assign(loginUrl);
    }
};

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
document.addEventListener('submit', submitLogoutSafely, true);
