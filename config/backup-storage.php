<?php

return [
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
        'destination_root' => env('CONTPAQ_EXPORT_ROOT', ''),
        // Destinos adicionales opcionales. Cada raíz debe ser una ruta UNC
        // previamente compartida con el equipo donde se ejecuta Laravel.
        'additional_destinations' => [
            [
                'key' => 'secundaria',
                'label' => env('CONTPAQ_EXPORT_SECONDARY_LABEL', ''),
                'root' => env('CONTPAQ_EXPORT_SECONDARY_ROOT', ''),
            ],
        ],
        'chunk_size' => (int) env('CONTPAQ_EXPORT_CHUNK_SIZE', 8 * 1024 * 1024),
        'queue_connection' => env('CONTPAQ_EXPORT_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'database')),
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
