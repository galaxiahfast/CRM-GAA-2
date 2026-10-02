<?php

return [
    'responsible' => 'Lic. Julián Emiliano Ortiz Rivero',

    'email' => [
        'from_address' => env('BACKUP_MAIL_FROM_ADDRESS', 'emiliano.ortiz@datamid.com.mx'),
        'from_name' => env('BACKUP_MAIL_FROM_NAME', 'Soporte DataMID'),
        'recipients' => array_values(array_filter(array_map(
            static fn (string $email): string => trim($email),
            explode(',', env('BACKUP_MAIL_RECIPIENTS', 'dionicio.farfan@datamid.com.mx,soporte@datamid.com.mx,becarios@datamid.com.mx')),
        ))),
        'recipient_options' => [
            'dionicio.farfan@datamid.com.mx' => 'Dionicio Farfán',
            'soporte@datamid.com.mx' => 'Soporte DataMID',
            'becarios@datamid.com.mx' => 'Becarios DataMID',
            'emiliano.ortiz@datamid.com.mx' => 'Enviarme una copia',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fuentes de Create Synchronicity
    |--------------------------------------------------------------------------
    |
    | Estas rutas se consultan en modo de solo lectura. La cuenta que ejecuta
    | PHP en producción debe tener permiso de lectura sobre cada recurso SMB.
    |
    */
    'servers' => [
        [
            'name' => 'IFACTURE',
            'ip' => '192.168.2.178',
            'share' => '\\\\SRVIFAC\\log',
            'profiles' => [
                ['name' => 'Comprobantes33', 'log' => 'Comprobantes33'],
                ['name' => 'ComprobantesPagos', 'log' => 'ComprobantesPagos'],
                ['name' => 'Data', 'log' => 'Data'],
            ],
        ],
        [
            'name' => 'AUDICTAMEN',
            'ip' => '192.168.2.203',
            'share' => '\\\\LENOVO-LA0X1877\\log',
            'profiles' => [
                ['name' => 'Audictamen', 'log' => 'Audictamen'],
                ['name' => 'datafiscal', 'log' => 'datafiscal'],
                ['name' => 'datahub', 'log' => 'datagub'],
                ['name' => 'MSSQL', 'log' => 'MSSQL'],
            ],
        ],
        [
            'name' => 'Compaqi (Tulum)',
            'ip' => '192.168.1.2',
            'share' => '\\\\192.168.1.2\\log',
            'profiles' => [
                ['name' => 'Compac', 'log' => 'Compac'],
                ['name' => 'Data', 'log' => 'Data'],
            ],
        ],
        [
            'name' => 'Compaqi (Mérida)',
            'ip' => '192.168.2.210',
            'share' => '\\\\SRVCONTPAQ\\log',
            'profiles' => [
                ['name' => 'base_contabilidad', 'log' => 'base_contabilidad'],
                ['name' => 'base_nominas', 'log' => 'base_nominas'],
                ['name' => 'Carpeta_Contpaq', 'log' => 'Carpeta_contpaq'],
                ['name' => 'Compac', 'log' => 'Compac'],
            ],
        ],
        [
            'name' => 'Compaqi (Auditoría)',
            'ip' => '192.168.2.252',
            'share' => '\\\\SRVCONTA2\\log',
            'profiles' => [
                ['name' => 'Compac', 'log' => 'compac'],
                ['name' => 'Data', 'log' => 'Data'],
            ],
        ],
    ],
];
