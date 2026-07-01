<?php

namespace App\Livewire\TimeControl\Admin;

use Livewire\Component;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceManagement extends Component
{
    // Filtros
    public $searchCollaborator = '';
    public $userId = null;
    public $from;
    public $to;

    // Estado del Modal
    public $showAttendanceModal = false;
    public $selectedRecordId;
    public $selectedEmployeeName = '';
    public $selectedRecordDate = '';
    
    // Formulario del Modal
    public $modalDecimalHours = 0;
    public $modalHourlyRate = 100; // Puedes cambiar el base o jalarlo del puesto/usuario
    public $modalFoodAllowance = 0;
    public $modalExtraBonus = 0;
    public $modalNotes = '';

    public function mount()
    {
        // Rango inicial de la interfaz
        $this->from = '2026-06-25';
        $this->to = '2026-07-01';
    }

    public function clearCollaborator()
    {
        $this->userId = null;
        $this->searchCollaborator = '';
    }

    /**
     * 🔥 Se activa al presionar "Ajustar"
     */
    public function editRow($id)
    {
        $this->selectedRecordId = $id;
        
        $user = User::find($this->userId);
        $this->selectedEmployeeName = $user ? trim($user->name . ' ' . $user->last_name) : 'Colaborador';
        
        // Buscamos la jornada directo en la tabla real
        $entry = DB::table('time_entries')->where('id', $id)->first();

        if ($entry) {
            // Convertimos los segundos almacenados a horas decimales para el modal
            $this->modalDecimalHours = round(($entry->total_duration_seconds ?? 0) / 3600, 2);
            $this->modalHourlyRate = $entry->hourly_rate ?? 100.00; // Asegúrate si tienes esta columna, si no usa el default
            $this->modalFoodAllowance = $entry->food_allowance ?? (($this->modalDecimalHours > 6) ? 50.00 : 0.00);
            $this->modalExtraBonus = $entry->bonus ?? 0.00;
            $this->modalNotes = $entry->bonus_reason ?? '';
        }

        $this->showAttendanceModal = true;
    }

    public function closeModal()
    {
        $this->showAttendanceModal = false;
    }

    /**
     * Guardar los cambios del Modal
     */
    public function saveAdjustment()
    {
        $this->validate([
            'modalDecimalHours' => 'required|numeric|min:0',
            'modalHourlyRate'   => 'required|numeric|min:0',
            'modalFoodAllowance'=> 'required|numeric',
            'modalExtraBonus'   => 'required|numeric|min:0',
        ]);

        // Convertimos las horas decimales de regreso a segundos para tu columna 'total_duration_seconds'
        $newSeconds = htmlspecialchars($this->modalDecimalHours) * 3600;

        DB::table('time_entries')
            ->where('id', $this->selectedRecordId)
            ->update([
                'total_duration_seconds' => $newSeconds,
                // Agrega estas columnas si cuentas con ellas en time_entries, si no, puedes omitirlas o manejarlas en metadatos:
                'bonus'                  => $this->modalExtraBonus,
                'bonus_reason'           => $this->modalNotes,
                'updated_at'             => Carbon::now()
            ]);

        $this->showAttendanceModal = false;
        session()->flash('message', 'Jornada actualizada correctamente.');
    }

    public function render()
    {
        $users = User::select('id', 'name', 'last_name')->get();
        $attendanceRecords = [];

        if ($this->userId) {
            // Traemos las jornadas de la tabla time_entries usando tus columnas reales
            $entries = DB::table('time_entries')
                ->where('user_id', $this->userId)
                ->whereBetween('entry_date', [$this->from, $this->to])
                ->orderBy('entry_date', 'desc')
                ->get();

            foreach ($entries as $entry) {
                $totalSeconds = $entry->total_duration_seconds ?? 0;

                // 1. Convertir segundos a formato legible: 00h 00m 00s
                $hours = intdiv($totalSeconds, 3600);
                $minutes = intdiv($totalSeconds % 3600, 60);
                $seconds = $totalSeconds % 60;
                $netTimeLabel = sprintf('%02dh %02dm %02ds', $hours, $minutes, $seconds);

                // 2. Horas decimales
                $decimalHours = round($totalSeconds / 3600, 2);

                //  Por el nombre correcto. Intenta primero con 'time_intervals':
                $intervals = DB::table('time_intervals')
                    ->where('time_entry_id', $entry->id)
                    ->orderBy('started_at', 'asc')
                    ->get();

                $punchesArray = [];
                foreach ($intervals as $interval) {
                    if ($interval->started_at) {
                        $punchesArray[] = Carbon::parse($interval->started_at)->format('H:i:s');
                    }
                    if ($interval->ended_at) {
                        $punchesArray[] = Carbon::parse($interval->ended_at)->format('H:i:s');
                    }
                }
                $punchesString = count($punchesArray) > 0 ? implode(', ', $punchesArray) : '—';

                // 4. Cálculos Financieros
                $hourlyRate = $entry->hourly_rate ?? 100.00; 
                $foodAllowance = $entry->food_allowance ?? (($decimalHours > 6) ? 50.00 : 0.00);
                $bonus = $entry->bonus ?? 0.00;
                $pagoBase = $decimalHours * $hourlyRate;

                $attendanceRecords[] = (object)[
                    'id'             => $entry->id,
                    'date'           => Carbon::parse($entry->entry_date)->format('d/m/Y'),
                    'punches'        => $punchesString,
                    'net_time_label' => $netTimeLabel,
                    'decimal_hours'  => $decimalHours,
                    'hourly_rate'    => $hourlyRate,
                    'food_allowance' => $foodAllowance,
                    'bonus'          => $bonus,
                    'pago_base'      => $pagoBase
                ];
            }
        }

        return view('livewire.time-control.admin.attendance-management', [
            'users' => $users,
            'attendanceRecords' => $attendanceRecords
        ])->layout('layouts.app');
    }
}