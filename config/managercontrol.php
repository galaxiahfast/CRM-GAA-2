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

    // Cuenta independiente para importar catálogos. Nunca se usa para registrar horas.
    'catalog_write' => [
        'enabled' => (bool) env('MANAGERCONTROL_CATALOG_WRITE_ENABLED', false),
        // Si no se define una cuenta propia, usa la cuenta de captura (crm_captura) una vez que el administrador de
        // SQL Server le otorgue SELECT e INSERT sobre los tres catálogos (ver docs/managercontrol-catalogos.md).
        'username' => env('MANAGERCONTROL_CATALOG_WRITE_USERNAME', env('MANAGERCONTROL_WRITE_USERNAME')),
        'password' => env('MANAGERCONTROL_CATALOG_WRITE_PASSWORD', env('MANAGERCONTROL_WRITE_PASSWORD')),
    ],
    // Vínculos manuales (id del CRM => id en ManagerControl), revisados uno por uno el 2026-11-12 contra el respaldo
    // storage/app/managercontrol-backups/2026-11-12_203719. Son clientes que YA existen en ManagerControl con otro nombre
    // (sin "SA DE CV", con errores de captura o con los apellidos en otro orden) y que se habrían duplicado. Se confirmaron
    // con las iniciales del RFC del CRM. Cuando el cliente está repetido en ManagerControl se usa el que tiene más horas.
    'catalog_manual_links' => [
        'customers' => [
            7 => 389,    // GIL CUEVAS LILIA MARIA = LILIA GIL CUEVAS
            9 => 655,    // MARIA GUADALUPE MOLINA (MOZG) = GUADALUPE MOLINA ZALDIVAR
            10 => 40,    // LISSIE MIREYA FUENTE MORENO = ...FUENTES MORENO
            11 => 362,   // MARIA EUGENIA DE ATOCHA GONZALEZ (GOEE) = MARIA EUGENIA GONZALEZ ESCALANTE
            13 => 124,   // CARLOS ENRIQUE GUILLERMO (GUCC) = CARLOS GUILLERMO CASTILLO (27 registros; el 306 solo 1)
            17 => 731,   // ALBERTO MATA (MAMA) = ALBERTO MATA MENDOZA (33 registros; el 545 solo 2)
            19 => 125,   // ALBERTO RENE GONZALEZ (GOEA) = ALBERTO RENE GONZALEZ ESCALANTE
            20 => 654,   // LUIS BAQUEDANO PEON = LUIS AUGUSTO BAQUEDANO PEON
            21 => 2783,  // VANESSA NOEMY HEREDIA (HEMV) = VANESA HEREDIA MEJIA
            22 => 111,   // JORGE ALBERTO HEREDIA (HEMJ) = HEREDIA MEJIA JORGE ALBERTO (243 registros; el 81 solo 19)
            23 => 679,   // JULIETA DEL CARMEN MENDICUTI (MEMJ) = JULIETA MENDICUTI MOGUEL
            24 => 457,   // MARIA TERESA RIVERA (RIDT) = MARIA TERESA RIVERA DAMIAN
            26 => 711,   // BUENFIL KELLEHER BERNARDO = BERNARDO BUENFIL
            29 => 698,   // AIDA GABRIELA MEJIA (MECA) = GABRIELA MEJIA CACERES
            30 => 2785,  // ALEJANDRO RUIZ ROSSELL (repetido 2785 y 2789, ninguno con horas)
            37 => 565,   // GRUPO PROBEEF S DE PR DE RL DE CV = GRUPO PROBEEF
            38 => 610,   // DECOME SA DE CV = DECOME
            40 => 609,   // GRUPO AGROPECUARIO EL PARAJE
            42 => 309,   // PATRONATO DE ASISTENCIA SOCIAL Y CULTURAL DE HUNUCMA = PATRONATO DE HUNUCMA
            44 => 657,   // CARLOS ARIEL MENDEZ FRANCO
            46 => 540,   // MANUEL JESUS OXTE CABRERA = MANUEL OXTE CABRERA
            48 => 528,   // DISTRIBUIDORA MAYORISTA DE CRISTALES
            51 => 636,   // TRANSPORTES Y MUDANZAS QUALITY (RFC con letras invertidas en ManagerControl)
            52 => 2795,  // ROQUE EMILIANO AVILA WALSON (44 registros; el 3800 ninguno)
            55 => 28,    // BAQUEDANO CASADO LUIS EUGENIO = LUIS EUGENIO BAQUEDANO CASADO
            56 => 66,    // ALEJANDRO ARTURO ORTEGA (OENA) = ALEJANDRO ORTEGA NOVELO
            59 => 68,    // RICARDO PENICHE (PERR) = RICARDO PENICHE RODRIGUEZ
            60 => 95,    // JORGE ALBERTO HEREDIA TRUJILLO (1341 registros; el 494 tiene 261)
            61 => 29,    // LUZ MARGARITA MEJIA (MECL) = LUZ MARGARIATA MEJIA CACERES
            64 => 516,   // JIMENA HERNANDEZ (HESJ) = JIMENA HERNANDEZ SALAZAR
            70 => 589,   // PATRICIO CORREA MILLET
            72 => 564,   // COMERCIALIZADORA PROBEFF = COMERCIALIADORA PROBEEF
            73 => 300,   // FERNANDO MATA ALCOCER
            74 => 727,   // PEDRO MATA ALCOCER
            75 => 90,    // HUMBERTO MEDINA ALBORNOS (MEAH) = HUMBERTO MEDINA
            76 => 91,    // TOMASA GAMBOA PERAZA
            77 => 301,   // JULIO CESAR SOSA (SOMJ) = JULIO CESAR SOSA MENDOZA
            78 => 60,    // MARTIN GELACIO MORFINEZ (MOMM) = MARTIN MORFINES MENDOZA (83 registros; el 498 tiene 47)
            80 => 13,    // MARTIN HILARIO ARGAEZ (AAGM) = MARTIN ARGAEZ GONZALEZ
            81 => 458,   // BACCSA INDUSTRIAL = BACSA INDUSTRIA SA DE CV (611 registros; el 1764 tiene 45)
            83 => 745,   // JORGE BUENFIL KELLEHER = JORGE BUENFIL
            86 => 113,   // JUAN JOSE CARDEÑA MANZANERO = JUAN CARDEÑA MAZANERO
            87 => 500,   // COHERSA DEL SURESTE
            88 => 21,    // COMPONENTES ELECTRONICOS INDUSTRIALES
            90 => 535,   // EDGARDO ESQUILANO MARTINEZ (EUME) = EDGARDO ESQUILIANO
            94 => 3799,  // FLEXIARRIENDO
            95 => 602,   // FRIGO INSUMOS
            99 => 532,   // ILE VICTORIA URIBE
            101 => 454,  // JIM MANN'S MERIDA PROPERTIES = JIM MANNS MERIDA
            110 => 455,  // MONTONA SOLAR
            125 => 48,   // DOLPHIN DEVELOPMENT CORPORATION
            126 => 741,  // FELIX ENRIQUE MEDINA MADRIAGA = FELIX MEDINA MADARIAGA
            127 => 4824, // GRUPO CERVECERO LA HACH = LA JACH
            128 => 443,  // GUILLERMO EMILIO HERNANDEZ SALAZAR (HESG) = GUILLERMO HERNNDEZ
            131 => 109,  // OPERACION E INTEGRACION EFICIENTE DE MEXICO
            133 => 114,  // RECURSOS HUMANOS VIDRIOS Y ALUMINIOS
            134 => 744,  // LA PROVENCE 33
            136 => 513,  // LUCIA DEL ROSARIO MONTALVO GAMBOA = LUCIA MONTALVO
            137 => 719,  // MARCO ANTONIO CAB CHAN = MARCO ANTONIO CAB (263 registros; el 720 tiene 123)
            138 => 704,  // MELISSA ALEJANDRA SEGURA PACHECO = MELLISA SEGURA
            139 => 398,  // MILDRED CRISTINA RAMIREZ ARMENTA (mismo RFC)
            140 => 70,   // RAFAEL ALEJANDRO GAMBOA QUINTAL = RAFAEL GAMBOA QUINTAL
            145 => 721,  // NORMA ANGELICA ROSSELL GARCIA (ROGN) = NORMA GARCIA ROSEL
            146 => 4826, // MIARIA DEL PILAR SOSA CAMARA = MARIA PILAR SOSA CAMARA
        ],
    ],
    // Clientes que se dejan sin importar hasta que alguien confirme si ya existen en ManagerControl.
    'catalog_hold' => [
        'customers' => [
            12 => 'JULIO MEJIA SALAZAR: ¿es JULIO MEJIA (#475)? El RFC del CRM tiene fecha 2015.',
            16 => 'CARLOS ANTONIO GRANJA PEREZ: ManagerControl tiene CARLOS ANTONIO GRANJA RICALDE (#54), con otro segundo apellido.',
            116 => 'SISTEMAS ELECTRONICOS PENINSULARES: ¿es SISTEMAS ELECTRICOS (#606)?',
            118 => 'TRANSPORTES FRIGO INN: ¿es TRANSPORTE FRIGO (#603)?',
            151 => 'Despacho López Mezquita QA: es un cliente de prueba del CRM y su RFC no es válido.',
        ],
    ],
    // Departamento de ManagerControl para cada categoría del CRM (confirmado para la empresa/ejercicio 1/3).
    'catalog_departments' => [
        'Atención a Clientes' => 45,
        'Auditoría y Revisión' => 11,
        'Consultoría Fiscal' => 35,
        'Contabilidad' => 41,
        'Control Administrativo' => 45,
        'Declaraciones e Informativas' => 41,
        'Facturación Electrónica' => 41,
        'Impuestos Especiales' => 41,
        'Impuestos Estatales' => 41,
        'Impuestos Federales' => 41,
        'Nómina y Seguridad Social' => 44,
        'Servicio Visual QA' => 43,
        'Trámites y Gestoría' => 35,
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
        'customers' => ['db_schema' => 'Catalogos', 'table' => 'Clientes', 'id' => 'IDCliente', 'name' => 'Nombre', 'rfc' => 'RFC', 'source_username' => 'NombreUsuario', 'company_id' => 'IDEmpresa', 'exercise_id' => 'IDEjercicio', 'deleted' => 'Baja'],
        'activities' => ['db_schema' => 'Catalogos', 'table' => 'Actividades', 'id' => 'IDActividad', 'name' => 'Nombre', 'source_username' => 'NombreUsuario', 'section_id' => 'IDSeccion', 'company_id' => 'IDEmpresa', 'exercise_id' => 'IDEjercicio', 'deleted' => 'Baja'],
        // «Cuenta/Concepto» de la app de escritorio: agrupa las actividades (Actividades.IDSeccion). Solo sirve para elegir; el registro no la guarda.
        'sections' => ['db_schema' => 'Catalogos', 'table' => 'Secciones', 'id' => 'IDSeccion', 'name' => 'Nombre', 'source_username' => 'NombreUsuario', 'department_id' => 'IDDepartamento', 'company_id' => 'IDEmpresa', 'exercise_id' => 'IDEjercicio', 'deleted' => 'Baja'],
    ],
];
