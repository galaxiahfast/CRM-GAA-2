<?php

namespace Tests\Unit;

use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceChangeHistoryPdfViewTest extends TestCase
{
    public function test_history_pdf_groups_each_change_into_four_balanced_columns(): void
    {
        $html = view('livewire.time-control.reports.attendance-change-history-pdf', [
            'changes' => [[
                'employee_name' => 'Jorge Armando Puc Dzib',
                'employee_id' => 'BT003',
                'date' => '08/10/2026',
                'changed_at' => '08/10/2026 16:10',
                'admin_name' => 'Administrador',
                'comment' => 'Se corrigió una marca.',
                'marks_before' => ['09:10:00', '16:00:00'],
                'marks_after' => ['09:00:00', '16:00:00'],
                'hourly_rate_before' => 25,
                'hourly_rate_after' => 30,
                'bonus_before' => 50,
                'bonus_after' => 50,
                'extra_bonus_before' => 0,
                'extra_bonus_after' => 100,
            ]],
            'isExample' => false,
            'generatedAt' => Carbon::parse('2026-10-09 12:00:00'),
        ])->render();

        $this->assertStringContainsString('colspan="4"', $html);
        $this->assertStringContainsString('width: 22%', $html);
        $this->assertStringContainsString('width: 31%', $html);
        $this->assertStringContainsString('Colaborador', $html);
        $this->assertStringContainsString('Cambio realizado', $html);
        $this->assertStringContainsString('Valores actualizados', $html);
        $this->assertStringContainsString('Registro', $html);
        $this->assertStringContainsString('Marcas anteriores:', $html);
        $this->assertStringContainsString('Marcas nuevas:', $html);
    }
}
