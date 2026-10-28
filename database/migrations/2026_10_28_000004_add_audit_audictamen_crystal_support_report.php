<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('technical_support_reports')->updateOrInsert(
            ['folio' => 'ST-20261028-0012'],
            [
                'issued_at' => '2026-10-28 21:55:30',
                'responsible' => 'Julian Emiliano Ortiz Rivero',
                'service_recipient' => 'Auditoría',
                'supports' => json_encode([
                    [
                        'key' => 'audictamen_crystal_reports_install',
                        'title' => 'Instalación del componente de Crystal Reports para AUDICTAMEN',
                        'quantity' => 1,
                        'targets' => ['Sanchez Coba Caridad Iliana'],
                    ],
                ], JSON_UNESCAPED_UNICODE),
                'details' => json_encode(['versions_applied' => []], JSON_UNESCAPED_UNICODE),
                'created_by' => null,
                'created_at' => '2026-10-28 21:55:30',
                'updated_at' => '2026-10-28 21:55:30',
            ],
        );
    }

    public function down(): void
    {
        DB::table('technical_support_reports')
            ->where('folio', 'ST-20261028-0012')
            ->delete();
    }
};
