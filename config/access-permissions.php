<?php

return [
    'profiles' => [
        'administrator' => [
            'label' => 'Administrador',
            'description' => 'Acceso completo a la administraciÃ³n y operaciÃ³n del sistema.',
        ],
        'auxiliary' => [
            'label' => 'Auxiliar',
            'description' => 'Acceso operativo a actividades, reloj y productividad personal.',
        ],
        'custom' => [
            'label' => 'Personalizado',
            'description' => 'Permite seleccionar individualmente los apartados disponibles.',
        ],
    ],
    /*
    |--------------------------------------------------------------------------
    | Catálogo inicial de permisos propios de la aplicación
    |--------------------------------------------------------------------------
    |
    | Estas claves son contratos estables entre el catálogo, el menú y las
    | rutas protegidas mediante @rolePermission y access.permission.
    |
    */
    'catalog' => [
        [
            'key' => 'administration.organization.manage',
            'name' => 'Administración y organigrama',
            'module' => 'Administración',
            'description' => 'Consulta y administra la estructura organizacional.',
            'sort_order' => 10,
            'roles' => ['Administrador'],
            'profiles' => ['administrator'],
        ],
        [
            'key' => 'administration.users.manage',
            'name' => 'Gestión de usuarios',
            'module' => 'Administración',
            'description' => 'Crea y actualiza colaboradores y sus perfiles.',
            'sort_order' => 20,
            'roles' => ['Administrador', 'Coordinador', 'Contador'],
            'profiles' => ['administrator'],
        ],
        [
            'key' => 'administration.roles.manage',
            'name' => 'Gestión de roles',
            'module' => 'Administración',
            'description' => 'Crea y modifica roles de la plataforma.',
            'sort_order' => 30,
            'roles' => ['Administrador'],
            'profiles' => ['administrator'],
        ],
        [
            'key' => 'administration.permissions.manage',
            'name' => 'Gestión de permisos',
            'module' => 'Administración',
            'description' => 'Asigna permisos individuales a los roles.',
            'sort_order' => 40,
            'roles' => ['Administrador'],
            'profiles' => ['administrator'],
        ],
        [
            'key' => 'administration.assignments.manage',
            'name' => 'Asignación de colaboradores',
            'module' => 'Administración',
            'description' => 'Gestiona relaciones operativas entre colaboradores y clientes.',
            'sort_order' => 45,
            'roles' => ['Administrador', 'Coordinador', 'Contador', 'Auxiliar'],
            'profiles' => ['administrator', 'auxiliary'],
        ],
        [
            'key' => 'time-control.supervision.view',
            'name' => 'Supervisión de horas',
            'module' => 'Control de Horas',
            'description' => 'Consulta informes y métricas de colaboradores.',
            'sort_order' => 50,
            'roles' => ['Administrador'],
            'profiles' => ['administrator'],
        ],
        [
            'key' => 'customers.view',
            'name' => 'Consulta de clientes',
            'module' => 'Clientes',
            'description' => 'Consulta los clientes disponibles para el usuario.',
            'sort_order' => 52,
            'roles' => ['Administrador', 'Coordinador', 'Contador', 'Auxiliar'],
            'profiles' => ['administrator', 'auxiliary'],
        ],
        [
            'key' => 'customers.manage',
            'name' => 'Gestión de clientes',
            'module' => 'Clientes',
            'description' => 'Crea y modifica clientes.',
            'sort_order' => 54,
            'roles' => ['Administrador', 'Coordinador'],
            'profiles' => ['administrator'],
        ],
        [
            'key' => 'activities.manage',
            'name' => 'Registro de actividades',
            'module' => 'Actividades',
            'description' => 'Registra y consulta actividades operativas.',
            'sort_order' => 60,
            'roles' => ['Administrador', 'Coordinador', 'Contador', 'Auxiliar'],
            'profiles' => ['administrator', 'auxiliary'],
        ],
        [
            'key' => 'time-control.clock.use',
            'name' => 'Reloj checador',
            'module' => 'Control de Horas',
            'description' => 'Consulta las marcas biométricas habilitadas.',
            'sort_order' => 70,
            'roles' => ['Administrador', 'Coordinador', 'Contador', 'Auxiliar'],
            'profiles' => ['administrator', 'auxiliary'],
        ],
        [
            'key' => 'time-control.productivity.view',
            'name' => 'Consulta de productividad',
            'module' => 'Control de Horas',
            'description' => 'Consulta métricas personales de productividad.',
            'sort_order' => 80,
            'roles' => ['Administrador', 'Coordinador', 'Contador', 'Auxiliar'],
            'profiles' => ['administrator', 'auxiliary'],
        ],
    ],
];
