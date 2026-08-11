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

    public string $lastUpdatedAt = '';

    public function mount(): void
    {
        abort_unless(Gate::allows('view-time-admin'), 403);

        $this->refreshActiveTimers();
    }

    public function refreshActiveTimers(): void
    {
        $now = Carbon::now($this->timezone());

        $this->activeTimers = TimeEntry::query()
            ->where('status', TimeEntry::STATUS_IN_PROGRESS)
            ->whereHas('intervals', fn ($query) => $query->whereNull('ended_at'))
            ->with([
                'customer:id,name',
                'subService:id,sub_service',
                'intervals:id,time_entry_id,started_at,ended_at',
                'user:id,name,last_name,email,profile_photo_path',
                'user.superiors:id,name,last_name,email,profile_photo_path',
                'user.subordinates:id,name,last_name,email,profile_photo_path',
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

        $this->lastUpdatedAt = $now->format('H:i:s');
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

    private function timezone(): string
    {
        return (string) config('time-control.timezone', 'America/Mexico_City');
    }
}
