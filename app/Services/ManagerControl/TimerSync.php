<?php

namespace App\Services\ManagerControl;

use App\Models\ManagerControlEmployeeLink;
use App\Models\TimeEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Envía las actividades del Cronómetro del CRM a ManagerControl. El flujo va en un solo sentido:
 *  - cada día, cliente y actividad del Cronómetro de un usuario se captura como UNA actividad en ManagerControl;
 *  - el Cronómetro manda: cada vez que su tiempo cambia (también mientras corre) se actualiza ese registro, aunque
 *    en ManagerControl lo hayan editado o dado de baja (en ese caso se vuelve a crear). Si el tiempo del Cronómetro no
 *    cambió, lo que se editó en ManagerControl se deja como está; nada de lo de ManagerControl cambia el Cronómetro;
 *  - ManagerControl guarda décimas de hora (0.1 h = 6 min), así que el tiempo del día se reparte entre sus actividades
 *    sin perder minutos: cada actividad de 1 minuto o más cuenta al menos 0.1 h y el resto del redondeo va a las que
 *    más les sobra (ver allocateTenths). El SubTotal se calcula con el tiempo exacto.
 * Solo se usan vínculos explícitos (usuario ↔ colaborador, cliente y actividad); lo que no esté vinculado se reporta y
 * no se envía. Se ejecuta al instante cuando cambia el Cronómetro de alguien (TimerSync::requestFor) y cada minuto.
 */
class TimerSync
{
    public function __construct(private readonly ManagerControlWriter $writer, private readonly ManagerControlRepository $repository) {}

    public function enabled(): bool
    {
        return (bool) config('managercontrol.timer_sync.enabled') && $this->writer->enabled();
    }

    /** Usuarios con cambios en el Cronómetro durante esta petición: se envían una sola vez, al terminar de responder. */
    private static array $pending = [];

    /**
     * Pide enviar a ManagerControl, apenas termine la respuesta, lo que cambió en el Cronómetro de un usuario. Nunca
     * interrumpe al usuario: cualquier falla se registra y el envío programado (cada minuto) lo vuelve a intentar.
     */
    public static function requestFor(?int $userId): void
    {
        if ($userId === null || ! config('managercontrol.timer_sync.enabled') || isset(self::$pending[$userId])) {
            return;
        }
        self::$pending[$userId] = true;

        dispatch(function () use ($userId): void {
            unset(self::$pending[$userId]);
            try {
                $lock = Cache::lock('managercontrol-timer-sync', 120);
                if (! $lock->get()) {
                    return; // ya hay un envío en curso; el programado lo completa
                }
                try {
                    app(self::class)->run(null, null, true, $userId);
                } finally {
                    $lock->release();
                }
            } catch (\Throwable $e) {
                Log::warning('No se pudo enviar el Cronómetro a ManagerControl: '.$e->getMessage(), ['user_id' => $userId]);
            }
        })->afterResponse();
    }

    /**
     * @return array{created: int, updated: int, unchanged: int, edited: int, deleted: int, skipped: int, errors: int, rows: list<array<string, mixed>>}
     */
    public function run(?string $from = null, ?string $to = null, bool $apply = false, ?int $onlyUserId = null): array
    {
        $timezone = (string) config('time-control.timezone', 'America/Mexico_City');
        $start = max((string) config('managercontrol.timer_sync.from', '2026-11-12'), $from ?? '0000-00-00');
        $end = $to ?? Carbon::now($timezone)->toDateString();
        $summary = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'edited' => 0, 'deleted' => 0, 'skipped' => 0, 'errors' => 0, 'rows' => []];

        if ($apply && ! $this->enabled()) {
            throw new WriteDisabled('El envío del Cronómetro a ManagerControl está apagado (MANAGERCONTROL_TIMER_SYNC_ENABLED) o la escritura no está activa.');
        }

        $companyId = (int) config('managercontrol.company_id');
        $exerciseId = (int) config('managercontrol.exercise_id');
        $links = DB::table('managercontrol_catalog_links')
            ->where('company_id', $companyId)->where('exercise_id', $exerciseId)
            ->get(['kind', 'crm_id', 'legacy_id'])
            ->mapWithKeys(fn (object $link): array => [$link->kind.':'.$link->crm_id => (int) $link->legacy_id]);
        $employees = ManagerControlEmployeeLink::query()->pluck('legacy_employee_id', 'user_id');

        // Un grupo por usuario, día, cliente y actividad. Si alguna parte sigue en progreso se espera a que se pause.
        $groups = TimeEntry::query()
            ->with('intervals')
            ->whereDate('entry_date', '>=', $start)->whereDate('entry_date', '<=', $end)
            ->when($onlyUserId !== null, fn ($query) => $query->where('user_id', $onlyUserId))
            ->orderBy('entry_date')->orderBy('user_id')->orderBy('id')
            ->get()
            ->groupBy(fn (TimeEntry $entry): string => implode('|', [$entry->user_id, $entry->entry_date->toDateString(), $entry->customer_id, $entry->sub_service_id]));

        // Segundos de cada grupo (las actividades en progreso cuentan con lo que llevan hasta ahora) y reparto en décimas por día
        $secondsByGroup = $groups->map(fn ($entries): int => (int) $entries->sum(fn (TimeEntry $entry): int => $entry->calculateEffectiveSeconds()));
        $tenthsByGroup = $this->allocateTenths($groups, $secondsByGroup, $employees, $links);

        foreach ($groups as $groupKey => $entries) {
            /** @var TimeEntry $first */
            $first = $entries->first();
            $key = ['user_id' => $first->user_id, 'work_date' => $first->entry_date->toDateString(), 'customer_id' => $first->customer_id, 'sub_service_id' => $first->sub_service_id];

            $seconds = $secondsByGroup[$groupKey];
            $row = ['key' => $key, 'seconds' => $seconds, 'action' => '', 'message' => ''];
            try {
                $row = array_merge($row, $this->syncGroup($key, $seconds, $tenthsByGroup[$groupKey] ?? 0, $employees, $links, $apply));
            } catch (ManagerControlException $e) {
                $row['action'] = 'error';
                $row['message'] = $e->getMessage();
                if ($apply) {
                    $this->remember($key, ['status' => 'error', 'message' => mb_substr($e->getMessage(), 0, 500)]);
                }
            }

            $summary[match ($row['action']) {
                'create' => 'created', 'update' => 'updated', 'unchanged' => 'unchanged', 'edited' => 'edited', 'error' => 'errors', default => 'skipped',
            }]++;
            $summary['rows'][] = $row;
        }

        // Actividades que el CRM envió y que después se borraron del Cronómetro: se dan de baja en ManagerControl
        // (baja lógica, como el escritorio), siempre que el registro siga como el CRM lo dejó.
        $sent = DB::table('managercontrol_timer_syncs')
            ->where('status', 'synced')->whereNotNull('legacy_record_id')
            ->whereDate('work_date', '>=', $start)->whereDate('work_date', '<=', $end)
            ->get();
        foreach ($sent as $previous) {
            $workDate = substr((string) $previous->work_date, 0, 10);
            if ($groups->has(implode('|', [$previous->user_id, $workDate, $previous->customer_id, $previous->sub_service_id]))) {
                continue;
            }
            $key = ['user_id' => (int) $previous->user_id, 'work_date' => $workDate, 'customer_id' => (int) $previous->customer_id, 'sub_service_id' => (int) $previous->sub_service_id];
            $row = ['key' => $key, 'seconds' => 0, 'action' => 'delete', 'message' => 'Se borró del Cronómetro: se da de baja en ManagerControl.'];
            try {
                if (! $this->untouched($previous, $workDate)) {
                    $row['action'] = 'edited';
                    $row['message'] = 'Se borró del Cronómetro, pero en ManagerControl lo editaron: se respeta.';
                    if ($apply) {
                        $this->remember($key, ['status' => 'edited', 'message' => $row['message']]);
                    }
                } elseif ($apply) {
                    $this->writer->delete((int) $previous->legacy_record_id, (int) $previous->legacy_employee_id);
                    $this->remember($key, ['status' => 'deleted', 'message' => 'Se borró del Cronómetro y se dio de baja en ManagerControl.']);
                }
            } catch (ManagerControlException $e) {
                $row['action'] = 'error';
                $row['message'] = $e->getMessage();
            }
            $summary[match ($row['action']) { 'edited' => 'edited', 'error' => 'errors', default => 'deleted' }]++;
            $summary['rows'][] = $row;
        }

        return $summary;
    }

    /** Si el registro del CRM sigue en ManagerControl tal como el CRM lo dejó (nadie lo editó ni lo dio de baja). */
    private function untouched(object $previous, string $workDate): bool
    {
        $current = collect($this->repository->workedHours((int) $previous->legacy_employee_id, $workDate, $workDate))
            ->firstWhere('id', (int) $previous->legacy_record_id);

        return $current !== null
            && $current['date'] === $workDate
            && $current['customer_id'] === (int) $previous->legacy_customer_id
            && $current['activity_id'] === (int) $previous->legacy_activity_id
            && abs($current['hours'] - (float) $previous->sent_hours) < 0.05
            && trim($current['description']) === trim((string) $previous->sent_description);
    }

    /**
     * Décimas de hora (0.1 h = 6 min) de cada grupo, repartidas por usuario y día: la suma del día es su tiempo real
     * redondeado a la décima (el sobrante va a quien más le sobra) y toda actividad de 1 minuto o más cuenta al menos 0.1.
     * Solo entran los grupos que sí se pueden enviar (colaborador, cliente y actividad vinculados).
     *
     * @return array<string, int>
     */
    private function allocateTenths($groups, $secondsByGroup, $employees, $links): array
    {
        $byDay = [];
        foreach ($groups as $groupKey => $entries) {
            $first = $entries->first();
            $seconds = $secondsByGroup[$groupKey];
            $linked = isset($employees[$first->user_id], $links['customers:'.$first->customer_id], $links['activities:'.$first->sub_service_id]);
            if ($linked && $seconds >= 60) {
                $byDay[$first->user_id.'|'.$first->entry_date->toDateString()][$groupKey] = $seconds;
            }
        }

        $result = [];
        foreach ($byDay as $secondsOfDay) {
            $exact = array_map(fn (int $seconds): float => $seconds / 360, $secondsOfDay);
            $tenths = array_map(fn (float $value): int => (int) floor($value), $exact);
            $remaining = (int) round(array_sum($exact)) - array_sum($tenths);
            $order = array_keys($exact);
            usort($order, fn ($a, $b): int => ($exact[$b] - $tenths[$b]) <=> ($exact[$a] - $tenths[$a]));
            foreach ($order as $groupKey) {
                if ($remaining <= 0) {
                    break;
                }
                $tenths[$groupKey]++;
                $remaining--;
            }
            foreach ($tenths as $groupKey => $value) {
                $result[$groupKey] = max(1, $value);
            }
        }

        return $result;
    }

    /**
     * @param  array{user_id: int, work_date: string, customer_id: int, sub_service_id: int}  $key
     * @return array{action: string, message: string}
     */
    private function syncGroup(array $key, int $seconds, int $tenths, $employees, $links, bool $apply): array
    {
        $previous = DB::table('managercontrol_timer_syncs')->where($key)->first();

        $employeeId = $employees[$key['user_id']] ?? null;
        $customerId = $links['customers:'.$key['customer_id']] ?? null;
        $activityId = $links['activities:'.$key['sub_service_id']] ?? null;
        $missing = array_keys(array_filter(['el usuario no tiene colaborador de ManagerControl asignado' => $employeeId === null,
            'el cliente no está vinculado con ManagerControl' => $customerId === null,
            'la actividad no está vinculada con ManagerControl' => $activityId === null]));
        if ($missing !== []) {
            return $this->skip($key, $apply, 'No se envió: '.implode(', ', $missing).'.');
        }

        // Menos de 1 minuto es un toque accidental; de ahí en adelante todo cuenta (mínimo 0.1 h, la unidad de ManagerControl)
        if ($tenths < 1) {
            if ($previous?->legacy_record_id) {
                return ['action' => 'unchanged', 'message' => 'Quedó en menos de 1 minuto; el registro de ManagerControl no se cambia.'];
            }

            return $this->skip($key, $apply, 'Menos de 1 minuto: no se envía.');
        }

        $description = (string) config('managercontrol.timer_sync.description', 'Registrado desde el Cronómetro del CRM');
        $sentHours = round($tenths / 10, 1);
        // El SubTotal se calcula con el tiempo exacto (3 min = 0.05 h), aunque las horas se guarden en décimas
        $data = ['date' => $key['work_date'], 'customer_id' => $customerId, 'activity_id' => $activityId, 'hours' => number_format($sentHours, 1, '.', ''),
            'exact_hours' => round($seconds / 3600, 2), 'description' => $description];
        $base = ['legacy_employee_id' => $employeeId, 'legacy_customer_id' => $customerId, 'legacy_activity_id' => $activityId, 'sent_seconds' => $seconds, 'sent_hours' => $sentHours, 'sent_description' => $description, 'message' => null];

        if ($previous === null || $previous->legacy_record_id === null) {
            return $this->create($key, (int) $employeeId, $data, $base, $apply, $sentHours);
        }

        if (abs((float) $previous->sent_hours - $sentHours) < 0.05) {
            if ($apply && (int) $previous->sent_seconds !== $seconds) {
                $this->remember($key, ['sent_seconds' => $seconds]);
            }
            // El tiempo del Cronómetro no cambió: lo que se editó en ManagerControl se deja como está
            if ($previous->status === 'edited') {
                return ['action' => 'edited', 'message' => 'Se editó en ManagerControl y el Cronómetro no cambió; se deja como está.'];
            }

            return ['action' => 'unchanged', 'message' => "{$sentHours} h"];
        }

        // El tiempo del Cronómetro cambió: manda el Cronómetro, aunque el registro se haya editado en ManagerControl.
        $current = collect($this->repository->workedHours((int) $previous->legacy_employee_id, $key['work_date'], $key['work_date']))
            ->firstWhere('id', (int) $previous->legacy_record_id);
        if ($current === null) {
            // Se dio de baja en ManagerControl: se vuelve a crear con el tiempo actual
            return $this->create($key, (int) $employeeId, $data, $base, $apply, $sentHours);
        }

        if ($apply) {
            $this->writer->update((int) $previous->legacy_record_id, (int) $employeeId, $data);
            $this->remember($key, $base + ['status' => 'synced']);
        }

        return ['action' => 'update', 'message' => "{$previous->sent_hours} h → {$sentHours} h"];
    }

    /** @return array{action: string, message: string} */
    private function create(array $key, int $employeeId, array $data, array $base, bool $apply, float $sentHours): array
    {
        if ($apply) {
            $recordId = $this->writer->create($employeeId, $data);
            $this->remember($key, $base + ['legacy_record_id' => $recordId, 'status' => 'synced']);
        }

        return ['action' => 'create', 'message' => "{$sentHours} h"];
    }

    /** @return array{action: string, message: string} */
    private function skip(array $key, bool $apply, string $message): array
    {
        if ($apply) {
            $this->remember($key, ['status' => 'skipped', 'message' => $message]);
        }

        return ['action' => 'skip', 'message' => $message];
    }

    private function remember(array $key, array $values): void
    {
        $exists = DB::table('managercontrol_timer_syncs')->where($key)->exists();
        $values['updated_at'] = now();
        if ($exists) {
            DB::table('managercontrol_timer_syncs')->where($key)->update($values);

            return;
        }
        DB::table('managercontrol_timer_syncs')->insert($key + $values + ['status' => $values['status'] ?? 'skipped', 'created_at' => now()]);
    }
}
