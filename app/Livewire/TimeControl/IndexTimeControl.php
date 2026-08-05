<?php

namespace App\Livewire\TimeControl;

use App\Models\Customer;
use App\Models\SubService;
use App\Models\TimeEntry;
use App\Services\TimeControl\Exceptions\ActiveEntryException;
use App\Services\TimeControl\Exceptions\NoOrganizationalProfileException;
use App\Services\TimeControl\TimerService;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Component;

class IndexTimeControl extends Component
{
    public ?int $customerId = null;

    public ?int $subServiceId = null;

    public string $description = '';

    public function mount(): void
    {
        if (auth()->user()->isAdmin()) {
            $this->redirect('/time/admin', navigate: false);
        }
    }

    public function start(TimerService $timer): void
    {
        $this->validate([
            'customerId' => [
                'required',
                Rule::exists('customers', 'id')->whereNull('deleted_at'),
            ],
            'subServiceId' => ['required', 'exists:sub_services,id'],
            'description' => ['nullable', 'string', 'max:500'],
        ], [], [
            'customerId' => 'cliente',
            'subServiceId' => 'actividad',
        ]);

        try {
            $timer->playToday(auth()->user(), $this->customerId, $this->subServiceId);
            $this->reset(['customerId', 'subServiceId', 'description']);
        } catch (ActiveEntryException|NoOrganizationalProfileException $e) {
            $this->addError('timer', $e->getMessage());
        }
    }

    public function pause(TimerService $timer, int $entryId): void
    {
        if ($entry = $this->ownedTrackableEntry($entryId)) {
            $timer->pause($entry);
        }
    }

    public function resume(TimerService $timer, int $entryId): void
    {
        if (! $entry = $this->ownedTrackableEntry($entryId)) {
            return;
        }

        if ($entry->status === TimeEntry::STATUS_AUTO_CLOSED) {
            $this->addError('timer', 'Esta actividad fue cerrada automaticamente y no puede reanudarse.');

            return;
        }

        try {
            $timer->switchTo(auth()->user(), $entry);
        } catch (ActiveEntryException|NoOrganizationalProfileException $e) {
            $this->addError('timer', $e->getMessage());
        }
    }

    private function ownedTrackableEntry(int $entryId): ?TimeEntry
    {
        return TimeEntry::where('user_id', auth()->id())
            ->where('id', $entryId)
            ->with('intervals')
            ->first();
    }

    public function render()
    {
        $active = TimeEntry::where('user_id', auth()->id())
            ->where('status', TimeEntry::STATUS_IN_PROGRESS)
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
            ->whereDate('entry_date', $this->localToday())
            ->with(['customer', 'subService', 'intervals'])
            ->latest('id')
            ->get();

        $customers = Customer::orderBy('name')
            ->whereNull('deleted_at')
            ->get(['id', 'name', 'last_name'])
            ->map(fn ($c) => [
                'id' => $c->id,
                'search_name' => mb_strtolower(trim($c->name.' '.$c->last_name)),
            ]);

        $subServices = SubService::orderBy('sub_service')
            ->get(['id', 'sub_service'])
            ->map(fn ($s) => [
                'id' => $s->id,
                'search_name' => mb_strtolower(trim($s->sub_service)),
            ]);

        $todayTotalSeconds = (int) $todayEntries->sum(fn (TimeEntry $entry) => $entry->calculateEffectiveSeconds());

        return view('livewire.time-control.index-time-control', [
            'active' => $active,
            'accumulatedSeconds' => $accumulatedSeconds,
            'openStartedAt' => $openStartedAt,
            'customers' => $customers,
            'subServices' => $subServices,
            'todayEntries' => $todayEntries,
            'todayTotalSeconds' => $todayTotalSeconds,
        ])->layout('layouts.app');
    }

    private function localToday(): string
    {
        return Carbon::now($this->moduleTimezone())->toDateString();
    }

    private function moduleTimezone(): string
    {
        $timezone = (string) config('app.timezone', 'America/Mexico_City');

        return $timezone === 'UTC' ? 'America/Mexico_City' : $timezone;
    }
}
