<script>
    // Zoom automático según el tamaño de la ventana, igual en el login y dentro de la app, para que todo se vea igual
    // en cualquier pantalla: el diseño se toma como un lienzo de 1800x950 y, si la ventana es más pequeña (en ancho o
    // en alto), todo se reduce de forma proporcional. Nunca baja del 85% (las vistas ya se ven al 90%, así que el contenido queda siempre por encima del ~76%): por debajo de ese punto ya no se encoge más y
    // aparece el scroll (horizontal y vertical). En celular (menos de 640px) no hay zoom de lienzo.
    window.appAutoScale = function () {
        var width = window.innerWidth;
        var height = window.innerHeight;

        return width < 640 ? 1 : Math.min(1, Math.max(0.85, Math.min(width / 1800, height / 950)));
    };

    // El contenido de las vistas (dentro de #app-zoom) se escala con este mismo factor mediante la variable CSS --app-zoom.
    window.appApplyZoom = function () {
        document.documentElement.style.setProperty('--app-zoom', String(window.appAutoScale() * (window.appContentBase || 1)));
    };
    window.appApplyZoom();
    window.addEventListener('resize', window.appApplyZoom, { passive: true });
</script>
