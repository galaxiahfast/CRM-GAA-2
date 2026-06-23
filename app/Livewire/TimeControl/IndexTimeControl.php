<?php

namespace App\Livewire\TimeControl;

use App\Models\Customer;
use App\Models\SubService;
use App\Models\TimeEntry;
use App\Services\TimeControl\Exceptions\ActiveEntryException;
use App\Services\TimeControl\Exceptions\NoOrganizationalProfileException;
use App\Services\TimeControl\TimerService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class IndexTimeControl extends Component
{
    public ?int $customerId = null;
    public ?int $subServiceId = null;
    public string $description = ''; // <-- Nueva propiedad para la observación inicial

    public function mount(): void
    {
        abort_unless(Gate::allows('operate-time-tracking'), 403);
    }

    public function start(TimerService $timer): void
    {
        abort_unless(Gate::allows('operate-time-tracking'), 403);

        $this->validate([
            'customerId' => ['required', 'exists:customers,id'],
            'subServiceId' => ['required', 'exists:sub_services,id'],
            'description' => ['nullable', 'string', 'max:500'], // <-- Validación opcional
        ], [], [
            'customerId' => 'cliente',
            'subServiceId' => 'actividad',
        ]);

        try {
            // Pasamos la descripción al servicio (asegúrate de que tu TimerService reciba este 4to parámetro opcional)
            $timer->start(auth()->user(), $this->customerId, $this->subServiceId, $this->description);
            
            // Reseteamos el formulario completo
            $this->reset(['customerId', 'subServiceId', 'description']);
        } catch (ActiveEntryException|NoOrganizationalProfileException $e) {
            $this->addError('timer', $e->getMessage());
        }
    }

    public function pause(TimerService $timer): void
    {
        if ($entry = $this->ownedActiveEntry()) {
            $timer->pause($entry);
        }
    }

    public function resume(TimerService $timer): void
    {
        if ($entry = $this->ownedActiveEntry()) {
            $timer->resume($entry);
        }
    }

    public function finish(TimerService $timer): void
    {
        if ($entry = $this->ownedActiveEntry()) {
            $timer->finish($entry);
        }
    }

    private function ownedActiveEntry(): ?TimeEntry
    {
        abort_unless(Gate::allows('operate-time-tracking'), 403);

        return TimeEntry::where('user_id', auth()->id())
            ->whereIn('status', [TimeEntry::STATUS_IN_PROGRESS, TimeEntry::STATUS_PAUSED])
            ->with('intervals')
            ->latest('id')
            ->first();
    }

    public function render()
    {
        $active = TimeEntry::where('user_id', auth()->id())
            ->whereIn('status', [TimeEntry::STATUS_IN_PROGRESS, TimeEntry::STATUS_PAUSED])
            ->with(['intervals', 'customer', 'subService'])
            ->latest('id')
            ->first();

        $accumulatedSeconds = 0;
        $openStartedAt = null;

        if ($active) {
            foreach ($active->intervals as $interval) {
                if ($interval->ended_at) {
                    $accumulatedSeconds += max(0, $interval->ended_at->diffInSeconds($interval->started_at, true));
                } else {
                    $openStartedAt = $interval->started_at;
                }
            }
        }

        $todayEntries = TimeEntry::where('user_id', auth()->id())
            ->whereDate('entry_date', now()->toDateString())
            ->with(['customer', 'subService'])
            ->latest('id')
            ->get();

        // Mapeamos los clientes para que Alpine los maneje fácilmente en formato JSON estructurado
        $customers = $active
            ? collect()
            : Customer::orderBy('name')
                ->get(['id', 'name', 'last_name'])
                ->map(fn($c) => [
                    'id' => $c->id,
                    'search_name' => mb_strtolower(trim($c->name . ' ' . $c->last_name))
                ]);

        // Modifica esta consulta dentro del método render()
        $subServices = $active
            ? collect()
            : SubService::orderBy('sub_service')
                ->get(['id', 'sub_service'])
                ->map(fn($s) => [
                    'id' => $s->id,
                    'search_name' => mb_strtolower(trim($s->sub_service))
                ]);

        return view('livewire.time-control.index-time-control', [
            'active' => $active,
            'accumulatedSeconds' => $accumulatedSeconds,
            'openStartedAt' => $openStartedAt,
            'customers' => $customers,
            'subServices' => $subServices,
            'todayEntries' => $todayEntries,
            'todayTotalSeconds' => (int) $todayEntries->sum('total_duration_seconds'),
        ])->layout('layouts.app');
    }
}