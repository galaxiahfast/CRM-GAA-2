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

    'questions' => [
        'general' => [
            'label' => 'Primeros pasos',
            'icon' => 'inicio',
            'items' => [
                'navegar' => [
                    'question' => '¿Cómo navego por la aplicación?',
                    'answer' => 'Utiliza el menú lateral para entrar a cada módulo. Los apartados con una flecha contienen submenús. Puedes contraer el menú para ganar espacio y volver a desplegarlo cuando lo necesites.',
                ],
                'perfil' => [
                    'question' => '¿Cómo actualizo mi perfil?',
                    'answer' => 'Abre el menú de tu usuario en la esquina superior derecha y selecciona Perfil. Desde ahí puedes actualizar tus datos personales y las opciones de seguridad disponibles para tu cuenta.',
                ],
                'contrasena' => [
                    'question' => '¿Qué hago si olvidé mi contraseña?',
                    'answer' => 'En la pantalla de inicio de sesión selecciona “¿Olvidaste tu contraseña?”, captura tu correo registrado y sigue el enlace que recibirás. Si no llega, revisa correo no deseado o solicita apoyo al administrador.',
                ],
            ],
        ],
        'clientes' => [
            'label' => 'Clientes',
            'icon' => 'clientes',
            'items' => [
                'crear_cliente' => [
                    'question' => '¿Cómo registro un cliente?',
                    'answer' => 'Entra a Clientes desde el menú lateral y utiliza la opción para crear un registro. Completa los datos obligatorios y guarda. El sistema te indicará si falta información o si algún dato requiere corrección.',
                ],
                'buscar_cliente' => [
                    'question' => '¿Cómo encuentro un cliente?',
                    'answer' => 'Dentro de Clientes utiliza el buscador y los filtros disponibles. Puedes buscar por nombre o información identificativa y abrir la tarjeta correspondiente para consultar su detalle.',
                ],
                'reportes_cliente' => [
                    'question' => '¿Dónde consulto el reporte de un cliente?',
                    'answer' => 'Abre el cliente y entra a su sección de reporte. La información visible dependerá de tu rol y de los permisos asignados a tu cuenta.',
                ],
            ],
        ],
        'horas' => [
            'label' => 'Control de horas',
            'icon' => 'reloj',
            'items' => [
                'registrar_actividad' => [
                    'question' => '¿Cómo registro una actividad?',
                    'answer' => 'Ve a Actividades > Control de Horas, selecciona el cliente y la actividad correspondiente, completa la información solicitada e inicia el registro. Finaliza el temporizador cuando hayas terminado.',
                ],
                'reloj_checador' => [
                    'question' => '¿Cómo consulto mis marcas del reloj checador?',
                    'answer' => 'Entra a Actividades > Control de Horas > Reloj Checador, selecciona el rango de fechas y presiona “Revisar Horas”. Verás marcas, tiempo neto, pagos, gráficas y opciones de exportación.',
                ],
                'corregir_horas' => [
                    'question' => '¿Cómo solicito una corrección de horas?',
                    'answer' => 'Si detectas una marca o actividad incorrecta, informa a tu administrador o supervisor indicando la fecha y el motivo. Los ajustes administrativos requieren un comentario para conservar la trazabilidad.',
                ],
            ],
        ],
        'administracion' => [
            'label' => 'Administración',
            'icon' => 'administracion',
            'items' => [
                'usuarios' => [
                    'question' => '¿Cómo creo o edito un usuario?',
                    'answer' => 'Si cuentas con permisos, entra a Administración y abre el formulario de usuarios. Desde ahí puedes crear, editar o eliminar registros y configurar su información organizacional.',
                ],
                'organigrama' => [
                    'question' => '¿Cómo utilizo el organigrama?',
                    'answer' => 'En Administración puedes buscar colaboradores, filtrar por área, arrastrar el lienzo y usar la rueda para acercar o alejar. Selecciona un nodo para consultar o editar sus datos según tus permisos.',
                ],
                'permisos' => [
                    'question' => '¿Por qué no puedo ver una opción?',
                    'answer' => 'La visibilidad de módulos y acciones depende del rol y de los permisos asignados. Solicita al administrador que revise tu perfil si consideras que necesitas acceso adicional.',
                ],
            ],
        ],
        'conciliacion' => [
            'label' => 'Conciliación bancaria',
            'icon' => 'conciliacion',
            'items' => [
                'conciliacion_ocr' => [
                    'question' => '¿Dónde utilizo la conciliación OCR?',
                    'answer' => 'Entra a General > Conciliación Bancaria > Conciliación OCR. Carga o selecciona los archivos solicitados y revisa la información detectada antes de continuar con la conciliación.',
                ],
                'conciliacion_bancaria' => [
                    'question' => '¿Cómo realizo una conciliación bancaria?',
                    'answer' => 'Ve a General > Conciliación Bancaria > Conciliación Bancaria. Selecciona el periodo y los filtros necesarios, compara los movimientos y confirma únicamente las coincidencias que hayas revisado.',
                ],
            ],
        ],
        'respaldos' => [
            'label' => 'Gestión de respaldos',
            'icon' => 'respaldo',
            'items' => [
                'comprobar_respaldos' => [
                    'question' => '¿Cómo compruebo los respaldos?',
                    'answer' => 'Entra a Actividades > Gestión de Respaldos. Selecciona la ubicación, la carpeta y las empresas que deseas revisar. Después utiliza los estados Todos, Completos, Por respaldar, Ya respaldadas e Incompletos para consultar el resultado.',
                ],
                'historial_respaldos' => [
                    'question' => '¿Dónde consulto respaldos anteriores?',
                    'answer' => 'En Gestión de Respaldos abre la pestaña Historial y selecciona una fecha. La pantalla carga primero los días recientes; desde el listado de fechas puedes consultar periodos anteriores.',
                ],
            ],
        ],
        'documentos' => [
            'label' => 'Documentos y soporte',
            'icon' => 'documento',
            'items' => [
                'hoja_entrega' => [
                    'question' => '¿Cómo genero una hoja de entrega?',
                    'answer' => 'Entra a Documentos > Hoja de Entrega, completa los datos solicitados y revisa la información antes de generar el documento.',
                ],
                'reporte_soporte' => [
                    'question' => '¿Cómo genero un reporte de soporte?',
                    'answer' => 'Ve a Documentos > Reportes > Reportes de Soporte. Indica el departamento o cliente, selecciona los servicios, captura las cantidades y agrega las personas o equipos atendidos. Revisa el resumen antes de generar el PDF.',
                ],
                'historial_soporte' => [
                    'question' => '¿Cómo busco o descargo reportes de soporte?',
                    'answer' => 'En Reportes de Soporte abre Historial. Puedes buscar por folio o servicio, filtrar por departamento y persona, abrir o descargar un reporte y seleccionar varios registros para realizar acciones en grupo.',
                ],
                'papelera_soporte' => [
                    'question' => '¿Cómo recupero un reporte eliminado?',
                    'answer' => 'Abre la papelera del historial de reportes, localiza el documento y utiliza Restaurar. La eliminación definitiva solo está disponible para usuarios con los permisos correspondientes.',
                ],
                'reporte_respaldos' => [
                    'question' => '¿Dónde genero el reporte de respaldos?',
                    'answer' => 'Entra a Documentos > Reportes > Reporte de Respaldos. Selecciona la información solicitada, revisa los resultados y genera o descarga el documento desde las acciones disponibles.',
                ],
            ],
        ],
        'herramientas' => [
            'label' => 'Menú y notificaciones',
            'icon' => 'herramientas',
            'items' => [
                'buscar_menu' => [
                    'question' => '¿Cómo encuentro una opción del menú?',
                    'answer' => 'Utiliza el buscador de la parte superior del menú lateral. Escribe el nombre del módulo o apartado y selecciona el resultado para abrirlo directamente.',
                ],
                'menu_compacto' => [
                    'question' => '¿Cómo contraigo o despliego el menú?',
                    'answer' => 'Presiona el botón del menú lateral para cambiar entre la vista completa y la compacta. En la vista compacta, coloca el puntero sobre un icono para consultar el nombre del apartado.',
                ],
                'notificaciones' => [
                    'question' => '¿Cómo reviso mis notificaciones?',
                    'answer' => 'Selecciona la campana del encabezado para abrir las notificaciones. Desde ahí puedes consultar los avisos disponibles y usar las acciones permitidas para marcarlos o administrarlos.',
                ],
            ],
        ],
        'reportes' => [
            'label' => 'Reportes y exportación',
            'icon' => 'reporte',
            'items' => [
                'exportar' => [
                    'question' => '¿Cómo exporto información?',
                    'answer' => 'Busca los botones CSV, PDF o TXT dentro del reporte. Antes de exportar, aplica el rango de fechas y filtros deseados para que el archivo contenga exactamente la información consultada.',
                ],
                'sin_datos' => [
                    'question' => '¿Por qué un reporte aparece sin datos?',
                    'answer' => 'Verifica el rango de fechas, los filtros seleccionados y tus permisos. También confirma que existan registros durante ese periodo. Si el problema continúa, compártelo en el Chat general de Soporte.',
                ],
            ],
        ],
    ],
];
