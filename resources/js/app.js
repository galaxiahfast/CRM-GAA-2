import './bootstrap';
import 'flowbite';
import './auth-particle-network';
import './auth-fit';
import './activity-sortable';
import './row-step-scroll';
import './organization-sortable';
import './clock-particle-network';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('productivityDatePicker', (model) => ({
        value: model,
        open: false,
        cursor: new Date(),
        left: 16,
        top: 16,
        init() {
            this.resetCursor();
            this.reposition = () => { if (this.open) this.position(); };
            window.addEventListener('resize', this.reposition);
            document.addEventListener('scroll', this.reposition, true);
        },
        destroy() {
            window.removeEventListener('resize', this.reposition);
            document.removeEventListener('scroll', this.reposition, true);
        },
        resetCursor() {
            const parts = String(this.value || '').split('-').map(Number);
            this.cursor = parts.length === 3 && parts.every(Number.isFinite)
                ? new Date(parts[0], parts[1] - 1, 1) : new Date(new Date().getFullYear(), new Date().getMonth(), 1);
        },
        get formattedValue() {
            const parts = String(this.value || '').split('-');
            // Formato 11/11/26 (el año con dos cifras)
            return parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0].slice(-2)}` : 'Elige una fecha';
        },
        // Cada cifra va en una caja de ancho fijo para que todas las fechas midan lo mismo (un "1" es más angosto que un "0").
        get formattedHtml() {
            return this.formattedValue.replace(/\d/g, '<span class="prod-dg">$&</span>');
        },
        get monthLabel() {
            const label = new Intl.DateTimeFormat('es-MX', { month: 'long', year: 'numeric' }).format(this.cursor);
            return label.charAt(0).toUpperCase() + label.slice(1);
        },
        get days() {
            const start = new Date(this.cursor.getFullYear(), this.cursor.getMonth(), 1);
            start.setDate(start.getDate() - (start.getDay() + 6) % 7);
            return Array.from({ length: 42 }, (_, index) => {
                const date = new Date(start);
                date.setDate(start.getDate() + index);
                return { date, key: this.toIso(date), outside: date.getMonth() !== this.cursor.getMonth() };
            });
        },
        toIso(date) {
            return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
        },
        isToday(date) { return this.toIso(date) === this.toIso(new Date()); },
        changeMonth(delta) { this.cursor = new Date(this.cursor.getFullYear(), this.cursor.getMonth() + delta, 1); },
        selectDate(date) { this.value = this.toIso(date); this.cursor = new Date(date.getFullYear(), date.getMonth(), 1); this.open = false; },
        toggle() {
            this.open = !this.open;
            if (this.open) { this.resetCursor(); this.$nextTick(() => this.position()); }
        },
        position() {
            // El calendario está fuera del contenedor con zoom y recibe el mismo zoom de la aplicación (--app-zoom).
            // Las medidas se calculan en píxeles visibles y al final se dividen entre el zoom.
            const zoom = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--app-zoom')) || 1;
            const rect = this.$refs.trigger.getBoundingClientRect();
            const width = Math.min(320 * zoom, window.innerWidth - 32);
            const height = 352 * zoom; // alto fijo del calendario (siempre 6 semanas)
            // 10px entre el calendario y el campo; si abajo no cabe, se abre hacia arriba con la misma distancia.
            const gap = 10;
            const left = Math.max(16, Math.min(rect.left, window.innerWidth - width - 16));
            const top = rect.bottom + gap + height < window.innerHeight
                ? rect.bottom + gap : Math.max(16, rect.top - gap - height);
            this.left = left / zoom;
            this.top = top / zoom;
        },
    }));

    // Selector con búsqueda (escribir para filtrar) para asignar un colaborador de ManagerControl.
    window.Alpine.data('legacyEmployeePicker', (model, employees, onPick = null) => ({
        value: model,
        employees,
        onPick,
        open: false,
        q: '',
        active: 0,
        left: 16,
        top: 16,
        width: 320,
        init() {
            this.reposition = () => { if (this.open) this.position(); };
            window.addEventListener('resize', this.reposition);
            document.addEventListener('scroll', this.reposition, true);
        },
        destroy() {
            window.removeEventListener('resize', this.reposition);
            document.removeEventListener('scroll', this.reposition, true);
        },
        idHtml(value) { return 'ID ' + String(value).padStart(5, '0').replace(/[0-9]/g, (digit) => `<span style='display:inline-block;width:0.64em;text-align:center'>${digit}</span>`); },
        initials(text) {
            return String(text || '').trim().split(/\s+/).slice(0, 2).map((word) => word.charAt(0)).join('').toUpperCase();
        },
        normalize(text) {
            return String(text || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        },
        get label() {
            const found = this.employees.find((employee) => String(employee.id) === String(this.value ?? ''));
            return found ? found.name : '';
        },
        get filtered() {
            const tokens = this.normalize(this.q).split(/\s+/).filter(Boolean);
            const list = tokens.length
                ? this.employees.filter((employee) => { const name = this.normalize(employee.name); return tokens.every((token) => name.includes(token)); })
                : this.employees;
            return list.slice(0, 80);
        },
        openList() {
            if (this.open) return;
            this.open = true;
            this.q = '';
            this.active = 0;
            this.$nextTick(() => this.position());
        },
        choose(employee) {
            const next = employee ? String(employee.id) : '';
            const changed = next !== String(this.value ?? '');
            this.value = next;
            this.open = false;
            // Al elegir se guarda solo (sin botón «Asignar»); con la × se quita la asignación.
            if (changed && typeof this.onPick === 'function') this.onPick(next);
        },
        move(delta) {
            const max = this.filtered.length - 1;
            this.active = Math.max(0, Math.min(max, this.active + delta));
            this.$nextTick(() => { const el = this.$refs.panel && this.$refs.panel.querySelector('[data-active="true"]'); el && el.scrollIntoView({ block: 'nearest' }); });
        },
        pick() {
            const employee = this.filtered[this.active];
            if (employee) this.choose(employee);
        },
        position() {
            // Igual que el calendario: el panel está fuera del contenedor con zoom; se mide en píxeles visibles y se divide entre el zoom.
            const zoom = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--app-zoom')) || 1;
            // El panel ocupa la celda completa menos 10px a cada lado de sus líneas.
            const cell = this.$refs.trigger.closest('.prod-dcell') || this.$refs.trigger;
            const cellRect = cell.getBoundingClientRect();
            const inset = cell === this.$refs.trigger ? 0 : 10 * zoom;
            const rect = { left: cellRect.left + inset, width: cellRect.width - inset * 2, top: cellRect.top, bottom: cellRect.bottom };
            const panelHeight = this.$refs.panel ? this.$refs.panel.getBoundingClientRect().height : 340 * zoom;
            const gap = 10;
            const width = Math.min(rect.width, window.innerWidth - 32);
            const left = Math.max(16, Math.min(rect.left, window.innerWidth - width - 16));
            const top = rect.bottom + gap + panelHeight < window.innerHeight
                ? rect.bottom + gap : Math.max(16, rect.top - gap - panelHeight);
            this.width = width / zoom;
            this.left = left / zoom;
            this.top = top / zoom;
        },
    }));

    window.Alpine.data('backupOverlayScroll', () => ({
        visible: false,
        height: 32,
        top: 0,
        horizontalVisible: false,
        width: 32,
        left: 0,
        observer: null,
        stopHorizontalDrag: null,
        // Con data-scroll-on-hover el indicador de scroll solo se muestra mientras el cursor está sobre el contenedor.
        hoverOnly: false,
        hovering: false,
        init() {
            this.hoverOnly = this.$el.hasAttribute('data-scroll-on-hover');
            if (this.hoverOnly) {
                this.$el.addEventListener('mouseenter', () => { this.hovering = true; this.update(); });
                this.$el.addEventListener('mouseleave', () => { this.hovering = false; this.update(); });
            }
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

            const canScroll = scroller.clientHeight > 0 && scroller.scrollHeight > scroller.clientHeight;
            this.visible = canScroll && (!this.hoverOnly || this.hovering);
            if (canScroll) {
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

    window.Alpine.data('clockQuickTour', (customSteps = null, tourSets = {}) => ({
        quickTourStep: 0,
        spotlightStyle: '',
        popoverStyle: '',
        maskTopStyle: '',
        maskLeftStyle: '',
        maskRightStyle: '',
        maskBottomStyle: '',
        previousBodyOverflow: '',
        // Solo se muestran los pasos cuyo elemento existe (los de la tabla necesitan al menos una actividad).
        quickTourSteps: [],
        tourSets,
        // Los pasos del Cronómetro por defecto; otras secciones (Productividad) pasan los suyos.
        allQuickTourSteps: customSteps ?? [
            {
                target: 'clock-tour-activity',
                title: '1. Elige una actividad',
                description: 'Busca la actividad que vas a registrar y selecciónala de la lista.',
            },
            {
                target: 'clock-tour-client',
                title: '2. Elige un cliente',
                description: 'Busca y selecciona el cliente o departamento donde realizaste el trabajo.',
            },
            {
                target: 'clock-tour-start',
                title: '3. Inicia el registro',
                description: 'Con el cliente y la actividad elegidos, pulsa este botón. El recorrido no inicia ni guarda una actividad; solo te muestra dónde hacerlo.',
            },
            {
                target: 'clock-tour-toggle',
                title: '4. Pausa o reanuda una actividad',
                description: 'En la columna Acción, el icono de pausa detiene el tiempo de la actividad en progreso y el de reanudar continúa una pausada. Si reanudas una mientras otra está en progreso, la otra se pausa sola.',
            },
            {
                target: 'clock-tour-row',
                title: '5. Cambia el orden de las filas',
                description: 'Mantén apretado el clic sobre una fila, fuera de los iconos, y arrástrala hacia arriba o hacia abajo. Al soltarla se guarda el nuevo orden y la columna N° se actualiza sola.',
            },
            {
                target: 'clock-tour-delete',
                title: '6. Elimina una actividad',
                description: 'La X borra la actividad y todo su tiempo. Te pedirá escribir su nombre exacto para confirmar. Si está en progreso, primero debes pausarla.',
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
        startQuickTour(set = null) {
            const source = set && this.tourSets[set] ? this.tourSets[set] : this.allQuickTourSteps;
            this.quickTourSteps = source.filter((step) => document.getElementById(step.target));
            if (! this.quickTourSteps.length) return;

            this.quickTourStep = 1;
            this.previousBodyOverflow = document.body.style.overflow;
            document.body.style.overflow = 'hidden';
            window.addEventListener('resize', this.quickTourViewportHandler);
            window.addEventListener('scroll', this.quickTourViewportHandler, true);
            window.addEventListener('keydown', this.quickTourKeyHandler);
            this.$nextTick(() => {
                this.revealQuickTourTarget();
                this.updateQuickTourPosition();
                this.$refs.quickTourNext?.focus({ preventScroll: true });
            });
        },
        nextQuickTourStep() {
            if (this.quickTourStep >= this.quickTourSteps.length) {
                this.closeQuickTour();
                return;
            }

            this.quickTourStep++;
            this.$nextTick(() => {
                this.revealQuickTourTarget();
                this.updateQuickTourPosition();
                this.$refs.quickTourNext?.focus({ preventScroll: true });
            });
        },
        previousQuickTourStep() {
            if (this.quickTourStep <= 1) return;

            this.quickTourStep--;
            this.$nextTick(() => {
                this.revealQuickTourTarget();
                this.updateQuickTourPosition();
                this.$refs.quickTourNext?.focus({ preventScroll: true });
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
        // Si lo que se explica está fuera de la vista o debajo de la barra superior fija, la página se desplaza
        // hasta dejarlo visible; el resaltado y la ventana se recolocan mientras se desplaza.
        revealQuickTourTarget() {
            const target = document.getElementById(this.quickTourSteps[this.quickTourStep - 1]?.target);
            if (! target) return;

            const rect = target.getBoundingClientRect();
            const navBottom = document.getElementById('main-nav')?.getBoundingClientRect().bottom ?? 0;
            const margin = 20;
            const hidden = rect.top < navBottom + margin || rect.bottom > window.innerHeight - margin;
            if (! hidden) return;

            const fitsBelowNav = rect.height <= window.innerHeight - navBottom - margin * 2;
            target.scrollIntoView({ behavior: 'smooth', block: fitsBelowNav ? 'center' : 'start' });
            // Respaldo: cuando termina el desplazamiento suave se vuelve a colocar todo.
            setTimeout(() => this.updateQuickTourPosition(), 450);
        },
        updateQuickTourPosition() {
            if (! this.quickTourStep) return;

            const target = document.getElementById(this.quickTourSteps[this.quickTourStep - 1]?.target);
            if (! target) return;

            // Sin borde: solo se deja un espacio de 10px entre el elemento y el fondo atenuado.
            const raw = target.getBoundingClientRect();
            const pad = 10;
            const rect = {
                left: raw.left - pad,
                top: raw.top - pad,
                right: raw.right + pad,
                bottom: raw.bottom + pad,
                width: raw.width + pad * 2,
                height: raw.height + pad * 2,
            };
            const left = Math.max(0, rect.left);
            const top = Math.max(0, rect.top);
            const right = Math.min(window.innerWidth, rect.right);
            const bottom = Math.min(window.innerHeight, rect.bottom);
            const width = Math.max(0, right - left);
            const height = Math.max(0, bottom - top);
            // Las franjas solo bloquean los clics; el oscurecido lo dibuja la sombra del recuadro para poder redondear las esquinas.
            const mask = 'position:fixed;background:transparent;pointer-events:auto;';
            const borderRadius = Math.min(12, Math.min(width, height) / 2);

            this.spotlightStyle = `position:fixed;left:${left}px;top:${top}px;width:${width}px;height:${height}px;border-radius:${borderRadius}px;box-shadow:0 0 0 100vmax rgba(10,12,18,.68);`;
            this.maskTopStyle = `${mask}left:0;top:0;width:100vw;height:${top}px;`;
            this.maskLeftStyle = `${mask}left:0;top:${top}px;width:${left}px;height:${height}px;`;
            this.maskRightStyle = `${mask}left:${right}px;top:${top}px;width:${Math.max(0, window.innerWidth - right)}px;height:${height}px;`;
            this.maskBottomStyle = `${mask}left:0;top:${bottom}px;width:100vw;height:${Math.max(0, window.innerHeight - bottom)}px;`;

            const dialog = document.querySelector('[aria-labelledby="clock-tour-title"]');
            // El mensaje está fuera del contenedor con zoom, así que se le aplica el mismo zoom de la aplicación
            // (--app-zoom). Las medidas de abajo son píxeles visibles; al final se dividen entre el zoom.
            const zoom = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--app-zoom')) || 1;
            const popoverWidth = Math.min(440 * zoom, window.innerWidth - 40);
            const popoverHeight = dialog?.getBoundingClientRect().height || (window.innerWidth < 500 ? 320 : 300) * zoom;
            const gutter = 20;
            const sideGap = 20;
            const rightSpace = window.innerWidth - rect.right - gutter;
            const leftSpace = rect.left - gutter;
            let popoverLeft;
            let popoverTop;

            if (rightSpace >= popoverWidth + sideGap) {
                popoverLeft = rect.right + sideGap;
                popoverTop = Math.min(window.innerHeight - popoverHeight - gutter, Math.max(gutter, rect.top + rect.height / 2 - popoverHeight / 2));
            } else if (leftSpace >= popoverWidth + sideGap) {
                popoverLeft = rect.left - popoverWidth - sideGap;
                popoverTop = Math.min(window.innerHeight - popoverHeight - gutter, Math.max(gutter, rect.top + rect.height / 2 - popoverHeight / 2));
            } else {
                popoverLeft = Math.min(window.innerWidth - popoverWidth - gutter, Math.max(gutter, rect.left + rect.width / 2 - popoverWidth / 2));
                const below = rect.bottom + sideGap + popoverHeight <= window.innerHeight;
                popoverTop = below ? rect.bottom + sideGap : Math.max(gutter, rect.top - popoverHeight - sideGap);
            }

            this.popoverStyle = `position:fixed;zoom:${zoom};left:${popoverLeft / zoom}px;top:${popoverTop / zoom}px;width:${popoverWidth / zoom}px;max-width:none;transform:translate(0,0);`;
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
