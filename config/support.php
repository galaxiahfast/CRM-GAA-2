<?php

return [
    'timezone' => env('SUPPORT_TIMEZONE', 'America/Mexico_City'),
    'automated_user' => [
        'name' => 'Sofia',
        'last_name' => 'Soporte (bot)',
        'email' => env('SUPPORT_AUTOMATED_USER_EMAIL', 'sofia.soporte@sistema.local'),
        'avatar' => env('SUPPORT_AUTOMATED_USER_AVATAR', 'img/support/sofia-avatar.svg'),
    ],
    'always_online_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('SUPPORT_ALWAYS_ONLINE_EMAILS', 'administrador@datamid.com.mx'))
    ))),
    'help_recipient_email' => env('SUPPORT_HELP_RECIPIENT_EMAIL', 'administrador@datamid.com.mx'),

    // El orden de las categorías sigue los apartados del menú lateral.
    'questions' => [
        'general' => [
            'label' => 'General',
            'icon' => 'inicio',
            'items' => [
                'navegar' => ['question' => '¿Cómo navego por la aplicación?', 'answer' => 'Utiliza el menú lateral para entrar a cada módulo. Los apartados con una flecha contienen submenús y puedes contraer el menú para ganar espacio.'],
                'inicio' => ['question' => '¿Qué información encuentro en Inicio?', 'answer' => 'Abre General > Inicio para consultar el acceso principal a la aplicación y los elementos disponibles para tu cuenta.'],
                'panel_general' => ['question' => '¿Para qué sirve el Panel General?', 'answer' => 'En General > Panel General puedes consultar el resumen disponible de la operación. La información mostrada depende de tu rol y de los permisos asignados.'],
                'conciliacion_ocr' => ['question' => '¿Dónde utilizo la conciliación OCR?', 'answer' => 'Entra a General > Conciliación Bancaria > Conciliación OCR. Carga o selecciona los archivos solicitados y revisa la información detectada antes de continuar.'],
                'conciliacion_bancaria' => ['question' => '¿Cómo realizo una conciliación bancaria?', 'answer' => 'Ve a General > Conciliación Bancaria > Conciliación Bancaria. Selecciona el periodo y los filtros necesarios, compara los movimientos y confirma únicamente las coincidencias revisadas.'],
                'buscar_menu' => ['question' => '¿Cómo encuentro rápidamente una opción?', 'answer' => 'Utiliza el buscador de la parte superior del menú lateral. Escribe el nombre del módulo o apartado y selecciona el resultado para abrirlo directamente.'],
            ],
        ],
        'informacion' => [
            'label' => 'Información',
            'icon' => 'administracion',
            'items' => [
                'usuarios' => ['question' => '¿Cómo creo o edito un usuario?', 'answer' => 'Entra a Información > Administración > Usuarios. Utiliza Agregar para crear un registro o las acciones de la fila para editarlo o eliminarlo, según tus permisos.'],
                'roles' => ['question' => '¿Cómo asigno o retiro roles?', 'answer' => 'En Administración abre Roles. Selecciona el rol y administra las personas asignadas y disponibles; así puedes cambiar asignaciones antes de eliminar un rol.'],
                'estructura' => ['question' => '¿Dónde administro puestos y áreas?', 'answer' => 'En Administración selecciona Puestos o Áreas. Desde el encabezado puedes agregar registros y desde cada fila puedes editar o eliminar los existentes.'],
                'permisos' => ['question' => '¿Por qué no puedo ver una opción?', 'answer' => 'La visibilidad de módulos y acciones depende del rol y de los permisos asignados. Solicita al administrador que revise tu perfil si necesitas acceso adicional.'],
                'organigrama' => ['question' => '¿Cómo utilizo el organigrama?', 'answer' => 'En Administración abre Organigrama. Puedes buscar colaboradores, filtrar por área, desplazar el lienzo y usar el control de acercamiento para consultar la estructura.'],
                'clientes' => ['question' => '¿Cómo registro o encuentro un cliente?', 'answer' => 'Entra a Información > Clientes. Utiliza Agregar para crear un registro y el buscador o los filtros para localizar y consultar clientes existentes.'],
            ],
        ],
        'actividades' => [
            'label' => 'Actividades',
            'icon' => 'reloj',
            'items' => [
                'registrar_actividad' => ['question' => '¿Cómo registro una actividad con el cronómetro?', 'answer' => 'Ve a Actividades > Control de Horas > Cronómetro, selecciona el cliente y la actividad, completa la información solicitada e inicia el registro. Finalízalo cuando termines.'],
                'reloj_checador' => ['question' => '¿Cómo consulto mis marcas del reloj checador?', 'answer' => 'Entra a Actividades > Control de Horas > Reloj Checador, selecciona el rango de fechas y presiona “Revisar Horas” para consultar marcas, tiempo neto y opciones de exportación.'],
                'panel_horas' => ['question' => '¿Dónde consulto el panel de control de horas?', 'answer' => 'Abre Actividades > Control de Horas > Panel de Control. Aplica los filtros disponibles para consultar la información correspondiente a tu acceso.'],
                'supervision_horas' => ['question' => '¿Cómo superviso o corrijo horas?', 'answer' => 'Si tienes permisos administrativos, entra a Supervisión de Horas, localiza el registro y realiza el ajuste indicando el motivo para conservar la trazabilidad.'],
                'productividad' => ['question' => '¿Dónde consulto la productividad?', 'answer' => 'En Control de Horas abre Productividad y utiliza el periodo y los filtros disponibles. Los resultados dependen de las actividades registradas y de tus permisos.'],
                'respaldos' => ['question' => '¿Cómo compruebo los respaldos?', 'answer' => 'Entra a Actividades > Gestión de Respaldos. Selecciona la ubicación, la carpeta y las empresas; después utiliza los estados para revisar respaldos completos, pendientes o incompletos.'],
            ],
        ],
        'documentos' => [
            'label' => 'Documentos',
            'icon' => 'documento',
            'items' => [
                'hoja_entrega' => ['question' => '¿Cómo genero una hoja de entrega?', 'answer' => 'Entra a Documentos > Hoja de Entrega, completa los datos solicitados y revisa la información antes de generar el documento.'],
                'reporte_soporte' => ['question' => '¿Cómo genero un reporte de soporte?', 'answer' => 'Ve a Documentos > Reportes > Reportes de Soporte. Indica el departamento o cliente, selecciona los servicios, captura cantidades y agrega las personas o equipos atendidos.'],
                'historial_soporte' => ['question' => '¿Cómo busco o descargo reportes de soporte?', 'answer' => 'En Reportes de Soporte abre Historial. Puedes buscar por folio o servicio, filtrar por departamento y persona, y abrir o descargar uno o varios documentos.'],
                'papelera_soporte' => ['question' => '¿Cómo recupero un reporte eliminado?', 'answer' => 'Abre la papelera del historial, localiza el documento y utiliza Restaurar. La eliminación definitiva solo está disponible con los permisos correspondientes.'],
                'reporte_respaldos' => ['question' => '¿Dónde genero el reporte de respaldos?', 'answer' => 'Entra a Documentos > Reportes > Reporte de Respaldos. Selecciona la información solicitada, revisa los resultados y genera o descarga el documento.'],
                'descarga_grupal' => ['question' => '¿Cómo descargo varios reportes juntos?', 'answer' => 'En el historial selecciona las filas deseadas con sus casillas y utiliza Descargar selección. Los filtros te ayudan a reunir primero los documentos que necesitas.'],
            ],
        ],
        'soporte' => [
            'label' => 'Soporte',
            'icon' => 'herramientas',
            'items' => [
                'chat' => ['question' => '¿Cómo utilizo el Chat de Soporte?', 'answer' => 'Entra a Soporte > Centro de Ayuda > Chat de Soporte. Escribe tu mensaje y envíalo para conversar con los colaboradores disponibles.'],
                'menciones' => ['question' => '¿Cómo menciono a una persona en el chat?', 'answer' => 'Escribe @ seguido del nombre y selecciona a la persona en las sugerencias. La mención permite identificar al destinatario y generar el aviso correspondiente.'],
                'archivos_chat' => ['question' => '¿Cómo adjunto un archivo o imagen?', 'answer' => 'En el Chat de Soporte utiliza la opción de adjuntar, elige un archivo permitido y revisa la vista previa antes de enviar el mensaje.'],
                'preguntas' => ['question' => '¿Cómo funciona el Asistente de preguntas?', 'answer' => 'Selecciona una categoría y después una pregunta. Sofia mostrará una respuesta temporal; la conversación se limpia automáticamente cuando sales de esta página.'],
                'notificaciones' => ['question' => '¿Cómo reviso mis notificaciones?', 'answer' => 'Selecciona la campana del encabezado para abrir las notificaciones. Desde ahí puedes consultar los avisos y usar las acciones disponibles para administrarlos.'],
                'perfil' => ['question' => '¿Cómo actualizo mi perfil o contraseña?', 'answer' => 'Abre el menú de tu usuario y selecciona Perfil. Desde ahí puedes actualizar los datos y opciones de seguridad disponibles para tu cuenta.'],
            ],
        ],
    ],
];
