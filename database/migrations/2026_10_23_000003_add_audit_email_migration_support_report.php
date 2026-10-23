<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('technical_support_reports')->updateOrInsert(
            ['folio' => 'ST-20261023-0007'],
            [
                'issued_at' => '2026-10-23 23:29:08',
                'responsible' => 'Julian Emiliano Ortiz Rivero',
                'service_recipient' => 'Auditoría',
                'supports' => json_encode([
                    [
                        'key' => 'email_local_backup',
                        'title' => 'Respaldo local de correo, configuración, contactos y calendario',
                        'quantity' => 1,
                    ],
                    [
                        'key' => 'email_data_migration',
                        'title' => 'Migración de correo y datos al equipo nuevo',
                        'quantity' => 1,
                    ],
                    [
                        'key' => 'email_pop_to_imap',
                        'title' => 'Cambio de cuenta de correo de POP a IMAP y sincronización inicial',
                        'quantity' => 1,
                    ],
                    [
                        'key' => 'windows_startup_ini_removal',
                        'title' => 'Eliminación de archivo .ini del inicio de Windows',
                        'quantity' => 1,
                    ],
                ], JSON_UNESCAPED_UNICODE),
                'details' => json_encode(['versions_applied' => []], JSON_UNESCAPED_UNICODE),
                'created_by' => null,
                'created_at' => '2026-10-23 23:29:08',
                'updated_at' => '2026-10-23 23:29:08',
            ],
        );
    }

    public function down(): void
    {
        DB::table('technical_support_reports')
            ->where('folio', 'ST-20261023-0007')
            ->delete();
    }
};
