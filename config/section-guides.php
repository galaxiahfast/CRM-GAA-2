<?php

return [
    'inicio' => [
        'label' => 'Inicio',
        'menu' => 'General › Inicio',
        'summary' => 'Inicio muestra los accesos disponibles según los permisos de la cuenta, organizados en General, Información, Actividades, Documentos y Soporte. También muestra la fecha, la hora y el estado de los usuarios en línea.',
        'items' => [
            ['question' => '¿Qué encuentro en Inicio?', 'answer' => 'La pantalla muestra accesos por sección a las herramientas que puedes usar.', 'steps' => ['Inicia sesión y abre Inicio.', 'Busca la sección General, Información, Actividades, Documentos o Soporte.', 'Selecciona la tarjeta del apartado al que quieres entrar.']],
            ['question' => '¿Por qué algunas tarjetas no aparecen?', 'answer' => 'Los accesos se muestran de acuerdo con los permisos de tu cuenta.', 'steps' => ['Revisa los apartados visibles en Inicio y en el menú lateral.', 'Si falta una herramienta que necesitas para tu trabajo, solicita al administrador que revise tus permisos.', 'Después de un cambio de permisos, vuelve a cargar la página.']],
            ['question' => '¿Cómo consulto quién está en línea?', 'answer' => 'El bloque En línea ahora muestra a los usuarios conectados.', 'steps' => ['Localiza En línea ahora en el costado derecho de Inicio.', 'Revisa la lista de personas conectadas.', 'En una pantalla estrecha, desplázate hacia abajo para encontrar el bloque.']],
            ['question' => '¿Dónde consulto la fecha y la hora?', 'answer' => 'La cabecera de Inicio muestra el rol, la fecha y un reloj actualizado.', 'steps' => ['Abre Inicio.', 'Mira la parte derecha de la cabecera para ver tu rol y la fecha.', 'Consulta el reloj situado debajo de la fecha.']],
        ],
    ],
    'detalle_cliente' => [
        'label' => 'Detalle del cliente',
        'menu' => 'Inicio › Panel general › Detalle del cliente',
        'summary' => 'Consulta los datos de contacto, los contadores asignados y el avance del cliente. En Documentos del cliente puedes revisar los servicios contratados y sus archivos del periodo.',
        'items' => [
            ['question' => '¿Qué información muestra esta pantalla?', 'answer' => 'Muestra los datos de contacto, contadores asignados y el avance de formatos del cliente en el periodo elegido.', 'steps' => ['Abre el cliente desde Panel general.', 'Consulta Información del cliente.', 'Revisa el porcentaje de avance del periodo.']],
            ['question' => '¿Cómo cambio el periodo?', 'answer' => 'Usa los selectores de Mes y Año en la cabecera para consultar los documentos de otro periodo.', 'steps' => ['Selecciona el mes que necesitas.', 'Selecciona el año.', 'Revisa el avance y los documentos actualizados.']],
            ['question' => '¿Cómo consulto los documentos?', 'answer' => 'En Documentos del cliente selecciona un servicio contratado para ver sus apartados y archivos.', 'steps' => ['Desplázate a Documentos del cliente.', 'Selecciona el servicio que quieres revisar.', 'Consulta los formatos y archivos disponibles del periodo.']],
            ['question' => '¿Cómo agrego o reviso un PDF?', 'answer' => 'En el apartado del formato puedes cargar el acuse o comprobante y revisar las acciones disponibles sobre un archivo existente.', 'steps' => ['Selecciona el servicio y apartado correctos.', 'Elige el recuadro del acuse o comprobante y selecciona el PDF.', 'Para revisar un archivo cargado, abre su menú de acciones.']],
        ],
    ],
    'panel_control_horas' => [
        'label' => 'Panel de Control de Horas',
        'menu' => 'Monitoreo › Control de Actividades › Panel de Control',
        'summary' => 'Consulta las horas y la productividad de un colaborador dentro de un periodo, con gráficas por día, cliente y actividad.',
        'items' => [
            ['question' => '¿Cómo consulto un periodo?', 'answer' => 'Elige las fechas y presiona Buscar. Si tienes acceso administrativo, selecciona primero al colaborador.', 'steps' => ['Abre Panel de Control en Control de Horas.', 'Si aparece el filtro Colaborador, busca por ID, nombre o correo y selecciona a la persona.', 'Define Fecha inicial y Fecha final.', 'Presiona Buscar para actualizar las cifras y las gráficas.']],
            ['question' => '¿Qué muestra el resumen?', 'answer' => 'El resumen reúne horas, promedios y la distribución general del periodo consultado.', 'steps' => ['Realiza la búsqueda del periodo.', 'Selecciona Abrir informe en la tarjeta Resumen.', 'Revisa los totales y vuelve a las gráficas cuando termines.']],
            ['question' => '¿Cómo reviso las gráficas?', 'answer' => 'Las gráficas muestran el tiempo por día y su distribución por cliente y actividad.', 'steps' => ['Después de Buscar, recorre las gráficas con los controles anterior y siguiente.', 'Consulta el título y las etiquetas de cada gráfica para identificar el desglose.', 'Si cambias el periodo o colaborador, presiona Buscar otra vez.']],
            ['question' => '¿Cómo exporto o imprimo el resultado?', 'answer' => 'La barra superior tiene Descargar PDF e Imprimir para el resultado visible.', 'steps' => ['Selecciona el colaborador y periodo que necesitas.', 'Presiona Buscar y confirma que el resultado corresponda a la consulta.', 'Usa Descargar PDF para guardar una copia o Imprimir para enviarla a la impresora.']],
        ],
    ],
    'supervision_horas' => [
        'label' => 'Supervisión de Horas',
        'menu' => 'Monitoreo › Control de Horas › Supervisión de Horas',
        'summary' => 'Permite seleccionar colaboradores, preparar un informe de horas, revisar sus actividades y exportar resultados individuales o consolidados.',
        'items' => [
            ['question' => '¿Cómo preparo un informe?', 'answer' => 'En Informe general selecciona los colaboradores y el periodo que deseas revisar.', 'steps' => ['Abre Supervisión de Horas y permanece en Informe general.', 'Selecciona a uno o varios colaboradores.', 'Define el periodo y genera el reporte para consultar los resultados.']],
            ['question' => '¿Dónde veo el resultado?', 'answer' => 'La sección Resultado del reporte presenta el consolidado y permite elegir una persona para ver su detalle.', 'steps' => ['Genera el informe.', 'Abre Resultado del reporte.', 'Selecciona un colaborador de la lista para revisar sus actividades y tiempos.']],
            ['question' => '¿Cómo consulto la actividad en línea?', 'answer' => 'Actividad en línea abre el seguimiento de los registros activos.', 'steps' => ['Desde la cabecera selecciona Actividad en línea.', 'Revisa los colaboradores y actividades que aparezcan.', 'Regresa a Informe general para preparar otro reporte.']],
            ['question' => '¿Qué opciones de exportación hay?', 'answer' => 'Puedes descargar un reporte individual, uno grupal o el consolidado general cuando el informe está actualizado.', 'steps' => ['Genera un informe con los colaboradores necesarios.', 'En Resultado del reporte elige Reporte individual, grupal o general.', 'También puedes usar Descargar PDF en la cabecera o Imprimir.']],
        ],
    ],
    'cronometro' => [
        'label' => 'Cronómetro',
        'menu' => 'Monitoreo › Control de Actividades › Cronómetro',
        'summary' => 'Registra el tiempo que dedicas a cada cliente o departamento y a cada actividad durante el día. Solo puede haber una actividad en progreso: al iniciar otra, la anterior se pausa automáticamente.',
        'items' => [
            ['question' => '¿Cómo inicio una actividad?', 'answer' => 'Elige el departamento o cliente y la actividad, y presiona Iniciar actividad.', 'steps' => ['En Nuevo registro, abre la lista de Cliente y selecciona el departamento o cliente.', 'Abre la lista de Actividad y selecciona la actividad.', 'Presiona Iniciar actividad: la fila aparece en la tabla marcada en verde y el tiempo comienza a contar.']],
            ['question' => '¿Cómo pauso o reanudo una actividad?', 'answer' => 'Cada fila tiene un icono de pausa o de reanudar en la columna Acción.', 'steps' => ['Localiza la fila de la actividad en la tabla.', 'Presiona el icono de pausa para detener el tiempo, o el de reanudar para continuar.', 'Si reanudas una actividad mientras otra está en progreso, la otra se pausa sola.']],
            ['question' => '¿Cómo cambio el orden de las actividades?', 'answer' => 'Arrastra la fila con el clic apretado hasta la posición que quieras.', 'steps' => ['Mantén presionado el clic sobre la fila, fuera de los iconos.', 'Desplaza el cursor hacia arriba o hacia abajo.', 'Suelta el clic: el orden se guarda y la columna N° se actualiza.']],
            ['question' => '¿Cómo elimino una actividad?', 'answer' => 'Usa la X de la fila y escribe el nombre exacto de la actividad para confirmar.', 'steps' => ['Si la actividad está en progreso, primero páusala.', 'Presiona la X de su fila.', 'Escribe el nombre de la actividad tal como aparece y confirma. No se pueden eliminar actividades cerradas automáticamente ni corregidas por un administrador.']],
            ['question' => '¿Qué significan los estados?', 'answer' => 'La columna Estado indica si la actividad está en progreso, pausada o cerrada.', 'steps' => ['En progreso: la fila va en verde y el tiempo sigue corriendo.', 'Pausada: el tiempo está detenido y puedes reanudarla.', 'Cerrada: el sistema la cerró automáticamente y ya no se puede reanudar.']],
        ],
    ],
    'productividad' => [
        'label' => 'Mi productividad',
        'menu' => 'Monitoreo › Control de Actividades › Productividad',
        'summary' => 'Consulta cuánto tiempo dedicaste a cada cliente y a cada actividad en el periodo que elijas, revisa el detalle de cada día y exporta la información.',
        'items' => [
            ['question' => '¿Cómo cambio el periodo?', 'answer' => 'En Periodo de consulta elige las fechas Desde y Hasta y presiona Consultar periodo.', 'steps' => ['Selecciona la fecha Desde y la fecha Hasta.', 'Presiona Consultar periodo.', 'El encabezado muestra el rango consultado y las listas se actualizan.']],
            ['question' => '¿Qué muestran las distribuciones?', 'answer' => 'Distribución por cliente y por actividad indican el tiempo, la barra y el porcentaje que representa cada uno del total del periodo.', 'steps' => ['Revisa la columna de tiempo de cada fila.', 'La barra y el porcentaje muestran cuánto pesa frente al total.', 'Desplázate con la rueda del ratón: cada giro mueve una fila y siempre se ven siete.']],
            ['question' => '¿Cómo cambio el orden de las filas?', 'answer' => 'Arrastra una fila con el clic apretado hasta la posición que quieras; el orden es solo visual.', 'steps' => ['Mantén presionado el clic sobre una fila de las distribuciones o del detalle.', 'Muévela hacia arriba o hacia abajo.', 'Suelta el clic: la columna N° se actualiza. Al consultar otro periodo se restablece el orden original.']],
            ['question' => '¿Dónde veo el detalle de una actividad?', 'answer' => 'Detalle de actividades por día muestra cada actividad con todos sus datos en la misma fila.', 'steps' => ['Busca el día y la actividad en el detalle.', 'En la fila verás el horario, la actividad y el cliente.', 'Pasa el cursor sobre el horario para ver el tiempo que tomó la actividad.', 'El detalle tiene su propio periodo, independiente del resumen de arriba.']],
            ['question' => '¿Cómo exporto la información?', 'answer' => 'Usa CSV, TXT o PDF en el encabezado del primer bloque.', 'steps' => ['Elige el periodo y presiona Consultar periodo.', 'En el encabezado selecciona el formato: CSV, TXT o PDF.', 'El archivo se descarga con el periodo consultado.']],
        ],
    ],
    'productividad_detalle' => [
        'label' => 'Detalle de actividades por día',
        'menu' => 'Monitoreo › Control de Actividades › Productividad › Detalle de actividades por día',
        'summary' => 'Consulta el registro completo de tus actividades día por día en el periodo que elijas, con su horario, cliente, tiempo y datos adicionales. Su periodo es independiente del bloque de arriba.',
        'items' => [
            ['question' => '¿Cómo cambio el periodo del detalle?', 'answer' => 'Este bloque tiene sus propias fechas: no se modifican con las del resumen de arriba.', 'steps' => ['En Periodo de consulta del detalle elige Desde y Hasta.', 'Presiona Consultar periodo.', 'El encabezado muestra el rango y la lista se actualiza.']],
            ['question' => '¿Dónde veo los datos de una actividad?', 'answer' => 'Cada fila del detalle muestra su horario, actividad y cliente, sin abrir nada.', 'steps' => ['Busca el día y la actividad.', 'En la fila verás el horario, la actividad y el cliente.', 'Pasa el cursor sobre el horario para ver el tiempo que tomó la actividad.', 'Si un nombre no cabe, pasa el cursor sobre él para verlo completo.']],
            ['question' => '¿Cómo cambio el orden de las filas?', 'answer' => 'Arrastra una fila dentro de su día; el orden es solo visual.', 'steps' => ['Mantén presionado el clic sobre una fila.', 'Muévela hacia arriba o hacia abajo.', 'Suelta el clic. Al consultar otro periodo se restablece el orden.']],
            ['question' => '¿Cómo descargo el reporte?', 'answer' => 'Usa Descargar reporte PDF en el encabezado del detalle.', 'steps' => ['Elige el periodo y presiona Consultar periodo.', 'Presiona Descargar reporte PDF.', 'El archivo se descarga con el periodo del detalle.']],
        ],
    ],
];
