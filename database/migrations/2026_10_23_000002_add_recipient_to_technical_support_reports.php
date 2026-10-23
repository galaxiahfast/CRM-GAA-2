<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('technical_support_reports', function (Blueprint $table): void {
            $table->string('service_recipient', 180)->nullable()->after('responsible');
        });

        foreach ($this->recipients() as $folio => $recipient) {
            DB::table('technical_support_reports')
                ->where('folio', $folio)
                ->update(['service_recipient' => $recipient]);
        }

        DB::table('technical_support_reports')
            ->where('folio', 'ST-20261022-0002')
            ->update([
                'supports' => json_encode([
                    ['key' => 'contpaqi_nominas_server_update', 'title' => 'Actualización de servidor de CONTPAQi Nóminas', 'quantity' => 1],
                    ['key' => 'contpaqi_nominas_update', 'title' => 'Actualización de terminales de CONTPAQi Nóminas', 'quantity' => 3],
                    ['key' => 'contpaqi_contabilidad_update', 'title' => 'Actualización de terminales de CONTPAQi Contabilidad', 'quantity' => 3],
                ], JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('technical_support_reports')
            ->where('folio', 'ST-20261022-0002')
            ->update([
                'supports' => json_encode([
                    ['key' => 'contpaqi_nominas_server_update', 'title' => 'Actualización de servidor de CONTPAQi Nóminas', 'quantity' => 1],
                    ['key' => 'contpaqi_nominas_update', 'title' => 'Actualización de terminales de CONTPAQi Nóminas', 'quantity' => 5],
                ], JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);

        Schema::table('technical_support_reports', function (Blueprint $table): void {
            $table->dropColumn('service_recipient');
        });
    }

    private function recipients(): array
    {
        return [
            'ST-20261022-0002' => 'Nóminas y Contabilidad',
            'ST-20261022-0003' => 'Contabilidad',
            'ST-20261022-0004' => 'Auditoría',
            'ST-20261022-0005' => 'Contabilidad',
            'ST-20261023-0006' => 'Jurídico',
        ];
    }
};
