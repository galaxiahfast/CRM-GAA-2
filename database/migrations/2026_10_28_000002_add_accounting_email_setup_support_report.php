<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('technical_support_reports')->updateOrInsert(
            ['folio' => 'ST-20261028-0010'],
            [
                'issued_at' => '2026-10-28 21:06:10',
                'responsible' => 'Julian Emiliano Ortiz Rivero',
                'service_recipient' => 'Contabilidad',
                'supports' => json_encode([
                    [
                        'key' => 'email_installation',
                        'title' => 'Instalación de correo electrónico',
                        'quantity' => 1,
                        'targets' => ['Carlos Rafael De Jesús Diaz Ruiz'],
                    ],
                ], JSON_UNESCAPED_UNICODE),
                'details' => json_encode(['versions_applied' => []], JSON_UNESCAPED_UNICODE),
                'created_by' => null,
                'created_at' => '2026-10-28 21:06:10',
                'updated_at' => '2026-10-28 21:06:10',
            ],
        );
    }

    public function down(): void
    {
        DB::table('technical_support_reports')
            ->where('folio', 'ST-20261028-0010')
            ->delete();
    }
};
