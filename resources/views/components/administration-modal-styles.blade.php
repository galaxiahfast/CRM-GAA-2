@once
<style>
    [data-administration-modal] {
        font-size: 15px;
    }
    [data-administration-modal] :where(p, span, button, input, select, option, label, h1, h2, h3, small) {
        font-size: 15px !important;
    }
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
    [data-administration-modal] section:not([class*="bg-blue-50"]):not(:last-child) {
        border-bottom: 1px solid #d4d4d8 !important;
        padding-bottom: 20px !important;
    }

    /* Botón cerrar (×): siempre relleno de rojo, con la × en blanco. */
    [data-administration-modal] .administration-form-panel header button[aria-label="Cerrar"] {
        border-color: #dc2626 !important;
        background-color: #dc2626 !important;
        color: #ffffff !important;
    }

    [data-administration-modal] .administration-form-panel header button[aria-label="Cerrar"]:hover {
        border-color: #b91c1c !important;
        background-color: #b91c1c !important;
        color: #ffffff !important;
    }

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
        min-height: 44px;
        border: 1px solid #d4d4d8 !important;
        border-radius: 0.75rem !important;
        background: #ffffff !important;
        color: #18181b !important;
        box-shadow: none !important;
    }
    [data-administration-modal] footer button:last-child {
        border-color: #18181b !important;
        background: #18181b !important;
        color: #ffffff !important;
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
</style>
@endonce
