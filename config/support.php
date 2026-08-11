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
