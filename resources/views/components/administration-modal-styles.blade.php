@once
<style>
    /* Botones de los formularios: verdes (claro #15803d, oscuro #4ade80). El principal va relleno; el secundario, con borde. */
    .admin-btn { --admin-green: #15803d; min-height: 47px; border: 1px solid var(--admin-green) !important; }
    html.module-dark-theme .admin-btn { --admin-green: #4ade80; }
    .admin-btn-primary { background-color: color-mix(in srgb, var(--admin-green) 12%, transparent) !important; color: var(--admin-green) !important; }
    .admin-btn-secondary { background-color: transparent !important; color: var(--admin-green) !important; }
    .admin-btn svg, [data-administration-modal] footer button svg { color: inherit !important; stroke: currentColor !important; }
    [data-administration-modal] .administration-form-panel,
    [data-administration-modal] .administration-form-panel > form {
        background-color: #ffffff !important;
    }

    /* Encabezado, cuerpo y pie en blanco; las líneas los separan. */
    [data-administration-modal] .administration-form-panel header,
    [data-administration-modal] .administration-form-panel footer {
        background-color: #ffffff !important;
        border-color: #d4d4d8 !important;
    }
    /* Scroll igual al del menú principal: delgado (3px), azul fuerte, sin flechas y siempre visible. */
    .administration-form-scrollbar {
        scrollbar-width: none !important;
        overscroll-behavior: contain;
    }

    .administration-form-scrollbar::-webkit-scrollbar {
        width: 0 !important;
        height: 0 !important;
        display: none;
    }

    .administration-form-thumb {
        position: absolute;
        right: 0;
        top: 0;
        z-index: 20;
        width: 3px;
        min-height: 32px;
        border-radius: 9999px;
        background: #1A3A6B;
        pointer-events: none;
    }

    /* El scroll (3px) siempre reserva su carril, y se descuenta del relleno derecho (20px - 3px):
       así el contenido queda con 20px a cada lado, centrado y alineado con el encabezado y el pie,
       haya o no scroll. */
    [data-administration-modal] .administration-form-scrollbar > div {
        padding-right: 17px !important;
    }

    /* Títulos de sección iguales en todos los formularios (como Usuarios): sin mayúsculas forzadas,
       sin espaciado entre letras y con el mismo tamaño que el resto del texto. */
    [data-administration-modal] :is(h2, h3) {
        text-transform: none !important;
        letter-spacing: normal !important;
        font-size: inherit !important;
        line-height: inherit !important;
        font-weight: 400 !important;
    }

    /* Secciones sin cajas ni líneas divisorias; mismo espacio título → etiqueta → campo (10px). */
    [data-administration-modal] section:not([class*="bg-blue-50"]) {
        border: 0 !important;
        border-radius: 0 !important;
        background: transparent !important;
        padding: 0 !important;
    }

    /* Línea divisoria entre secciones: 20px de aire arriba y abajo (la última sección no lleva). */
    [data-administration-modal] section:not([class*="bg-blue-50"]):not(:last-child),
    .org-user-modal section:not([class*="bg-blue-50"]):not(:last-child) {
        border-bottom: 1px solid #d4d4d8 !important;
        padding-bottom: 20px !important;
    }
    html.module-dark-theme [data-administration-modal] section:not([class*="bg-blue-50"]):not(:last-child),
    html.module-dark-theme .org-user-modal section:not([class*="bg-blue-50"]):not(:last-child) {
        border-bottom-color: #27272a !important;
    }

    /* Títulos de sección e icono del encabezado: azul del panel, sin recuadro. */
    .admin-section-title, .admin-title-icon { color: #1A3A6B !important; }
    html.module-dark-theme .admin-section-title, html.module-dark-theme .admin-title-icon,
    html.module-dark-theme #main-content .admin-section-title, html.module-dark-theme #main-content .admin-title-icon { color: #6aa9ff !important; }

    .admin-picker-chevron { color: #71717a; }
    html.module-dark-theme .admin-picker-chevron { color: #ffffff; }

    /* Títulos de sección de todos los formularios de administración (antes en mayúsculas, negritas y grises): azul, normales y con 20px de aire. */
    [data-administration-modal] :is(h2, h3)[class*="tracking-[.12em]"],
    .org-user-modal :is(h2, h3)[class*="tracking-[.12em]"] {
        color: #1A3A6B !important;
        font-size: 15px !important;
        font-weight: 400 !important;
        letter-spacing: 0 !important;
        line-height: 25px !important;
        text-transform: none !important;
        margin-bottom: 20px !important;
    }
    html.module-dark-theme #main-content [data-administration-modal] :is(h2, h3)[class*="tracking-[.12em]"],
    html.module-dark-theme #main-content .org-user-modal :is(h2, h3)[class*="tracking-[.12em]"] { color: #6aa9ff !important; }

    /* Campos en el modo oscuro: fondo de la página y borde fino, nunca el gris de relleno. */
    html.module-dark-theme #main-content [data-administration-modal] :is(input:not([type="checkbox"]):not([type="radio"]), select, textarea, [role="combobox"]) {
        background-color: #09090b !important;
        border-color: #27272a !important;
        color: #ffffff !important;
    }
    html.module-dark-theme #main-content [data-administration-modal] :is(input, select, textarea):disabled { background-color: #0f0f12 !important; }
    html.module-dark-theme #main-content [data-administration-modal] :is(input, select, textarea)::placeholder { color: #ffffff !important; opacity: 0.7; }

    /* Botón de eliminar: mismo trazo que los verdes (borde y texto rojos con fondo suave), no relleno sólido. */
    [data-administration-modal] footer button[class*="bg-red"],
    .org-user-modal button[class*="bg-red"] {
        --admin-red: #dc2626;
        border: 1px solid var(--admin-red) !important;
        background: color-mix(in srgb, var(--admin-red) 12%, transparent) !important;
        color: var(--admin-red) !important;
    }
    html.module-dark-theme [data-administration-modal] footer button[class*="bg-red"],
    html.module-dark-theme .org-user-modal button[class*="bg-red"] { --admin-red: #f87171; }

    /* Botón cerrar (×): solo la ×, sin recuadro ni relleno. */
    [data-administration-modal] .administration-form-panel header button[aria-label="Cerrar"] {
        border-color: transparent !important;
        background-color: transparent !important;
        color: #71717a !important;
    }

    [data-administration-modal] .administration-form-panel header button[aria-label="Cerrar"]:hover {
        border-color: transparent !important;
        background-color: transparent !important;
        color: #18181b !important;
    }

    html.module-dark-theme [data-administration-modal] .administration-form-panel header button[aria-label="Cerrar"] { color: #ffffff !important; }
    html.module-dark-theme [data-administration-modal] .administration-form-panel header button[aria-label="Cerrar"]:hover { color: #ffffff !important; opacity: 0.7; }

    /* Sin negritas dentro del formulario: solo el título principal. */
    [data-administration-modal] *:not(h1) {
        font-weight: 400 !important;
    }

    [data-administration-modal] header h1 {
        font-weight: 600 !important;
    }

    /* Cajas de texto sin aire interno (line-height: 1): la separación entre elementos es exacta
       y no depende del tamaño de letra ni de si es título, etiqueta o párrafo. */
    [data-administration-modal] section h2 {
        margin: 0 0 var(--admin-form-gap, 10px) !important;
        font-size: 15px !important;
        letter-spacing: 0 !important;
        line-height: 1 !important;
        text-transform: none !important;
        color: #000 !important;
    }

    [data-administration-modal] header h1,
    [data-administration-modal] header p {
        line-height: 1 !important;
        color: #000000 !important;
    }

    [data-administration-modal] label {
        line-height: 1 !important;
    }

    [data-administration-modal] textarea {
        resize: none;
    }

    /* Sin borde ni halo morado al enfocar: el campo conserva su borde gris. */
    [data-administration-modal] :is(input:not([type="checkbox"]):not([type="radio"]), select, textarea, [role="combobox"]):focus,
    [data-administration-modal] :is(input:not([type="checkbox"]):not([type="radio"]), select, textarea, [role="combobox"]):focus-visible {
        border-color: #d4d4d8 !important;
        outline: none !important;
        box-shadow: none !important;
        --tw-ring-shadow: 0 0 #0000 !important;
        --tw-ring-offset-shadow: 0 0 #0000 !important;
    }
    /* Mismo gris intermedio (#d4d4d8) en bordes de campos, botones e icono del encabezado. */
    [data-administration-modal] :is(input:not([type="checkbox"]):not([type="radio"]), select, textarea, [role="combobox"]) {
        border-color: #d4d4d8 !important;
    }

    [data-administration-modal] header div.border {
        border-color: #d4d4d8 !important;
    }

    [data-administration-modal] footer button {
        --admin-green: #15803d;
        min-height: 47px;
        border: 1px solid var(--admin-green) !important;
        border-radius: 0.75rem !important;
        background: transparent !important;
        color: var(--admin-green) !important;
        box-shadow: none !important;
    }
    html.module-dark-theme [data-administration-modal] footer button { --admin-green: #4ade80; }
    [data-administration-modal] footer button:last-child:not([class*="bg-red"]) {
        background: color-mix(in srgb, var(--admin-green) 12%, transparent) !important;
        color: var(--admin-green) !important;
    }
    /* ===== Diseño común para todos los formularios de administración ===== */

    /* Campos de texto, listas y áreas de texto iguales a los del formulario de Usuarios. */
    [data-administration-modal] :is(input:not([type="checkbox"]):not([type="radio"]), select, textarea) {
        border-radius: 0.75rem !important;
        background-color: #ffffff !important;
        color: #000000 !important;
    }

    [data-administration-modal] textarea {
        padding-top: 12px !important;
        padding-bottom: 12px !important;
    }

    [data-administration-modal] :is(input, select, textarea)::placeholder {
        color: #71717a;
        opacity: 1;
    }

    [data-administration-modal] :is(input, select, textarea):disabled {
        background-color: #f4f4f5 !important;
        color: #000000 !important;
        cursor: not-allowed;
    }

    /* 20px entre título → etiqueta → campo → siguiente elemento. */
    [data-administration-modal] section {
        --admin-form-gap: 20px;
    }

    [data-administration-modal] label + :is(input, select, textarea),
    [data-administration-modal] label + [wire\:key],
    [data-administration-modal] legend + * {
        margin-top: 20px !important;
    }

    [data-administration-modal] .administration-form-scrollbar [class*="gap-[15px]"],
    [data-administration-modal] .administration-form-scrollbar [class*="gap-[10px]"]:is(.grid, .flex-col) {
        gap: 20px !important;
    }

    [data-administration-modal] .administration-form-scrollbar [class*="space-y-5"] > * + *,
    [data-administration-modal] .administration-form-scrollbar [class*="space-y-[15px]"] > * + * {
        margin-top: 20px !important;
    }

    [data-administration-modal] :is(section, fieldset) [class*="mt-[15px]"],
    [data-administration-modal] :is(section, fieldset) [class*="mt-[10px]"]:not(input):not(label) {
        margin-top: 20px !important;
    }

    /* Colores azules antiguos → grises y negro del diseño de Usuarios. */
    [data-administration-modal] :is([class*="text-[#1A3A6B]"], [class*="text-[#102A52]"], [class*="text-[#1F4D86]"], [class*="text-gray-700"], [class*="text-gray-900"]) {
        color: #000000 !important;
    }

    [data-administration-modal] :is([class*="text-[#55749D]"], [class*="text-[#8290A3]"], [class*="text-gray-500"], [class*="text-gray-400"], [class*="text-zinc-500"], [class*="text-zinc-400"]) {
        color: #000000 !important;
    }

    [data-administration-modal] :is([class*="border-[#CAD7E7]"], [class*="border-[#B7CEEA]"], [class*="border-[#D7E2F0]"], [class*="border-[#D5DDE8]"], [class*="border-[#AFC7E3]"], [class*="border-gray-200"], [class*="border-gray-300"]) {
        border-color: #d4d4d8 !important;
    }

    [data-administration-modal] :is([class*="bg-[#EAF2FC]"], [class*="bg-[#DCE9F8]"], [class*="bg-[#EEF5FF]"], [class*="bg-[#E7F0FB]"], [class*="bg-[#E8EEF7]"], [class*="bg-[#F1F3F6]"], [class*="bg-blue-50"]) {
        background-color: #f4f4f5 !important;
    }

    [data-administration-modal] .administration-form-scrollbar [class*="border-[#1A3A6B]"] {
        border-color: #18181b !important;
    }

    [data-administration-modal] [class*="rounded-lg"]:is(label, button, div, fieldset) {
        border-radius: 0.75rem;
    }

    /* Listas desplegables propias dentro del modal: mismo aspecto que las de los selectores. */
    [data-administration-modal] [role="listbox"] {
        margin-top: 10px !important;
        padding: 0 !important;
        border-color: #d4d4d8 !important;
        border-radius: 0.75rem !important;
        background-color: #ffffff !important;
        box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1) !important;
    }

    [data-administration-modal] [role="listbox"] [role="option"] {
        display: block;
        width: 100%;
        padding: 20px !important;
        border-radius: 0 !important;
        background: transparent !important;
        color: #000000 !important;
        line-height: 1 !important;
        text-align: left;
    }

    [data-administration-modal] [role="listbox"] [role="option"]:hover,
    [data-administration-modal] [role="listbox"] [role="option"]:focus {
        background-color: #f4f4f5 !important;
    }

    [data-administration-modal] [role="listbox"] [role="option"] :is(span, strong) {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        line-height: 1 !important;
    }

    [data-administration-modal] [role="listbox"] > p {
        padding: 20px !important;
        line-height: 1 !important;
    }

    /* Mismas listas en el modo oscuro (y en la ventana de edición del organigrama): fondo de la página, borde fino y opción elegida en azul. */
    .org-user-modal [role="listbox"] { margin-top: 10px; border-radius: 0.75rem; overflow: hidden; }
    html.module-dark-theme #main-content :is([data-administration-modal], .org-user-modal) [role="listbox"] {
        background-color: #09090b !important;
        border-color: #27272a !important;
        box-shadow: none !important;
    }
    html.module-dark-theme #main-content :is([data-administration-modal], .org-user-modal) [role="listbox"] [role="option"] {
        background: transparent !important;
        color: #ffffff !important;
    }
    html.module-dark-theme #main-content :is([data-administration-modal], .org-user-modal) [role="listbox"] [role="option"]:is(:hover, :focus) {
        background-color: #131316 !important;
    }
    :is([data-administration-modal], .org-user-modal) [role="listbox"] [role="option"][aria-selected="true"] { color: #1A3A6B !important; }
    html.module-dark-theme #main-content :is([data-administration-modal], .org-user-modal) [role="listbox"] [role="option"][aria-selected="true"] { color: #6aa9ff !important; }

    /* Los títulos de sección usan el color del texto en cada tema. */
    .admin-section-title,
    :is([data-administration-modal], .org-user-modal) :is(h2, h3)[class*="tracking-[.12em]"] {
        color: #000000 !important;
    }
    html.module-dark-theme .admin-section-title,
    html.module-dark-theme #main-content .admin-section-title,
    html.module-dark-theme #main-content :is([data-administration-modal], .org-user-modal) :is(h2, h3)[class*="tracking-[.12em]"] {
        color: #ffffff !important;
    }

    /* Ritmo del formulario: 20 px a los lados, 15 px arriba/abajo y entre líneas. */
    [data-administration-modal] .administration-form-scrollbar > div {
        padding: 15px 17px 15px 20px !important;
    }
    [data-administration-modal] .administration-form-panel > form > :is(header, footer),
    [data-administration-modal] .administration-form-panel > :is(header, footer) {
        padding: 15px 20px !important;
    }
    [data-administration-modal] :is(textarea, .permission-access-card span.block, .permission-access-card strong.block) {
        line-height: 30px !important;
    }
    [data-administration-modal] :is(h2, h3, legend) {
        color: #18181b !important;
    }
    html.module-dark-theme .administration-monochrome [data-administration-modal] :is(h2, h3, legend) {
        color: #ffffff !important;
    }

    /* Tarjetas de permisos legibles en ambos temas, incluso cuando aún no están elegidas. */
    [data-administration-modal] .permission-access-card {
        padding: 15px 20px !important;
        opacity: 1 !important;
        background-color: #fafafa !important;
        border-color: #d4d4d8 !important;
    }
    [data-administration-modal] .permission-access-card :is(strong, span) {
        color: #18181b !important;
    }
    html.module-dark-theme .administration-monochrome [data-administration-modal] .permission-access-card {
        background-color: #111114 !important;
        border-color: #27272a !important;
    }
    html.module-dark-theme .administration-monochrome [data-administration-modal] .permission-access-card :is(strong, span) {
        color: #ffffff !important;
    }
    html.module-dark-theme .administration-monochrome [data-administration-modal] .permission-access-card > div > label > span:first-child {
        background-color: #18181b !important;
        border-color: #3f3f46 !important;
    }
    [data-administration-modal] :is(.role-permission-choice, .role-permission-preview) {
        border-color: #d4d4d8 !important;
        background-color: #fafafa !important;
    }
    [data-administration-modal] .role-permission-choice[aria-pressed="true"] {
        border-color: #15803d !important;
        background-color: #f0fdf4 !important;
    }
    html.module-dark-theme .administration-monochrome [data-administration-modal] :is(.role-permission-choice, .role-permission-preview) {
        border-color: #27272a !important;
        background-color: #111114 !important;
    }
    html.module-dark-theme .administration-monochrome [data-administration-modal] .role-permission-choice[aria-pressed="true"] {
        border-color: #4ade80 !important;
        background-color: #102217 !important;
    }
    html.module-dark-theme .administration-monochrome [data-administration-modal] .role-permission-choice :is(strong, span),
    html.module-dark-theme .administration-monochrome [data-administration-modal] .role-permission-preview legend {
        color: #ffffff !important;
    }

    /* Los catálogos Livewire teletransportan sus modales a body. */
    html.module-dark-theme body [data-administration-modal] .administration-form-panel,
    html.module-dark-theme body [data-administration-modal] .administration-form-panel > form,
    html.module-dark-theme body [data-administration-modal] .administration-form-panel :is(header, footer),
    html.module-dark-theme body [data-administration-modal] .administration-form-scrollbar,
    html.module-dark-theme body [data-administration-modal] .administration-form-scrollbar > div {
        background-color: #09090b !important;
        border-color: #27272a !important;
        color: #ffffff !important;
    }
    html.module-dark-theme body [data-administration-modal] :is(section, fieldset, article) {
        border-color: #27272a !important;
        background-color: #09090b !important;
    }
    html.module-dark-theme body [data-administration-modal] :is(h1, h2, h3, h4, legend, label, p, strong, span):not(button *) {
        color: #ffffff !important;
    }
    html.module-dark-theme body [data-administration-modal] :is(input:not([type="checkbox"]):not([type="radio"]), select, textarea, [role="combobox"]) {
        background-color: #09090b !important;
        border-color: #27272a !important;
        color: #ffffff !important;
    }
    html.module-dark-theme body [data-administration-modal] :is(input, select, textarea)::placeholder {
        color: #a1a1aa !important;
        opacity: 1;
    }
    html.module-dark-theme body [data-administration-modal] .permission-access-card,
    html.module-dark-theme body [data-administration-modal] .role-permission-choice,
    html.module-dark-theme body [data-administration-modal] .role-permission-preview {
        background-color: #111114 !important;
        border-color: #27272a !important;
    }
    html.module-dark-theme body [data-administration-modal] .role-permission-choice[aria-pressed="true"] {
        border-color: #4ade80 !important;
        background-color: #102217 !important;
    }
    html.module-dark-theme body [data-administration-modal] .permission-access-card :is(strong, span),
    html.module-dark-theme body [data-administration-modal] .role-permission-choice :is(strong, span) {
        color: #ffffff !important;
    }
    html.module-dark-theme #main-content .administration-monochrome [data-administration-modal] :is(h2, h3, legend) {
        color: #ffffff !important;
    }
    html.module-dark-theme body [data-administration-modal] .role-permission-choice > span > span:last-child {
        background-color: #27272a !important;
        color: #ffffff !important;
    }
    [data-administration-modal] .permission-access-card button[aria-label^="Vista previa"] {
        background-color: #f4f4f5 !important;
        color: #18181b !important;
    }
    html.module-dark-theme body [data-administration-modal] .permission-access-card button[aria-label^="Vista previa"] {
        background-color: #27272a !important;
        color: #ffffff !important;
    }
    html.module-dark-theme #main-content .administration-monochrome [data-administration-modal] .permission-access-card button[aria-label^="Vista previa"] {
        background-color: #27272a !important;
        color: #ffffff !important;
    }
    html.module-dark-theme body [data-administration-modal] :is(div[class*="bg-white/50"], span[class*="bg-[#DCE9F8]"]) {
        background-color: #111114 !important;
        color: #ffffff !important;
    }

    /* Roles: opciones de grupo, vista de accesos y listas de usuarios (sin cajas grises ni pastillas). */
    [data-administration-modal] .role-choice {
        display: flex; align-items: center; justify-content: space-between; gap: 20px;
        width: 100%; min-width: 0; padding: 15px 20px; text-align: left;
        border: 1px solid #d4d4d8; border-radius: 12px; background: transparent !important;
        color: #18181b !important; font-size: 15px; line-height: 30px; cursor: pointer;
        transition: border-color .15s, background-color .15s;
    }
    [data-administration-modal] .role-choice:hover:not(:disabled) { border-color: #15803d; }
    [data-administration-modal] .role-choice[aria-pressed="true"] { border-color: #15803d; background: rgba(21, 128, 61, .12) !important; }
    [data-administration-modal] .role-choice:disabled { opacity: .5; cursor: not-allowed; }
    [data-administration-modal] .role-choice .role-choice-count { flex: none; color: #1d4ed8 !important; }
    html.module-dark-theme [data-administration-modal] .role-choice { border-color: #27272a; color: #ffffff !important; }
    html.module-dark-theme [data-administration-modal] .role-choice:hover:not(:disabled),
    html.module-dark-theme [data-administration-modal] .role-choice[aria-pressed="true"] { border-color: #4ade80; }
    html.module-dark-theme [data-administration-modal] .role-choice[aria-pressed="true"] { background: rgba(74, 222, 128, .12) !important; }
    html.module-dark-theme [data-administration-modal] .role-choice .role-choice-count { color: #60a5fa !important; }

    [data-administration-modal] .role-access-group {
        margin: 0 0 20px; padding: 0 20px 15px; border: 1px solid #d4d4d8; border-radius: 12px; min-width: 0;
    }
    [data-administration-modal] .role-access-group:last-child { margin-bottom: 0; }
    [data-administration-modal] .role-access-group legend { padding: 0 8px; font-size: 15px; line-height: 30px; }
    [data-administration-modal] .role-access-group ul { display: grid; gap: 15px; margin: 0; padding: 0; list-style: none; }
    [data-administration-modal] .role-access-row { display: flex; align-items: center; justify-content: space-between; gap: 20px; }
    [data-administration-modal] .role-access-row p { margin: 0; font-size: 15px; line-height: 30px; color: #18181b; }
    [data-administration-modal] .role-access-row .role-access-description { font-size: 14px; }
    [data-administration-modal] .role-access-state { flex: none; font-size: 15px; line-height: 30px; color: #18181b; }
    [data-administration-modal] .role-access-row.is-included .role-access-state { color: #15803d; }
    [data-administration-modal] .role-access-empty { margin: 0; font-size: 15px; line-height: 30px; color: #18181b; }
    html.module-dark-theme [data-administration-modal] .role-access-group { border-color: #27272a; }
    html.module-dark-theme [data-administration-modal] .role-access-row p,
    html.module-dark-theme [data-administration-modal] .role-access-state,
    html.module-dark-theme [data-administration-modal] .role-access-empty { color: #ffffff !important; }
    html.module-dark-theme body [data-administration-modal] .role-access-row.is-included .role-access-state { color: #4ade80 !important; }

    [data-administration-modal] .role-user-box {
        height: 256px; overflow-y: auto; border: 1px solid #d4d4d8; border-radius: 12px;
        scrollbar-width: thin; scrollbar-color: #1A3A6B transparent;
    }
    [data-administration-modal] .role-user-box::-webkit-scrollbar { width: 4px; }
    [data-administration-modal] .role-user-box::-webkit-scrollbar-thumb { background: #1A3A6B; border-radius: 999px; }
    [data-administration-modal] .role-user-box::-webkit-scrollbar-track { background: transparent; }
    [data-administration-modal] .role-user-row {
        display: flex; align-items: center; justify-content: space-between; gap: 15px;
        padding: 15px 20px; font-size: 15px; line-height: 30px; color: #18181b; cursor: pointer;
    }
    [data-administration-modal] .role-user-row.is-check { justify-content: flex-start; }
    [data-administration-modal] .role-user-row:hover { background: rgba(21, 128, 61, .08); }
    [data-administration-modal] .role-user-row span { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    [data-administration-modal] .role-user-empty { margin: 0; padding: 15px 20px; font-size: 15px; line-height: 30px; color: #18181b; }
    html.module-dark-theme [data-administration-modal] .role-user-box { border-color: #27272a; scrollbar-color: #1A3A6B transparent; }
    html.module-dark-theme [data-administration-modal] .role-user-row,
    html.module-dark-theme [data-administration-modal] .role-user-empty { color: #ffffff !important; }
    html.module-dark-theme [data-administration-modal] .role-user-row:hover { background: rgba(74, 222, 128, .08); }
    [data-administration-modal] .role-notice {
        margin: 0; padding: 15px 20px; border: 1px solid #15803d; border-radius: 12px; font-size: 15px; line-height: 30px;
    }
    [data-administration-modal] .role-notice.is-error, [data-administration-modal] .role-notice.is-warning { border-color: #dc2626; }
</style>
@endonce
