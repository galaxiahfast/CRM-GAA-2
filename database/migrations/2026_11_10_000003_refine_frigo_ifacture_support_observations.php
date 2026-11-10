<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const FOLIO = 'ST-20261110-0027';

    private const SUMMARY = 'iFacture solo reconocía la IP y no el nombre del servidor. Se configuró y probó el acceso por nombre de red para evitar depender de una IP fija.';

    private const PREVIOUS_SUMMARY = 'La terminal no reconocía el nombre del servidor de iFacture, aunque sí permitía acceder mediante su dirección IP. Se configuró y comprobó el acceso usando el nombre de red del servidor, de modo que la terminal pueda localizarlo sin depender de una dirección IP fija.';

    public function up(): void
    {
        $this->updateSummary(self::SUMMARY);
    }

    public function down(): void
    {
        $this->updateSummary(self::PREVIOUS_SUMMARY);
    }

    private function updateSummary(string $summary): void
    {
        $report = DB::table('technical_support_reports')->where('folio', self::FOLIO)->first();

        if (! $report) {
            return;
        }

        $details = json_decode((string) $report->details, true, flags: JSON_THROW_ON_ERROR);
        $details['summary'] = $summary;

        DB::table('technical_support_reports')
            ->where('folio', self::FOLIO)
            ->update([
                'details' => json_encode($details, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'updated_at' => now(),
            ]);

        $movement = DB::table('technical_support_report_movements')
            ->where('folio', self::FOLIO)
            ->where('action', 'created')
            ->orderByDesc('id')
            ->first();

        if (! $movement || ! $movement->report_snapshot) {
            return;
        }

        $snapshot = json_decode((string) $movement->report_snapshot, true, flags: JSON_THROW_ON_ERROR);
        $snapshot['details'] = $details;

        DB::table('technical_support_report_movements')
            ->where('id', $movement->id)
            ->update([
                'report_snapshot' => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                'updated_at' => now(),
            ]);
    }
};
