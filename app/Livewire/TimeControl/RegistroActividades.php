<?php

namespace App\Livewire\TimeControl;

use App\Models\TimeEntry;
use App\Services\ReferenceDataCache;
use App\Services\Reports\ReportExportManager;
use App\Services\TimeControl\Exceptions\ActiveEntryException;
use App\Services\TimeControl\Exceptions\NoOrganizationalProfileException;
use App\Services\TimeControl\TimeReportService;
use App\Services\TimeControl\TimerService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RegistroActividades extends Component
{
    public ?int $customerId = null;

    public ?int $subServiceId = null;

    public string $description = '';

    public bool $showDeleteModal = false;

    public ?int $deleteEntryId = null;

    public string $deleteActivityName = '';

    public string $deleteConfirmation = '';

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

    public function downloadPdf(TimeReportService $reports, ReportExportManager $exporter): StreamedResponse
    {
        $today = $this->localToday();
        $report = $reports->userReport(auth()->user(), $today, $today);

        return $exporter->download('pdf', $report);
    }

    public function requestDeletion(int $entryId): void
    {
        $this->resetValidation('deleteConfirmation');

        $entry = TimeEntry::query()
            ->where('user_id', auth()->id())
            ->with('subService:id,sub_service')
            ->withCount('audits')
            ->find($entryId);

        if (! $entry) {
            $this->addError('timer', 'La actividad no existe o no te pertenece.');

            return;
        }

        if ($entry->status === TimeEntry::STATUS_IN_PROGRESS) {
            $this->addError('timer', 'No puedes eliminar una actividad en progreso. Primero debes pausarla.');

            return;
        }

        if ($entry->status === TimeEntry::STATUS_AUTO_CLOSED) {
            $this->addError('timer', 'No puedes eliminar una actividad cerrada automáticamente.');

            return;
        }

        if ($entry->audits_count > 0) {
            $this->addError('timer', 'No puedes eliminar una actividad que fue corregida por un administrador.');

            return;
        }

        $this->deleteEntryId = $entry->id;
        $this->deleteActivityName = $entry->subService?->sub_service ?? 'Actividad sin nombre';
        $this->deleteConfirmation = '';
        $this->showDeleteModal = true;
    }

    public function cancelDeletion(): void
    {
        $this->reset(['showDeleteModal', 'deleteEntryId', 'deleteActivityName', 'deleteConfirmation']);
        $this->resetValidation('deleteConfirmation');
    }

    public function deleteEntry(): void
    {
        $this->validate([
            'deleteConfirmation' => ['required', 'string', 'max:255'],
        ], [], [
            'deleteConfirmation' => 'nombre de la actividad',
        ]);

        $error = DB::transaction(function (): ?string {
            $entry = TimeEntry::query()
                ->where('user_id', auth()->id())
                ->with('subService:id,sub_service')
                ->withCount('audits')
                ->lockForUpdate()
                ->find($this->deleteEntryId);

            if (! $entry) {
                return 'La actividad ya no existe o no te pertenece.';
            }

            if ($entry->status === TimeEntry::STATUS_IN_PROGRESS) {
                return 'La actividad está en progreso. Debes pausarla antes de eliminarla.';
            }

            if ($entry->status === TimeEntry::STATUS_AUTO_CLOSED) {
                return 'Las actividades cerradas automáticamente no se pueden eliminar.';
            }

            if ($entry->audits_count > 0) {
                return 'Las actividades corregidas por un administrador no se pueden eliminar.';
            }

            $activityName = $entry->subService?->sub_service ?? 'Actividad sin nombre';

            if (! hash_equals($activityName, trim($this->deleteConfirmation))) {
                return 'Escribe exactamente el nombre de la actividad para confirmar.';
            }

            $entry->delete();

            return null;
        });

        if ($error) {
            $this->addError('deleteConfirmation', $error);

            return;
        }

        $this->cancelDeletion();
    }

    /** @param array<int, array{value:mixed}> $orderedItems */
    public function updateActivityOrder(array $orderedItems): void
    {
        $requestedIds = collect($orderedItems)
            ->pluck('value')
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($requestedIds->isEmpty()) {
            return;
        }

        $ownedIds = TimeEntry::query()
            ->where('user_id', auth()->id())
            ->whereDate('entry_date', $this->localToday())
            ->whereIn('id', $requestedIds)
            ->pluck('id');

        $safeOrder = $requestedIds
            ->filter(fn (int $id) => $ownedIds->contains($id))
            ->values();

        DB::transaction(function () use ($safeOrder): void {
            foreach ($safeOrder as $index => $entryId) {
                TimeEntry::query()
                    ->where('user_id', auth()->id())
                    ->whereDate('entry_date', $this->localToday())
                    ->whereKey($entryId)
                    ->update(['sort_order' => $index + 1]);
            }
        });
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
            ->orderByRaw('CASE WHEN sort_order = 0 THEN 0 ELSE 1 END')
            ->orderBy('sort_order')
            ->latest('id')
            ->get();

        $catalogs = app(ReferenceDataCache::class)->timeControl();

        $todayTotalSeconds = (int) $todayEntries->sum(fn (TimeEntry $entry) => $entry->calculateEffectiveSeconds());

        return view('livewire.time-control.registro-actividades', [
            'active' => $active,
            'accumulatedSeconds' => $accumulatedSeconds,
            'openStartedAt' => $openStartedAt,
            'customers' => $catalogs['customers'],
            'subServices' => $catalogs['subServices'],
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
