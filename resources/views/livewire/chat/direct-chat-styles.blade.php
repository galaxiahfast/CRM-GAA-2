{{-- Estilos del chat «Mensajes». Van fuera del componente para no reenviarlos en cada actualización automática (cada 2 s). --}}
<x-panel-styles />
<style>
    /* Mismo diseño que Inicio: verde para acciones, gris (58 %) para lo secundario, bordes de 18px y sombra pareja alrededor */
    [data-direct-chat] { --dc-green: #15803d; --dc-muted: color-mix(in srgb, var(--panel-text) 58%, transparent); --dc-soft: color-mix(in srgb, var(--panel-accent) 6%, var(--panel-surface)); --panel-muted: var(--panel-text); }
    html.module-dark-theme [data-direct-chat] { --dc-green: #4ade80; --dc-soft: #111116; }
    [data-direct-chat] * { font-weight: 400; }
    [data-direct-chat] p { line-height: 30px; }
    /* Los textos de una sola línea marcados con leading-none miden lo que su letra: así el espacio arriba y abajo es el mismo */
    [data-direct-chat] p.leading-none, [data-direct-chat] span.leading-none { line-height: 1; }
    [data-direct-chat] .dc-card { border: 1px solid var(--panel-border); border-radius: 18px; background: var(--panel-surface); color: var(--panel-text); box-shadow: 0 0 30px rgba(15, 23, 42, .14); }
    html.module-dark-theme [data-direct-chat] .dc-card { box-shadow: 0 0 30px rgba(0, 0, 0, .6); }
    [data-direct-chat] .dc-bar { border-bottom: 0; border-bottom-left-radius: 0; border-bottom-right-radius: 0; }
    [data-direct-chat] .dc-divider { border-color: var(--panel-border); }
    [data-direct-chat] .dc-title { font-size: 19px; font-weight: 600; line-height: 1; letter-spacing: -.01em; color: var(--panel-text); }
    [data-direct-chat] .dc-name { font-size: 15px; font-weight: 600; line-height: 30px; color: var(--panel-text); }
    [data-direct-chat] p.dc-name { line-height: 1; }
    [data-direct-chat] .dc-muted { color: var(--dc-muted); }
    [data-direct-chat] .dc-line { line-height: 30px; }
    [data-direct-chat] .dc-green-text { color: var(--dc-green); }
    [data-direct-chat] .dc-avatar { display: grid; height: 50px; width: 50px; flex-shrink: 0; place-items: center; border-radius: 9999px; border: 1px solid var(--panel-border); object-fit: cover; }
    [data-direct-chat] .dc-initials { background: var(--dc-soft); color: var(--panel-text); }
    [data-direct-chat] .dc-icon-btn { display: grid; height: 40px; width: 40px; place-items: center; border-radius: 12px; color: var(--panel-text); transition: background-color 150ms ease; }
    [data-direct-chat] .dc-icon-btn:hover { background-color: var(--dc-soft); }
    [data-direct-chat] .dc-badge { display: inline-flex; min-width: 24px; height: 24px; flex-shrink: 0; align-items: center; justify-content: center; border-radius: 9999px; padding: 0 8px; background-color: var(--dc-green); color: #ffffff; line-height: 1; }
    /* Botón principal como la etiqueta fuerte de Inicio (la hora): fondo del color del texto */
    [data-direct-chat] .dc-primary { border-radius: 18px; padding: 15px 20px; background: var(--panel-text); color: var(--panel-surface); line-height: 1.3; transition: opacity 150ms ease; }
    [data-direct-chat] .dc-primary:hover { opacity: .85; }
    [data-direct-chat] .dc-chip { border: 1px solid var(--panel-border); border-radius: 18px; padding: 15px 20px; background: var(--panel-surface); color: var(--panel-text); line-height: 1.3; transition: background-color 150ms ease; }
    [data-direct-chat] .dc-chip:hover { background: var(--dc-soft); }
    [data-direct-chat] .dc-field { display: block; height: 50px; border-radius: 18px; border: 1px solid var(--panel-border); background-color: var(--panel-surface); color: var(--panel-text); padding: 0 20px; }
    [data-direct-chat] .dc-field::placeholder { color: var(--dc-muted); opacity: 1; }
    [data-direct-chat] .dc-row, [data-direct-chat] .dc-preview { transition: background-color 150ms ease; }
    [data-direct-chat] .dc-row:hover, [data-direct-chat] .dc-preview:hover { background-color: var(--dc-soft); }
    /* Burbujas: las propias en verde suave, las recibidas en el fondo suave de Inicio */
    [data-direct-chat] .dc-bubble { max-width: 80%; border: 1px solid var(--panel-border); border-radius: 18px; padding: 15px 20px; background: var(--dc-soft); }
    [data-direct-chat] .dc-bubble p { line-height: 25px; }
    [data-direct-chat] .dc-mine { background-color: color-mix(in srgb, var(--dc-green) 10%, var(--panel-surface)); border-color: color-mix(in srgb, var(--dc-green) 45%, var(--panel-border)); }
    [data-direct-chat] .dc-bubble p.dc-time { display: flex; align-items: center; justify-content: flex-end; gap: 5px; margin-top: 5px; line-height: 20px; }
    [data-direct-chat] .dc-typing { background: linear-gradient(to top, var(--panel-surface) 70%, transparent); }
    [data-direct-chat] .dc-status { box-shadow: 0 0 0 2px var(--panel-surface); }
    [data-direct-chat] :is(button, a, input):focus,
    [data-direct-chat] :is(button, a, input):focus-visible { outline: none !important; box-shadow: none !important; }
    [data-direct-chat] .dc-field:focus, [data-direct-chat] .dc-field:focus-visible { border-color: color-mix(in srgb, var(--panel-text) 45%, var(--panel-border)) !important; }
    /* Puntitos de «escribiendo» (los mismos del Chat de soporte) */
    [data-direct-chat] .dc-dot { width: 4px; height: 4px; border-radius: 9999px; background: currentColor; animation: dc-typing 1.2s infinite ease-in-out; }
    [data-direct-chat] .dc-dot:nth-child(2) { animation-delay: 0.18s; }
    [data-direct-chat] .dc-dot:nth-child(3) { animation-delay: 0.36s; }
    @keyframes dc-typing { 0%, 60%, 100% { transform: translateY(0); opacity: 0.35; } 30% { transform: translateY(-5px); opacity: 1; } }
    @media (prefers-reduced-motion: reduce) { [data-direct-chat] .dc-dot { animation-duration: 2.4s; } }
    @media (max-width: 639px) { [data-direct-chat] { right: 10px !important; } }
    @media print { [data-direct-chat] { display: none !important; } }
    /* 20px exactos en todo (igual que Inicio): la caja de cada texto se recorta a sus letras, así el margen o el relleno es la
       distancia que se ve; entre líneas de un mismo párrafo también hay 20px. Los botones llevan 20px por cada lado. */
    [data-direct-chat] :is(p, .dc-name, .dc-line, .dc-title, button, .dc-day) { text-box: trim-both cap alphabetic; }
    [data-direct-chat] :is(p, .dc-name, .dc-line) { line-height: calc(.7em + 20px) !important; }
    [data-direct-chat] .dc-title { line-height: calc(.7em + 20px); }
    [data-direct-chat] .dc-second { margin-top: 20px; }
    /* Lo que se corta con «…» no recorta las letras que sobresalen de la caja (acentos, g, j, y) */
    [data-direct-chat] .truncate { overflow: clip; overflow-clip-margin: 10px; }
    [data-direct-chat] .dc-bubble { padding: 20px; }
    [data-direct-chat] .dc-bubble p { line-height: calc(.7em + 20px) !important; }
    /* La hora, el estado y «escribiendo» son texto normal (no flex) para que su caja también se recorte a las letras;
       los puntos e íconos van en línea con márgenes negativos para no agrandar la línea */
    [data-direct-chat] .dc-bubble p.dc-time { display: block; margin-top: 20px; text-align: right; line-height: 1 !important; }
    [data-direct-chat] .dc-bubble p.dc-time svg { display: inline-block; margin: -6px 0 -6px 5px; vertical-align: middle; }
    [data-direct-chat] .dc-inline-dot { display: inline-block; height: 8px; width: 8px; vertical-align: 1px; }
    [data-direct-chat] .dc-inline-dots { display: inline-flex; gap: 6px; height: 4px; align-items: center; vertical-align: 1px; }
    [data-direct-chat] :is(.dc-primary, .dc-chip) { padding: 20px; line-height: 1; }
    /* Los campos miden lo mismo que los botones: 20px + la altura de la letra (10.5px) + 20px; 20px a los lados */
    [data-direct-chat] .dc-field { height: 50.5px; padding: 0 20px; line-height: 1; }
</style>
