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

    // Empresa y Ejercicio, como al entrar a la app de escritorio. La tabla de horas tiene llave (IDEmpresa, IDEjercicio, IDRegistroDiario)
    // y la base guarda varias empresas: TODAS las consultas (y escrituras) se limitan a estos dos valores.
    // 1 / 3 = «GONZÁLEZ ALONZO Y ASOCIADOS S.C.P.-CONTABILIDAD», ejercicio «ENERO 2010- DICIEMBRE 2010» (el que usa la oficina).
    'company_id' => env('MANAGERCONTROL_COMPANY_ID', 1),
    'exercise_id' => env('MANAGERCONTROL_EXERCISE_ID', 3),

    /*
    | Escritura (agregar, editar y dar de baja actividades). APAGADA por defecto: el CRM es de solo lectura
    | hasta que el responsable de la base cree una cuenta con permisos SOLO sobre ControlTiempos.RegistroDiario
    | (ver docs/managercontrol-escritura.md). Usa credenciales propias, distintas de las de lectura.
    */
    'write' => [
        'enabled' => (bool) env('MANAGERCONTROL_WRITE_ENABLED', false),
        'username' => env('MANAGERCONTROL_WRITE_USERNAME'),
        'password' => env('MANAGERCONTROL_WRITE_PASSWORD'),
        'max_hours' => 24,
        // Días hacia atrás que se permite capturar (nunca fechas futuras).
        'max_past_days' => (int) env('MANAGERCONTROL_WRITE_MAX_PAST_DAYS', 400),
    ],

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
            'id' => 'IDRegistroDiario',
            'company_id' => 'IDEmpresa',
            'exercise_id' => 'IDEjercicio',
            'date' => 'FechaRegistro',
            'hours' => 'HoraLaborada',
            'description' => 'Descripcion',
            'deleted' => 'Baja',
            // Columnas que llena ManagerControl al capturar (confirmado con los datos): ver docs/managercontrol-escritura.md
            'created_at' => 'FechaCreacion',
            'username' => 'NombreUsuario',
            'rate' => 'Precio',
            'subtotal' => 'SubTotal',
            'section_id' => 'IDSeccion',
        ],
        'employees' => ['db_schema' => 'Catalogos', 'table' => 'Empleados', 'id' => 'IDEmpleado', 'name' => ['Nombres', 'ApellidoPaterno', 'ApellidoMaterno'], 'deleted' => 'Baja', 'department_id' => 'IDDepartamento', 'company_id' => 'IDEmpresa', 'exercise_id' => 'IDEjercicio', 'username' => 'NombreUsuario', 'rate' => 'Precio'],
        'departments' => ['db_schema' => 'Catalogos', 'table' => 'Departamentos', 'id' => 'IDDepartamento', 'name' => 'Nombre', 'company_id' => 'IDEmpresa', 'exercise_id' => 'IDEjercicio'],
        'customers' => ['db_schema' => 'Catalogos', 'table' => 'Clientes', 'id' => 'IDCliente', 'name' => 'Nombre', 'company_id' => 'IDEmpresa', 'exercise_id' => 'IDEjercicio', 'deleted' => 'Baja'],
        'activities' => ['db_schema' => 'Catalogos', 'table' => 'Actividades', 'id' => 'IDActividad', 'name' => 'Nombre', 'section_id' => 'IDSeccion', 'company_id' => 'IDEmpresa', 'exercise_id' => 'IDEjercicio', 'deleted' => 'Baja'],
        // «Cuenta/Concepto» de la app de escritorio: agrupa las actividades (Actividades.IDSeccion). Solo sirve para elegir; el registro no la guarda.
        'sections' => ['db_schema' => 'Catalogos', 'table' => 'Secciones', 'id' => 'IDSeccion', 'name' => 'Nombre', 'company_id' => 'IDEmpresa', 'exercise_id' => 'IDEjercicio', 'deleted' => 'Baja'],
    ],
];
