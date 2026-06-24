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
    /** Límite teórico máximo de horas por jornada (9:00-18:00 + 4h extra). */
    public const MAX_DAILY_HOURS = 13;

    /**
     * Inicia una nueva actividad para el usuario, congelando su contexto
     * organizacional actual (snapshot).
     *
     * @throws ActiveEntryException Si ya existe un cronómetro activo.
     * @throws NoOrganizationalProfileException Si el usuario no tiene perfil.
     */
    public function start(User $user, int $customerId, int $subServiceId): TimeEntry
    {
        if ($this->activeEntry($user)) {
            throw new ActiveEntryException;
        }

        $profile = $user->activeOrganizationalProfile()->first();

        if (! $profile) {
            throw new NoOrganizationalProfileException;
        }

        return DB::transaction(function () use ($user, $customerId, $subServiceId, $profile) {
            $entry = TimeEntry::create([
                'user_id' => $user->id,
                'customer_id' => $customerId,
                'sub_service_id' => $subServiceId,
                'role_id_snapshot' => $user->role_id,
                'job_position_id_snapshot' => $profile->job_position_id,
                'physical_area_id_snapshot' => $profile->physical_area_id,
                'entry_date' => Carbon::now()->toDateString(),
                'status' => TimeEntry::STATUS_IN_PROGRESS,
                'total_duration_seconds' => 0,
            ]);

            $entry->intervals()->create(['started_at' => Carbon::now()]);

            return $entry;
        });
    }

    /** Pausa la actividad: cierra el intervalo abierto y recalcula. */
    public function pause(TimeEntry $entry): TimeEntry
    {
        if ($entry->status !== TimeEntry::STATUS_IN_PROGRESS) {
            return $entry;
        }

        return DB::transaction(function () use ($entry) {
            $this->closeOpenInterval($entry, Carbon::now());
            $entry->status = TimeEntry::STATUS_PAUSED;
            $entry->total_duration_seconds = $entry->load('intervals')->calculateEffectiveSeconds();
            $entry->save();

            return $entry;
        });
    }

    /** Reanuda la actividad creando un nuevo intervalo. */
    public function resume(TimeEntry $entry): TimeEntry
    {
        if ($entry->status !== TimeEntry::STATUS_PAUSED) {
            return $entry;
        }

        return DB::transaction(function () use ($entry) {
            $entry->intervals()->create(['started_at' => Carbon::now()]);
            $entry->status = TimeEntry::STATUS_IN_PROGRESS;
            $entry->save();

            return $entry;
        });
    }

    /** Finaliza la actividad por decisión del usuario (estado 2). */
    public function finish(TimeEntry $entry): TimeEntry
    {
        if (! in_array($entry->status, [TimeEntry::STATUS_IN_PROGRESS, TimeEntry::STATUS_PAUSED], true)) {
            return $entry;
        }

        return DB::transaction(function () use ($entry) {
            $this->closeOpenInterval($entry, Carbon::now());
            $entry->status = TimeEntry::STATUS_FINISHED;
            $entry->total_duration_seconds = $entry->load('intervals')->calculateEffectiveSeconds();
            $entry->save();

            return $entry;
        });
    }

    /**
     * Cierre forzoso nocturno (regla 8.6 / escenario 12.2): toda entrada en
     * progreso pasa a estado 3, cerrando el intervalo al final del día o al
     * límite máximo de horas permitido.
     */
    public function autoCloseOpenEntries(?Carbon $now = null): int
    {
        $now = $now ?: Carbon::now();
        $count = 0;

        TimeEntry::where('status', TimeEntry::STATUS_IN_PROGRESS)
            ->with('intervals')
            ->each(function (TimeEntry $entry) use ($now, &$count) {
                DB::transaction(function () use ($entry, $now) {
                    $endOfDay = Carbon::parse($entry->entry_date)->endOfDay();
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
    private function capToDailyLimit(TimeEntry $entry): void
    {
        $maxSeconds = self::MAX_DAILY_HOURS * 3600;
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
