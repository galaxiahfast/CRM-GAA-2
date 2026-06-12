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

    public function mount(): void
    {
        // El Administrador no puede operar el cronómetro (reglas 4.1 / 8.8).
        abort_unless(Gate::allows('operate-time-tracking'), 403);
    }

    public function start(TimerService $timer): void
    {
        abort_unless(Gate::allows('operate-time-tracking'), 403);

        $this->validate([
            'customerId' => ['required', 'exists:customers,id'],
            'subServiceId' => ['required', 'exists:sub_services,id'],
        ], [], [
            'customerId' => 'cliente',
            'subServiceId' => 'actividad',
        ]);

        try {
            $timer->start(auth()->user(), $this->customerId, $this->subServiceId);
            $this->reset(['customerId', 'subServiceId']);
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

    /** Recupera la entrada activa garantizando que pertenece al usuario. */
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

        // Los catálogos solo alimentan el formulario de inicio; evitamos
        // consultarlos cuando ya hay un cronómetro activo (form oculto).
        $customers = $active
            ? collect()
            : Customer::orderBy('name')->get(['id', 'name', 'last_name']);
        $subServices = $active
            ? collect()
            : SubService::orderBy('sub_service')->get(['id', 'sub_service']);

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
 