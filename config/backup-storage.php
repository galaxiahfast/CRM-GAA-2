<?php

return [
    'disk' => env('BACKUP_STORAGE_DISK', 'local'),
    'chunk_size' => (int) env('BACKUP_CHUNK_SIZE', 5 * 1024 * 1024),
    'max_file_size' => (int) env('BACKUP_MAX_FILE_SIZE', 500 * 1024 * 1024 * 1024),
    'max_extracted_size' => (int) env('BACKUP_MAX_EXTRACTED_SIZE', 500 * 1024 * 1024 * 1024),
    'max_active_uploads' => (int) env('BACKUP_MAX_ACTIVE_UPLOADS', 2),
    'stale_after_minutes' => (int) env('BACKUP_UPLOAD_STALE_MINUTES', 15),
    'queue' => env('BACKUP_UPLOAD_QUEUE', 'backup-uploads'),
    'sites' => [
        'merida' => 'Mérida',
        'tulum' => 'Tulum',
    ],
];
