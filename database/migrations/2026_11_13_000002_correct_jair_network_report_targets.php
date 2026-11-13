<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const FOLIO = 'ST-20261113-0029';

    public function up(): void
    {
        $report = DB::table('technical_support_reports')->where('folio', self::FOLIO)->first();
        if (! $report) {
            return;
        }

        $supports = json_decode($report->supports, true) ?: [];
        foreach ($supports as &$support) {
            $support['targets'] = ['Jair Guadalupe Herrera Carrillo'];
        }
        unset($support);

        $details = json_decode($report->details, true) ?: [];
        $details['omit_closing_paragraph'] = true;

        DB::table('technical_support_reports')->where('id', $report->id)->update([
            'supports' => json_encode($supports, JSON_UNESCAPED_UNICODE),
            'details' => json_encode($details, JSON_UNESCAPED_UNICODE),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // No se restaura la atribución incorrecta del equipo.
    }
};
