<?php

namespace App\Livewire\TimeControl\Admin;

use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ActiveTimers extends Component
{
    /** @var array<int, array<string, mixed>> */
    public array $activeTimers = [];

    public bool $showingDemoTimers = false;

    public function mount(): void
    {
        abort_unless(Gate::allows('view-time-admin'), 403);

        $this->refreshActiveTimers();
    }

    public function refreshActiveTimers(): void
    {
        $now = Carbon::now($this->timezone());

        $this->showingDemoTimers = false;

        $this->activeTimers = TimeEntry::query()
            ->where('status', TimeEntry::STATUS_IN_PROGRESS)
            ->whereHas('intervals', fn ($query) => $query->whereNull('ended_at'))
            ->with([
                'customer:id,name',
                'subService:id,sub_service',
                'jobPositionSnapshot:id,name',
                'physicalAreaSnapshot:id,name',
                'intervals:id,time_entry_id,started_at,ended_at',
                'user:id,name,last_name,email,profile_photo_path',
                'user.superiors:id,name,last_name',
                'user.subordinates:id,name,last_name',
            ])
            ->get()
            ->sortBy(fn (TimeEntry $entry): string => $this->userName($entry->user))
            ->map(function (TimeEntry $entry) use ($now): array {
                $user = $entry->user;
                $openInterval = $entry->intervals
                    ->whereNull('ended_at')
                    ->sortByDesc('started_at')
                    ->first();

                return [
                    'id' => (int) $entry->id,
                    'user_id' => (int) $entry->user_id,
                    'name' => $this->userName($user),
                    'email' => $user?->email ?? 'Correo no disponible',
                    'initials' => $this->initials($this->userName($user)),
                    'photo_url' => $user ? $this->profilePhotoUrl($user) : null,
                    'activity' => $entry->subService?->sub_service ?? 'Actividad no disponible',
                    'customer' => $entry->customer?->name ?? 'Sin cliente asignado',
                    'position' => $entry->jobPositionSnapshot?->name ?? 'Sin puesto asignado',
                    'area' => $entry->physicalAreaSnapshot?->name ?? 'Sin área asignada',
                    'elapsed_seconds' => $this->elapsedSeconds($entry, $now),
                    'started_at' => $openInterval
                        ? Carbon::parse((string) $openInterval->getRawOriginal('started_at'), $this->timezone())->format('H:i')
                        : '—',
                    'superiors' => $user?->superiors
                        ->map(fn (User $superior): array => [
                            'id' => (int) $superior->id,
                            'name' => $this->userName($superior),
                        ])->values()->all() ?? [],
                    'subordinates' => $user?->subordinates
                        ->map(fn (User $subordinate): array => [
                            'id' => (int) $subordinate->id,
                            'name' => $this->userName($subordinate),
                        ])->values()->all() ?? [],
                ];
            })
            ->values()
            ->all();

        if ($this->activeTimers === [] && app()->environment('local')) {
            $this->activeTimers = $this->localDemoTimers($now);
            $this->showingDemoTimers = true;
        }

        $this->dispatch('active-timers-refreshed');
    }

    public function render(): View
    {
        return view('livewire.time-control.admin.active-timers')->layout('layouts.app');
    }

    private function userName(?User $user): string
    {
        if (! $user) {
            return 'Usuario no disponible';
        }

        return trim($user->name.' '.($user->last_name ?? '')) ?: 'Usuario';
    }

    private function initials(string $name): string
    {
        return collect(preg_split('/\s+/', trim($name)))
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('') ?: '?';
    }

    private function profilePhotoUrl(User $user): ?string
    {
        if (! $user->profile_photo_path) {
            return null;
        }

        if (config('filesystems.disks.public.driver') === 'local') {
            return url('/storage/'.ltrim($user->profile_photo_path, '/'));
        }

        return $user->profile_photo_url;
    }

    private function elapsedSeconds(TimeEntry $entry, Carbon $now): int
    {
        return (int) $entry->intervals->sum(function ($interval) use ($now): int {
            $startedAt = Carbon::parse((string) $interval->getRawOriginal('started_at'), $this->timezone());
            $endedAt = $interval->getRawOriginal('ended_at')
                ? Carbon::parse((string) $interval->getRawOriginal('ended_at'), $this->timezone())
                : $now;

            return (int) $startedAt->diffInSeconds($endedAt, true);
        });
    }

    /** @return array<int, array<string, mixed>> */
    private function localDemoTimers(Carbon $now): array
    {
        return [
            [
                'id' => -1,
                'user_id' => -1,
                'name' => 'Alejandra Medina',
                'email' => 'alejandra.medina@ejemplo.local',
                'initials' => 'AM',
                'photo_url' => null,
                'activity' => 'Conciliación bancaria',
                'customer' => 'Comercializadora del Caribe',
                'position' => 'Auxiliar de Auditoría',
                'area' => 'Contabilidad',
                'elapsed_seconds' => 8137,
                'started_at' => $now->copy()->subSeconds(8137)->format('H:i'),
                'superiors' => [['id' => -11, 'name' => 'María Fernández']],
                'subordinates' => [],
            ],
            [
                'id' => -2,
                'user_id' => -2,
                'name' => 'Carlos Jesús Campos',
                'email' => 'carlos.campos@ejemplo.local',
                'initials' => 'CC',
                'photo_url' => null,
                'activity' => 'Revisión de pólizas contables',
                'customer' => 'Grupo Contable Mérida',
                'position' => 'Contador Senior',
                'area' => 'Contabilidad',
                'elapsed_seconds' => 5422,
                'started_at' => $now->copy()->subSeconds(5422)->format('H:i'),
                'superiors' => [['id' => -12, 'name' => 'Laura Méndez']],
                'subordinates' => [['id' => -21, 'name' => 'Diego Cen']],
            ],
            [
                'id' => -3,
                'user_id' => -3,
                'name' => 'Diego Cen',
                'email' => 'diego.cen@ejemplo.local',
                'initials' => 'DC',
                'photo_url' => null,
                'activity' => 'Captura de movimientos',
                'customer' => 'Administradora Peninsular',
                'position' => 'Becario Contable',
                'area' => 'Contabilidad',
                'elapsed_seconds' => 2984,
                'started_at' => $now->copy()->subSeconds(2984)->format('H:i'),
                'superiors' => [['id' => -13, 'name' => 'Carlos Jesús Campos']],
                'subordinates' => [],
            ],
            [
                'id' => -4,
                'user_id' => -4,
                'name' => 'Maydeli Lisseth Hernández',
                'email' => 'maydeli.hernandez@ejemplo.local',
                'initials' => 'MH',
                'photo_url' => null,
                'activity' => 'Cálculo y revisión de impuestos',
                'customer' => 'Consultoría Fiscal del Mayab',
                'position' => 'Auxiliar de Nómina',
                'area' => 'Contabilidad',
                'elapsed_seconds' => 10451,
                'started_at' => $now->copy()->subSeconds(10451)->format('H:i'),
                'superiors' => [['id' => -14, 'name' => 'Dionicio Farfán']],
                'subordinates' => [],
            ],
        ];
    }

    private function timezone(): string
    {
        return (string) config('time-control.timezone', 'America/Mexico_City');
    }
}
