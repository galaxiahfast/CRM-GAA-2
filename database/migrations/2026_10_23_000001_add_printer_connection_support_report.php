<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('technical_support_reports')->updateOrInsert(
            ['folio' => 'ST-20261023-0006'],
            [
                'issued_at' => '2026-10-23 23:03:00',
                'responsible' => 'Julian Emiliano Ortiz Rivero',
                'supports' => json_encode([
                    [
                        'key' => 'computer_printer_connection',
                        'title' => 'Conexión de computadora a impresora',
                        'quantity' => 1,
                    ],
                ], JSON_UNESCAPED_UNICODE),
                'details' => json_encode(['versions_applied' => []], JSON_UNESCAPED_UNICODE),
                'created_by' => null,
                'created_at' => '2026-10-23 23:03:00',
                'updated_at' => '2026-10-23 23:03:00',
            ],
        );
    }

    public function down(): void
    {
        DB::table('technical_support_reports')
            ->where('folio', 'ST-20261023-0006')
            ->delete();
    }
};
