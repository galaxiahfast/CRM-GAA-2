<?php

return [
    'display_timezone' => env('BACKUP_DISPLAY_TIMEZONE', 'America/Mexico_City'),
    'infrastructure' => [
        'application_host' => env('BACKUP_APPLICATION_HOST', '192.168.2.249'),
        'source_host' => env('BACKUP_SOURCE_HOST', '192.168.2.210'),
    ],
    'server' => [
        // Catálogo de solo lectura de CONTPAQi. La aplicación nunca crea,
        // mueve, renombra ni elimina archivos dentro de esta ubicación.
        'root' => env('CONTPAQ_BACKUP_ROOT', '\\\\SRVCONTPAQ\\Compac2'),
        'companies_directory' => env('CONTPAQ_COMPANIES_DIRECTORY', 'Empresas'),
        'archives_directory' => env('CONTPAQ_ARCHIVES_DIRECTORY', 'Index/respaldos'),
        'cache_seconds' => (int) env('CONTPAQ_BACKUP_CACHE_SECONDS', 300),
    ],
    'export' => [
        // Nombre esperado del volumen, mostrado en la interfaz.
        'volume_label' => env('CONTPAQ_EXPORT_VOLUME_LABEL', 'CONTABILIDAD'),
        // Debe ser una ruta de red estable compartida por el servidor, por ejemplo
        // \\SERVIDOR\CONTABILIDAD. Así no depende de la letra local del USB.
        'destination_root' => env('CONTPAQ_EXPORT_ROOT') ?: '\\\\SRVCONTPAQ\\e',
        // En Windows busca automáticamente las unidades disponibles en el
        // equipo donde corre PHP. La unidad del sistema nunca se ofrece.
        'detect_local_volumes' => (bool) env('CONTPAQ_EXPORT_DETECT_VOLUMES', false),
        // Destinos adicionales opcionales. Cada raíz debe ser una ruta UNC
        // previamente compartida con el equipo donde se ejecuta Laravel.
        'additional_destinations' => [
            [
                'key' => 'secundaria',
                'label' => env('CONTPAQ_EXPORT_SECONDARY_LABEL', ''),
                'root' => env('CONTPAQ_EXPORT_SECONDARY_ROOT', ''),
            ],
        ],
        'chunk_size' => (int) env('CONTPAQ_EXPORT_CHUNK_SIZE', 16 * 1024 * 1024),
        'queue_connection' => env('CONTPAQ_EXPORT_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'database')),
    ],
    'backup_profiles' => [
        'merida-contabilidad' => [
            'label' => 'Mérida · Contabilidad',
            'site' => 'merida',
            'department' => 'contabilidad',
            'source_root' => env('CONTPAQ_MERIDA_CONTABILIDAD_ROOT', '\\\\SRVCONTPAQ\\Compac2'),
            'destination_root' => env('CONTPAQ_MERIDA_CONTABILIDAD_EXPORT_ROOT', '\\\\SRVCONTPAQ\\e'),
            'destination_label' => 'CONTABILIDAD',
            'classification' => 'accounting',
            'enabled' => true,
        ],
        'merida-nominas' => [
            'label' => 'Mérida · Nóminas',
            'site' => 'merida',
            'department' => 'nominas',
            'source_root' => env('CONTPAQ_MERIDA_NOMINAS_ROOT', '\\\\SRVCONTPAQ\\Compac2'),
            'destination_root' => env('CONTPAQ_MERIDA_NOMINAS_EXPORT_ROOT', '\\\\SRVCONTPAQ\\f'),
            'destination_label' => 'NÓMINAS',
            'classification' => 'payroll',
            'enabled' => true,
        ],
        // Al habilitar Tulum solo será necesario configurar sus rutas.
        'tulum-contabilidad' => [
            'label' => 'Tulum · Contabilidad',
            'site' => 'tulum',
            'department' => 'contabilidad',
            'source_root' => env('CONTPAQ_TULUM_CONTABILIDAD_ROOT', ''),
            'destination_root' => env('CONTPAQ_TULUM_CONTABILIDAD_EXPORT_ROOT', ''),
            'destination_label' => 'TULUM CONTABILIDAD',
            'classification' => 'accounting',
            'enabled' => (bool) env('CONTPAQ_TULUM_CONTABILIDAD_ENABLED', false),
        ],
    ],
    'sql_catalog' => [
        // Solo ejecuta SELECT sobre los catálogos generales de CONTPAQi.
        'enabled' => (bool) env('CONTPAQ_SQL_CATALOG_ENABLED', true),
        'username' => env('CONTPAQ_SQL_USERNAME', ''),
        'password' => env('CONTPAQ_SQL_PASSWORD', ''),
        'sqlcmd_path' => env('CONTPAQ_SQLCMD_PATH', 'sqlcmd'),
        'timeout_seconds' => (int) env('CONTPAQ_SQL_TIMEOUT_SECONDS', 15),
        'cache_seconds' => (int) env('CONTPAQ_SQL_CACHE_SECONDS', 300),
        'databases' => [
            'payroll' => env('CONTPAQ_MERIDA_NOMINAS_CATALOG_DATABASE', 'nomGenerales'),
            'accounting' => env('CONTPAQ_MERIDA_CONTABILIDAD_CATALOG_DATABASE', 'GeneralesSQL'),
        ],
        'sites' => [
            'merida' => [
                'payroll' => env('CONTPAQ_MERIDA_NOMINAS_SQL_INSTANCE', 'SRVCONTPAQ\\NOMINAS'),
                'accounting' => env('CONTPAQ_MERIDA_CONTABILIDAD_SQL_INSTANCE', 'SRVCONTPAQ\\CONTABILIDAD'),
            ],
            // Cada instancia de Tulum es opcional. El catálogo consulta solo
            // las que estén configuradas y nunca ejecuta escrituras.
            'tulum' => [
                'payroll' => env('CONTPAQ_TULUM_NOMINAS_SQL_INSTANCE', ''),
                'accounting' => env('CONTPAQ_TULUM_CONTABILIDAD_SQL_INSTANCE', ''),
            ],
        ],
    ],
    // Catálogo confirmado de las bases que pertenecen a Nóminas. La
    // lista solo se usa si SQL Server todavía no cuenta con un catálogo
    // disponible ni con una última lectura válida en caché.
    'company_classifications' => [
        'merida' => [
            'payroll_folders' => [
                'ct1_CORPORATIVO_N',
                'ct1_SOLUCIONANDO_',
                'ct1_TRADEWINDS_AP',
                'ctALBERTO_RENE_GO',
                'NtALMACEN_DE_PINT',
                'NtALPINA_SA_DE_CV',
                'NtALUMIK_SA_DE_CV',
                'NtARGENTUM_MEXICA',
                'NtCIRCUITO_SA_DE_',
                'ct_COCO_MAYA',
                'ctCOCO_MAYA_DE_YU',
                'ctCOCO_MAYA_DE_YUCATAN_SPR',
                'ctCORPORATIVO_NOH',
                'ctCORPORATIVO_NOHOCH_TULUM_PTU',
                'ctDATAMID_DEL_SUR',
                'ntDEFENSA_PENAL_E',
                'ctDEFENSA_PENAL_E',
                'NtALUMAYAB21_DE',
                'NtHERRAMAX_SA_DE_',
                'ctINMOBILIARIA_HO',
                'ctJACOBO_711',
                'ctJEREMY_TOURS_S',
                'ctKORLINT',
                'NtLA_VIGA_VIANA_Y',
                'ctMEXICO_PACIFIC_',
                'ctPINTURAS_SIMILA',
                'ctSOC_COOP_PRODUC',
                'ctTRADEWINDS_APLI',
                'NtVALSI_SA_DE_CV',
            ],
        ],
    ],
    'disk' => env('BACKUP_STORAGE_DISK', 'local'),
    // Keep each request below the usual 40 MB PHP post limit while reducing
    // round trips for large backup archives.
    'chunk_size' => (int) env('BACKUP_CHUNK_SIZE', 32 * 1024 * 1024),
    'parallel_chunks' => (int) env('BACKUP_PARALLEL_CHUNKS', 3),
    'max_file_size' => (int) env('BACKUP_MAX_FILE_SIZE', 500 * 1024 * 1024 * 1024),
    'max_extracted_size' => (int) env('BACKUP_MAX_EXTRACTED_SIZE', 500 * 1024 * 1024 * 1024),
    'max_active_uploads' => (int) env('BACKUP_MAX_ACTIVE_UPLOADS', 2),
    'stale_after_minutes' => (int) env('BACKUP_UPLOAD_STALE_MINUTES', 15),
    'queue' => env('BACKUP_UPLOAD_QUEUE', 'backup-uploads'),
    // Process the archive as part of the completion request. This keeps the
    // upload self-contained and avoids requiring a separately managed worker.
    'queue_connection' => env('BACKUP_QUEUE_CONNECTION', 'sync'),
    'worker_timeout' => (int) env('BACKUP_WORKER_TIMEOUT', 7200),
    'sites' => [
        'merida' => 'Mérida',
        'tulum' => 'Tulum',
    ],
    'products' => [
        'merida' => [
            'contabilidad' => 'Contabilidad',
            'nominas' => 'Nóminas',
        ],
        'tulum' => [
            'contabilidad' => 'Contabilidad',
        ],
    ],
    'demo_companies' => [
        'Abarrotes del Sureste',
        'Administradora Peninsular',
        'Arquitectura Maya',
        'Comercializadora del Caribe',
        'Constructora Horizonte',
        'Consultoría Fiscal del Mayab',
        'Distribuidora Kukulkán',
        'Grupo Contable Mérida',
        'Hotel Costa Esmeralda',
        'Inmobiliaria Montejo',
        'Logística Yucatán',
        'Operadora Tulum',
        'Servicios Corporativos Itzá',
        'Tecnología del Golfo',
        'Transportes Peninsulares',
    ],
];
