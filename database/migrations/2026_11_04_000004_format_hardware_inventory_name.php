<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const FOLIO = 'ST-20261104-0022';

    public function up(): void
    {
        $this->updateName('Moo Cuy Ángel Gabriel');
    }

    public function down(): void
    {
        $this->updateName('MOO CUY ANGEL GABRIEL');
    }

    private function updateName(string $name): void
    {
        $report = DB::table('technical_support_reports')->where('folio', self::FOLIO)->first();
        if (! $report) {
            return;
        }

        $supports = json_decode($report->supports, true) ?: [];
        if (isset($supports[0])) {
            $supports[0]['targets'] = [$name];
        }

        $details = json_decode($report->details, true) ?: [];
        foreach ($details['hardware_specs'] ?? [] as $index => $spec) {
            if (($spec['label'] ?? '') === 'Usuario del equipo') {
                $details['hardware_specs'][$index]['value'] = $name;
            }
        }

        DB::table('technical_support_reports')->where('id', $report->id)->update([
            'supports' => json_encode($supports, JSON_UNESCAPED_UNICODE),
            'details' => json_encode($details, JSON_UNESCAPED_UNICODE),
        ]);
    }
};
