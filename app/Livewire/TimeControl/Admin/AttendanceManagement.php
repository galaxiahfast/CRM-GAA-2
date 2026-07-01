<?php

namespace App\Livewire\TimeControl\Admin;

use Livewire\Component;
use App\Models\User;
use App\Models\TimeEntry; // Tu modelo de asistencia / checador
use Carbon\Carbon;

class AttendanceManagement extends Component
{
    // Propiedades reactivas para filtros
    public $searchCollaborator = '';
    public $userId = null;
    public $from;
    public $to;

    // Propiedades para el control del Modal de Ajustes
    public $showAttendanceModal = false;
    public $selectedRecordId;
    public $selectedEmployeeName = '';
    public $selectedRecordDate = '';
    
    // Campos del Formulario dentro del Modal
    public $modalDecimalHours;
    public $modalHourlyRate;
    public $modalFoodAllowance;
    public $modalExtraBonus;
    public $modalNotes;

    /**
     * Inicialización de fechas por defecto (Mes actual o día en curso)
     */
    public function mount()
    {
        $this->from = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->to = Carbon::now()->format('Y-m-d');
    }

    /**
     * Limpia el ID del colaborador si borran el buscador
     */
    public function clearCollaborator()
    {
        $this->userId = null;
    }

    /**
     * Abre el modal y precarga los datos actuales del registro biométrico seleccionado
     */
    public function editRow($id)
    {
        $record = TimeEntry::with('user')->find($id);
        
        if ($record) {
            $this->selectedRecordId = $record->id;
            $this->selectedEmployeeName = trim(($record->user->name ?? '') . ' ' . ($record->user->last_name ?? ''));
            $this->selectedRecordDate = $record->date ?? $record->entry_date;
            
            // Precarga de valores numéricos y monetarios
            $this->modalDecimalHours = $record->decimal_hours;
            $this->modalHourlyRate = $record->hourly_rate ?? 0;
            $this->modalFoodAllowance = number_format($record->food_allowance ?? 0, 2, '.', '');
            $this->modalExtraBonus = $record->bonus ?? $record->extra_bonus ?? 0;
            $this->modalNotes = $record->notes ?? '';

            $this->showAttendanceModal = true;
        }
    }

    /**
     * Procesa y guarda las horas editadas, el precio por hora y los bonos
     */
    public function updateAttendanceRecord()
    {
        $this->validate([
            'modalDecimalHours' => 'required|numeric|min:0',
            'modalHourlyRate'   => 'required|numeric|min:0',
            'modalFoodAllowance'=> 'required|in:0.00,50.00',
            'modalExtraBonus'   => 'nullable|numeric|min:0',
            'modalNotes'        => 'nullable|string|max:255',
        ]);

        $record = TimeEntry::find($this->selectedRecordId);
        if ($record) {
            // Guardamos los ajustes aplicados por el administrador
            $record->decimal_hours = $this->modalDecimalHours;
            $record->hourly_rate = $this->modalHourlyRate;
            $record->food_allowance = $this->modalFoodAllowance;
            
            // Validamos compatibilidad con el nombre de columna exacto en tu migración (bonus o extra_bonus)
            if (array_key_exists('bonus', $record->getAttributes())) {
                $record->bonus = $this->modalExtraBonus ?: 0;
            } else {
                $record->extra_bonus = $this->modalExtraBonus ?: 0;
            }

            $record->notes = $this->modalNotes;
            $record->save();

            $this->showAttendanceModal = false;
            session()->flash('success', 'La jornada e incentivos fueron actualizados correctamente.');
        }
    }

    public function render()
    {
        // 1. Colección de usuarios para el buscador Alpine.js (Excluimos administradores si es necesario)
        $users = User::select('id', 'name', 'last_name')->get(); 

        // 2. Query base con relación cargada para optimizar consultas a la base de datos
        $query = TimeEntry::with('user');

        // 3. Filtrado por el colaborador seleccionado en el buscador inteligente
        if ($this->userId) {
            $query->where('user_id', $this->userId);
        }

        // 4. Filtrado por rango de fechas (Usa la columna 'entry_date' detectada en tu log de base de datos)
        if ($this->from && $this->to) {
            $query->whereBetween('entry_date', [$this->from, $this->to]);
        }

        $attendanceRecords = $query->orderBy('entry_date', 'desc')->get();

        // 5. Retornamos la vista inyectando el Layout oficial de Jetstream
        return view('livewire.time-control.admin.attendance-management', [
            'users' => $users,
            'attendanceRecords' => $attendanceRecords
        ])->layout('layouts.app');
    }
}