<?php

namespace App\Services\TimeControl;

use App\Models\TimeEntry;
use App\Models\User;
use App\Services\TimeControl\Exceptions\ActiveEntryException;
use App\Services\TimeControl\Exceptions\NoOrganizationalProfileException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Lógica central del cronómetro de Control de Horas.
 *
 * Centraliza el motor de registro de tiempo para que Livewire, la API y el
 * Scheduler compartan exactamente las mismas reglas de negocio.
 */
class TimerService
{
    /**
     * Inicia una nueva actividad para el usuario, congelando su contexto
     * organizacional actual (snapshot).
     *
     * @throws ActiveEntryException Si ya existe un cronómetro activo.
     * @throws NoOrganizationalProfileException Si el usuario no tiene perfil.
     */
    public function start(User $user, int $customerId, int $subServiceId): TimeEntry
    {
        if ($this->runningEntry($user)) {
            throw new ActiveEntryException;
        }

        $profile = $user->activeOrganizationalProfile()->first();

        if (! $profile) {
            throw new NoOrganizationalProfileException;
        }

        return DB::transaction(function () use ($user, $customerId, $subServiceId, $profile) {
            $now = $this->localNow();

            $entry = TimeEntry::create([
                'user_id' => $user->id,
                'customer_id' => $customerId,
                'sub_service_id' => $subServiceId,
                'role_id_snapshot' => $user->role_id,
                'job_position_id_snapshot' => $profile->job_position_id,
                'physical_area_id_snapshot' => $profile->physical_area_id,
                'entry_date' => $now->toDateString(),
                'status' => TimeEntry::STATUS_IN_PROGRESS,
                'total_duration_seconds' => 0,
            ]);

            $entry->intervals()->create(['started_at' => $now]);

            return $entry;
        });
    }

    /**
     * Inicia la actividad del dia o reanuda la fila ya existente para esa
     * combinacion de cliente + actividad, pausando cualquier otro cronometro.
     */
    public function playToday(User $user, int $customerId, int $subServiceId): TimeEntry
    {
        return DB::transaction(function () use ($user, $customerId, $subServiceId) {
            $today = $this->localNow()->toDateString();
            $entry = TimeEntry::where('user_id', $user->id)
                ->whereDate('entry_date', $today)
                ->where('customer_id', $customerId)
                ->where('sub_service_id', $subServiceId)
                ->with('intervals')
                ->first();

            if ($entry) {
                return $this->switchTo($user, $entry);
            }

            if ($running = $this->runningEntry($user)) {
                $this->pause($running);
            }

            return $this->start($user, $customerId, $subServiceId);
        });
    }

    /** Pausa la actividad: cierra el intervalo abierto y recalcula. */
    public function pause(TimeEntry $entry): TimeEntry
    {
        if ($entry->status !== TimeEntry::STATUS_IN_PROGRESS) {
            return $entry;
        }

        return DB::transaction(function () use ($entry) {
            $this->closeOpenInterval($entry, $this->localNow());
            $entry->status = TimeEntry::STATUS_PAUSED;
            $entry->total_duration_seconds = $entry->load('intervals')->calculateEffectiveSeconds();
            $entry->save();

            return $entry;
        });
    }

    /** Reanuda la actividad creando un nuevo intervalo. */
    public function resume(TimeEntry $entry): TimeEntry
    {
        if (! in_array($entry->status, [TimeEntry::STATUS_PAUSED, TimeEntry::STATUS_FINISHED], true)) {
            return $entry;
        }

        return DB::transaction(function () use ($entry) {
            $entry->intervals()->create(['started_at' => $this->localNow()]);
            $entry->status = TimeEntry::STATUS_IN_PROGRESS;
            $entry->save();

            return $entry;
        });
    }

    /**
     * Reanuda una entrada pausada asegurando que no quede otro cronómetro
     * corriendo para el mismo usuario.
     */
    public function switchTo(User $user, TimeEntry $entry): TimeEntry
    {
        return DB::transaction(function () use ($user, $entry) {
            $running = $this->runningEntry($user);

            if ($running && $running->id !== $entry->id) {
                $this->pause($running);
            }

            return $this->resume($entry);
        });
    }

    /** Compatibilidad: el flujo simplificado ya no finaliza; solo pausa. */
    public function finish(TimeEntry $entry): TimeEntry
    {
        return $this->pause($entry);
    }

    /**
     * Cierre forzoso nocturno (regla 8.6 / escenario 12.2): toda entrada en
     * progreso pasa a estado 3, cerrando el intervalo al final del día o al
     * límite máximo de horas permitido.
     */
    public function autoCloseOpenEntries(?Carbon $now = null): int
    {
        $now = $now ? $now->copy()->timezone($this->moduleTimezone()) : $this->localNow();
        $count = 0;

        TimeEntry::where('status', TimeEntry::STATUS_IN_PROGRESS)
            ->with('intervals')
            ->each(function (TimeEntry $entry) use ($now, &$count) {
                DB::transaction(function () use ($entry, $now) {
                    $endOfDay = Carbon::parse($entry->entry_date, $this->moduleTimezone())->endOfDay();
                    $closeAt = $now->lessThan($endOfDay) ? $now->copy() : $endOfDay;

                    $this->closeOpenInterval($entry, $closeAt);
                    $this->capToDailyLimit($entry);

                    $entry->status = TimeEntry::STATUS_AUTO_CLOSED;
                    $entry->total_duration_seconds = $entry->load('intervals')->calculateEffectiveSeconds();
                    $entry->save();
                });

                $count++;
            });

        return $count;
    }

    public function activeEntry(User $user): ?TimeEntry
    {
        return TimeEntry::where('user_id', $user->id)
            ->whereIn('status', [TimeEntry::STATUS_IN_PROGRESS, TimeEntry::STATUS_PAUSED])
            ->latest('id')
            ->first();
    }

    public function runningEntry(User $user): ?TimeEntry
    {
        return TimeEntry::where('user_id', $user->id)
            ->where('status', TimeEntry::STATUS_IN_PROGRESS)
            ->latest('id')
            ->first();
    }

    private function closeOpenInterval(TimeEntry $entry, Carbon $endedAt): void
    {
        $open = $entry->intervals()->whereNull('ended_at')->latest('id')->first();

        if ($open) {
            // Nunca permitir un intervalo con fin anterior al inicio.
            $open->ended_at = $endedAt->greaterThan($open->started_at) ? $endedAt : $open->started_at;
            $open->save();
        }
    }

    /** Recorta el último intervalo si la entrada excede el límite diario. */
    private function localNow(): Carbon
    {
        return Carbon::now($this->moduleTimezone());
    }

    private function moduleTimezone(): string
    {
        return (string) config('time-control.timezone', 'America/Mexico_City');
    }

    private function capToDailyLimit(TimeEntry $entry): void
    {
        $maxSeconds = (int) config('time-control.max_daily_hours', 18) * 3600;
        $entry->load('intervals');
        $total = $entry->calculateEffectiveSeconds();

        if ($total <= $maxSeconds) {
            return;
        }

        $overflow = $total - $maxSeconds;
        $last = $entry->intervals()->whereNotNull('ended_at')->latest('id')->first();

        if ($last) {
            $trimmed = Carbon::parse($last->ended_at)->subSeconds($overflow);
            $last->ended_at = $trimmed->greaterThan($last->started_at) ? $trimmed : $last->started_at;
            $last->save();
        }
    }
}
