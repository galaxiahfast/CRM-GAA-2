<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Catálogo inicial de permisos propios de la aplicación
    |--------------------------------------------------------------------------
    |
    | Estas claves no sustituyen los Gate ni middleware históricos. Cada módulo
    | puede adoptarlas gradualmente con @rolePermission y access.permission.
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
        ],
        [
            'key' => 'administration.users.manage',
            'name' => 'Gestión de usuarios',
            'module' => 'Administración',
            'description' => 'Crea y actualiza colaboradores y sus perfiles.',
            'sort_order' => 20,
            'roles' => ['Administrador'],
        ],
        [
            'key' => 'administration.roles.manage',
            'name' => 'Gestión de roles',
            'module' => 'Administración',
            'description' => 'Crea y modifica roles de la plataforma.',
            'sort_order' => 30,
            'roles' => ['Administrador'],
        ],
        [
            'key' => 'administration.permissions.manage',
            'name' => 'Gestión de permisos',
            'module' => 'Administración',
            'description' => 'Asigna permisos individuales a los roles.',
            'sort_order' => 40,
            'roles' => ['Administrador'],
        ],
        [
            'key' => 'time-control.supervision.view',
            'name' => 'Supervisión de horas',
            'module' => 'Control de Horas',
            'description' => 'Consulta informes y métricas de colaboradores.',
            'sort_order' => 50,
            'roles' => ['Administrador'],
        ],
        [
            'key' => 'activities.manage',
            'name' => 'Registro de actividades',
            'module' => 'Actividades',
            'description' => 'Registra y consulta actividades operativas.',
            'sort_order' => 60,
            'roles' => ['Administrador', 'Auxiliar'],
        ],
        [
            'key' => 'time-control.clock.use',
            'name' => 'Reloj checador',
            'module' => 'Control de Horas',
            'description' => 'Consulta las marcas biométricas habilitadas.',
            'sort_order' => 70,
            'roles' => ['Administrador', 'Auxiliar'],
        ],
        [
            'key' => 'time-control.productivity.view',
            'name' => 'Consulta de productividad',
            'module' => 'Control de Horas',
            'description' => 'Consulta métricas personales de productividad.',
            'sort_order' => 80,
            'roles' => ['Administrador', 'Auxiliar'],
        ],
    ],
];
