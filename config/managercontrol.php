<?php

/*
|--------------------------------------------------------------------------
| ManagerControl (sistema de escritorio legacy) — acceso ESTRICTAMENTE de solo lectura
|--------------------------------------------------------------------------
| Aquí NUNCA van usuarios ni contraseñas: se leen de .env (MANAGERCONTROL_*).
| La cadena de conexión del archivo ManagerControl.exe.config solo se usa para
| descubrir servidor/instancia/base; su usuario y contraseña se descartan.
*/
return [
    'config_path' => env(
        'MANAGERCONTROL_CONFIG_PATH',
        'C:\\Program Files (x86)\\Operación e Integración Eficiente de México S de RL de CV\\ManagerControl\\ManagerControl.exe.config'
    ),
    'config_connection_name' => env('MANAGERCONTROL_CONFIG_CONNECTION', 'DefaultConnectionString'),

    // Valores de .env (tienen prioridad sobre lo descubierto en el .exe.config).
    'host' => env('MANAGERCONTROL_HOST'),
    'instance' => env('MANAGERCONTROL_INSTANCE'),
    'port' => env('MANAGERCONTROL_PORT'),
    'database' => env('MANAGERCONTROL_DATABASE'),
    // Usuario de la aplicación ManagerControl (informativo; NO es un login de SQL Server).
    'username' => env('MANAGERCONTROL_USERNAME'),
    'password' => env('MANAGERCONTROL_PASSWORD'),
    // Login de SQL Server de SOLO LECTURA. Si se definen, tienen prioridad sobre los dos anteriores.
    'sql_username' => env('MANAGERCONTROL_SQL_USERNAME'),
    'sql_password' => env('MANAGERCONTROL_SQL_PASSWORD'),
    'trust_server_certificate' => (bool) env('MANAGERCONTROL_TRUST_CERT', true),

    'login_timeout' => 5,
    'query_timeout' => 15,
    'max_rows' => 5000,

    // Ejercicio de la app de escritorio (opcional): si se define, se filtra por su columna exercise_id.
    'exercise_id' => env('MANAGERCONTROL_EXERCISE_ID'),

    /*
    | Mapa del esquema. Los nombres por defecto son una INFERENCIA a partir de las clases del
    | legacy (ControlHoras, Empleado, Cliente, Actividad) y deben confirmarse con
    | `php artisan managercontrol:schema`. Solo se admiten identificadores [A-Za-z0-9_].
    */
    'hours_unit' => env('MANAGERCONTROL_HOURS_UNIT', 'hours'), // HoraLaborada es decimal (horas)
    // Esquema CONFIRMADO con `php artisan managercontrol:schema` (SQL Server 2012, ControlTiempos_Contabilidad).
    // 'deleted' = columna bit de baja lógica (se excluyen las filas con 1). 'name' puede ser una lista de columnas.
    'schema' => [
        'hours' => [
            'db_schema' => 'ControlTiempos',
            'table' => 'RegistroDiario',
            'employee_id' => 'IDEmpleado',
            'customer_id' => 'IDCliente',
            'activity_id' => 'IDActividad',
            'exercise_id' => 'IDEjercicio',
            'date' => 'FechaRegistro',
            'hours' => 'HoraLaborada',
            'description' => 'Descripcion',
            'deleted' => 'Baja',
        ],
        'employees' => ['db_schema' => 'Catalogos', 'table' => 'Empleados', 'id' => 'IDEmpleado', 'name' => ['Nombres', 'ApellidoPaterno', 'ApellidoMaterno'], 'deleted' => 'Baja'],
        'customers' => ['db_schema' => 'Catalogos', 'table' => 'Clientes', 'id' => 'IDCliente', 'name' => 'Nombre'],
        'activities' => ['db_schema' => 'Catalogos', 'table' => 'Actividades', 'id' => 'IDActividad', 'name' => 'Nombre'],
    ],
];
