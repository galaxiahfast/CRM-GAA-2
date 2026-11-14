<?php

namespace App\Services\ManagerControl;

use App\Models\ManagerControlEmployeeLink;
use App\Models\TimeEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Envía las actividades del Cronómetro del CRM a ManagerControl. El flujo va en un solo sentido:
 *  - cada día, cliente y actividad del Cronómetro de un usuario se captura como UNA actividad en ManagerControl;
 *  - si el tiempo del Cronómetro cambia, se actualiza ese registro, pero solo si sigue como el CRM lo dejó;
 *  - lo que se edite o dé de baja en ManagerControl se respeta: ese registro ya no se vuelve a tocar, y nada de lo
 *    que pase en ManagerControl cambia el Cronómetro.
 * Solo se usan vínculos explícitos (usuario ↔ colaborador, cliente y actividad); lo que no esté vinculado se reporta y
 * no se envía. Las actividades en progreso se envían cuando se pausan o se cierran.
 */
class TimerSync
{
    public function __construct(private readonly ManagerControlWriter $writer, private readonly ManagerControlRepository $repository) {}

    public function enabled(): bool
    {
        return (bool) config('managercontrol.timer_sync.enabled') && $this->writer->enabled();
    }

    /**
     * @return array{created: int, updated: int, unchanged: int, edited: int, deleted: int, skipped: int, errors: int, rows: list<array<string, mixed>>}
     */
    public function run(?string $from = null, ?string $to = null, bool $apply = false): array
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
            ->orderBy('entry_date')->orderBy('user_id')->orderBy('id')
            ->get()
            ->groupBy(fn (TimeEntry $entry): string => implode('|', [$entry->user_id, $entry->entry_date->toDateString(), $entry->customer_id, $entry->sub_service_id]));

        foreach ($groups as $entries) {
            /** @var TimeEntry $first */
            $first = $entries->first();
            $key = ['user_id' => $first->user_id, 'work_date' => $first->entry_date->toDateString(), 'customer_id' => $first->customer_id, 'sub_service_id' => $first->sub_service_id];
            if ($entries->contains(fn (TimeEntry $entry): bool => $entry->status === TimeEntry::STATUS_IN_PROGRESS)) {
                continue;
            }

            $seconds = (int) $entries->sum(fn (TimeEntry $entry): int => $entry->calculateEffectiveSeconds());
            $row = ['key' => $key, 'seconds' => $seconds, 'action' => '', 'message' => ''];
            try {
                $row = array_merge($row, $this->syncGroup($key, $seconds, $employees, $links, $apply));
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
     * @param  array{user_id: int, work_date: string, customer_id: int, sub_service_id: int}  $key
     * @return array{action: string, message: string}
     */
    private function syncGroup(array $key, int $seconds, $employees, $links, bool $apply): array
    {
        $previous = DB::table('managercontrol_timer_syncs')->where($key)->first();
        if ($previous !== null && $previous->status === 'edited') {
            return ['action' => 'edited', 'message' => 'Se editó o dio de baja en ManagerControl; se respeta.'];
        }

        $employeeId = $employees[$key['user_id']] ?? null;
        $customerId = $links['customers:'.$key['customer_id']] ?? null;
        $activityId = $links['activities:'.$key['sub_service_id']] ?? null;
        $missing = array_keys(array_filter(['el usuario no tiene colaborador de ManagerControl asignado' => $employeeId === null,
            'el cliente no está vinculado con ManagerControl' => $customerId === null,
            'la actividad no está vinculada con ManagerControl' => $activityId === null]));
        if ($missing !== []) {
            return $this->skip($key, $apply, 'No se envió: '.implode(', ', $missing).'.');
        }

        // ManagerControl guarda décimas de hora: menos de 6 minutos no se puede capturar
        $hours = round($seconds / 3600, 2);
        if (round($hours, 1) < 0.1) {
            if ($previous?->legacy_record_id) {
                return ['action' => 'unchanged', 'message' => 'Quedó en menos de 6 minutos; el registro de ManagerControl no se cambia.'];
            }

            return $this->skip($key, $apply, 'Menos de 6 minutos: ManagerControl no admite tiempos menores a 0:06.');
        }

        $description = (string) config('managercontrol.timer_sync.description', 'Registrado desde el Cronómetro del CRM');
        $data = ['date' => $key['work_date'], 'customer_id' => $customerId, 'activity_id' => $activityId, 'hours' => number_format($hours, 2, '.', ''), 'description' => $description];
        $sentHours = round($hours, 1);
        $base = ['legacy_employee_id' => $employeeId, 'legacy_customer_id' => $customerId, 'legacy_activity_id' => $activityId, 'sent_seconds' => $seconds, 'sent_hours' => $sentHours, 'sent_description' => $description, 'message' => null];

        if ($previous === null || $previous->legacy_record_id === null) {
            if ($apply) {
                $recordId = $this->writer->create((int) $employeeId, $data);
                $this->remember($key, $base + ['legacy_record_id' => $recordId, 'status' => 'synced']);
            }

            return ['action' => 'create', 'message' => "{$sentHours} h"];
        }

        if (abs((float) $previous->sent_hours - $sentHours) < 0.05) {
            if ($apply && (int) $previous->sent_seconds !== $seconds) {
                $this->remember($key, ['sent_seconds' => $seconds]);
            }

            return ['action' => 'unchanged', 'message' => "{$sentHours} h"];
        }

        // Solo se cambia si el registro sigue como el CRM lo dejó; si alguien lo editó o dio de baja en ManagerControl, se respeta
        $current = collect($this->repository->workedHours((int) $previous->legacy_employee_id, $key['work_date'], $key['work_date']))
            ->firstWhere('id', (int) $previous->legacy_record_id);
        $untouched = $current !== null
            && $current['date'] === $key['work_date']
            && $current['customer_id'] === (int) $previous->legacy_customer_id
            && $current['activity_id'] === (int) $previous->legacy_activity_id
            && abs($current['hours'] - (float) $previous->sent_hours) < 0.05
            && trim($current['description']) === trim((string) $previous->sent_description);
        if (! $untouched) {
            if ($apply) {
                $this->remember($key, ['status' => 'edited', 'message' => 'Se editó o dio de baja en ManagerControl; el CRM ya no lo cambia.']);
            }

            return ['action' => 'edited', 'message' => 'Se editó o dio de baja en ManagerControl; se respeta.'];
        }

        if ($apply) {
            $this->writer->update((int) $previous->legacy_record_id, (int) $employeeId, $data, null, [
                'date' => $key['work_date'], 'customer_id' => (int) $previous->legacy_customer_id, 'activity_id' => (int) $previous->legacy_activity_id,
                'hours' => (float) $previous->sent_hours, 'description_raw' => (string) $current['description_raw'],
            ]);
            $this->remember($key, $base + ['status' => 'synced']);
        }

        return ['action' => 'update', 'message' => "{$previous->sent_hours} h → {$sentHours} h"];
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
