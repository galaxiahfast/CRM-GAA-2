<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->targetsByFolio() as $folio => $targets) {
            $report = DB::table('technical_support_reports')->where('folio', $folio)->first();

            if (! $report) {
                continue;
            }

            $supports = json_decode((string) $report->supports, true, flags: JSON_THROW_ON_ERROR);

            $supports = array_map(function (array $support) use ($targets): array {
                $support['targets'] = $targets;

                return $support;
            }, $supports);

            DB::table('technical_support_reports')
                ->where('folio', $folio)
                ->update([
                    'supports' => json_encode($supports, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        foreach (array_keys($this->targetsByFolio()) as $folio) {
            $report = DB::table('technical_support_reports')->where('folio', $folio)->first();

            if (! $report) {
                continue;
            }

            $supports = json_decode((string) $report->supports, true, flags: JSON_THROW_ON_ERROR);

            $supports = array_map(function (array $support): array {
                unset($support['targets']);

                return $support;
            }, $supports);

            DB::table('technical_support_reports')
                ->where('folio', $folio)
                ->update([
                    'supports' => json_encode($supports, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                    'updated_at' => now(),
                ]);
        }
    }

    private function targetsByFolio(): array
    {
        return [
            'ST-20261022-0004' => ['Noh Chan Claudia Alejandra'],
            'ST-20261022-0005' => ['Perla Naomy Rivero Couoh', 'Josue Ricardo Puc Pech'],
            'ST-20261023-0006' => ['Victor Gabriel Tapia Hernandez'],
            'ST-20261023-0007' => ['Sanchez Coba Caridad Iliana'],
        ];
    }
};
