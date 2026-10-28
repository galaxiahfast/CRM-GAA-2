<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $target = ['Noh Chan Claudia Alejandra'];

        DB::table('technical_support_reports')->updateOrInsert(
            ['folio' => 'ST-20261028-0011'],
            [
                'issued_at' => '2026-10-28 21:52:26',
                'responsible' => 'Julian Emiliano Ortiz Rivero',
                'service_recipient' => 'Auditoría',
                'supports' => json_encode([
                    [
                        'key' => 'microsoft_365_install',
                        'title' => 'Instalación de Microsoft 365',
                        'quantity' => 1,
                        'targets' => $target,
                    ],
                    [
                        'key' => 'office_license_activation',
                        'title' => 'Activación de licencia de Microsoft Office',
                        'quantity' => 1,
                        'targets' => $target,
                    ],
                    [
                        'key' => 'claude_addin_activation',
                        'title' => 'Activación del complemento de Claude en Microsoft 365',
                        'quantity' => 1,
                        'targets' => $target,
                    ],
                ], JSON_UNESCAPED_UNICODE),
                'details' => json_encode(['versions_applied' => []], JSON_UNESCAPED_UNICODE),
                'created_by' => null,
                'created_at' => '2026-10-28 21:52:26',
                'updated_at' => '2026-10-28 21:52:26',
            ],
        );
    }

    public function down(): void
    {
        DB::table('technical_support_reports')
            ->where('folio', 'ST-20261028-0011')
            ->delete();
    }
};
