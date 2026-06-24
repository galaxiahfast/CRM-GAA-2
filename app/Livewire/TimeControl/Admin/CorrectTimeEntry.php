<?php

namespace App\Livewire\TimeControl\Admin;

use App\Models\TimeEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class CorrectTimeEntry extends Component
{
    public ?int $editingId = null;

    /** @var array<int, array{id:int, started_at:string, ended_at:?string}> */
    public array $intervals = [];

    public string $reason = '';

    public function mount(): void
    {
        abort_unless(Gate::allows('correct-time-tracking'), 403);
    }

    public function edit(int $id): void
    {
        abort_unless(Gate::allows('correct-time-tracking'), 403);

        $entry = TimeEntry::with('intervals')->findOrFail($id);

        $this->editingId = $entry->id;
        $this->reason = '';
        $this->intervals = $entry->intervals->map(fn ($i) => [
            'id' => $i->id,
            'started_at' => optional($i->started_at)->format('Y-m-d\TH:i'),
            'ended_at' => optional($i->ended_at)?->format('Y-m-d\TH:i'),
        ])->values()->all();
    }

    public function cancel(): void
    {
        $this->reset(['editingId', 'intervals', 'reason']);
    }

    public function save(): void
    {
        abort_unless(Gate::allows('correct-time-tracking'), 403);

        $this->validate([
            'reason' => ['required', 'string', 'min:5'],
            'intervals.*.started_at' => ['required', 'date'],
            'intervals.*.ended_at' => ['nullable', 'date', 'after:intervals.*.started_at'],
        ], [], [
            'reason' => 'motivo',
        ]);

        $entry = TimeEntry::with('intervals')->findOrFail($this->editingId);

        // Snapshot inmutable del estado ANTES del cambio.
        $oldValues = $this->snapshot($entry);

        // 💡 SOLUCIÓN: Agregamos '$oldValues' al operador 'use' para heredar la variable dentro de la clausura
        DB::transaction(function () use ($entry, $oldValues) {
            foreach ($this->intervals as $row) {
                $interval = $entry->intervals->firstWhere('id', $row['id']);
                if (! $interval) {
                    continue;
                }
                $interval->started_at = Carbon::parse($row['started_at']);
                $interval->ended_at = $row['ended_at'] ? Carbon::parse($row['ended_at']) : null;
                $interval->save();
            }

            $entry->load('intervals');
            $entry->total_duration_seconds = $entry->calculateEffectiveSeconds();
            $entry->save();

            $entry->audits()->create([
                'admin_id' => auth()->id(),
                'old_values' => $oldValues,
                'new_values' => $this->snapshot($entry->fresh('intervals')),
                'reason' => $this->reason,
            ]);
        });

        $this->cancel();
        session()->flash('success', 'Registro corregido y auditado correctamente.');
    }

    private function snapshot(TimeEntry $entry): array
    {
        return [
            'total_duration_seconds' => (int) $entry->total_duration_seconds,
            'status' => (int) $entry->status,
            'intervals' => $entry->intervals->map(fn ($i) => [
                'id' => $i->id,
                'started_at' => optional($i->started_at)->toDateTimeString(),
                'ended_at' => optional($i->ended_at)?->toDateTimeString(),
            ])->values()->all(),
        ];
    }

    public function render()
    {
        $entries = TimeEntry::whereIn('status', [
            TimeEntry::STATUS_FINISHED,
            TimeEntry::STATUS_AUTO_CLOSED,
        ])
            ->with(['user', 'customer', 'subService'])
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $editing = $this->editingId
            ? TimeEntry::with(['audits.admin', 'user', 'customer', 'subService'])->find($this->editingId)
            : null;

        return view('livewire.time-control.admin.corrections', [
            'entries' => $entries,
            'editing' => $editing,
        ])->layout('layouts.app');
    }
}