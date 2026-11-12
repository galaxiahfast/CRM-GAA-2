<?php

namespace App\Services\ManagerControl;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Agregar, editar y dar de baja actividades en ManagerControl, igual que lo hace la app de escritorio
 * (reglas confirmadas con los datos, ver docs/managercontrol-escritura.md):
 *  - FechaRegistro solo fecha; FechaCreacion = momento de la captura;
 *  - HoraLaborada en décimas de hora (decimal 18,1);
 *  - Precio = precio por hora del colaborador; SubTotal = Precio × HoraLaborada (redondeado a 1 decimal);
 *  - NombreUsuario = usuario del colaborador; IDSeccion = 0;
 *  - «Eliminar» = Baja = 1 (baja lógica: nunca se borra la fila).
 * Todo va limitado a la Empresa/Ejercicio configurados y al colaborador dueño del registro.
 * Cada cambio queda en la bitácora del CRM (managercontrol_audits).
 */
class ManagerControlWriter
{
    public function __construct(private readonly ManagerControlRepository $repository, private readonly WriteConnection $db) {}

    public function enabled(): bool
    {
        return (bool) config('managercontrol.write.enabled')
            && filled(config('managercontrol.write.username'))
            && filled(config('managercontrol.write.password'))
            && filled(config('managercontrol.company_id'))
            && filled(config('managercontrol.exercise_id'));
    }

    /** @param array{date: string, customer_id: int|string, activity_id: int|string, hours: float|string, description: ?string} $data */
    public function create(int $employeeId, array $data, ?User $actor = null): int
    {
        $this->assertEnabled();
        $clean = $this->validate($data);
        $capture = $this->repository->employeeCaptureData($employeeId)
            ?? throw new ManagerControlException('El colaborador no existe en la empresa y ejercicio configurados.');
        $h = $this->repository->identifiers('hours');

        $sql = "INSERT INTO {$h['table']} ({$h['company_id']}, {$h['exercise_id']}, {$h['username']}, {$h['employee_id']}, {$h['activity_id']}, {$h['customer_id']}, "
            ."{$h['hours']}, {$h['description']}, {$h['date']}, {$h['created_at']}, {$h['deleted']}, {$h['rate']}, {$h['subtotal']}, {$h['section_id']}) "
            ."OUTPUT INSERTED.{$h['id']} VALUES (?, ?, ?, ?, ?, ?, ?, ?, CAST(? AS date), GETDATE(), 0, ?, ?, 0)";
        $id = $this->db->insert($sql, [
            (int) config('managercontrol.company_id'), (int) config('managercontrol.exercise_id'), $capture['username'], $employeeId,
            $clean['activity_id'], $clean['customer_id'], $clean['hours'], $clean['description'], $clean['date'],
            $capture['rate'], round($capture['rate'] * $clean['hours'], 1),
        ]);

        $this->audit('create', $id, $employeeId, $clean, $actor);

        return $id;
    }

    /** @param array{date: string, customer_id: int|string, activity_id: int|string, hours: float|string, description: ?string} $data */
    public function update(int $recordId, int $employeeId, array $data, ?User $actor = null): void
    {
        $this->assertEnabled();
        $clean = $this->validate($data);
        $h = $this->repository->identifiers('hours');

        // El precio se conserva el de la captura original; el subtotal se recalcula con las horas nuevas.
        $sql = "UPDATE {$h['table']} SET {$h['activity_id']} = ?, {$h['customer_id']} = ?, {$h['hours']} = ?, {$h['description']} = ?, "
            ."{$h['date']} = CAST(? AS date), {$h['subtotal']} = ROUND({$h['rate']} * ?, 1) "
            .$this->ownedRecordWhere($h);
        $this->db->update($sql, [
            $clean['activity_id'], $clean['customer_id'], $clean['hours'], $clean['description'], $clean['date'], $clean['hours'],
            ...$this->ownedRecordBindings($recordId, $employeeId),
        ]);

        $this->audit('update', $recordId, $employeeId, $clean, $actor);
    }

    public function delete(int $recordId, int $employeeId, ?User $actor = null): void
    {
        $this->assertEnabled();
        $h = $this->repository->identifiers('hours');

        $this->db->update("UPDATE {$h['table']} SET {$h['deleted']} = 1 ".$this->ownedRecordWhere($h), $this->ownedRecordBindings($recordId, $employeeId));

        $this->audit('delete', $recordId, $employeeId, [], $actor);
    }

    private function ownedRecordWhere(array $h): string
    {
        return "WHERE {$h['id']} = ? AND {$h['company_id']} = ? AND {$h['exercise_id']} = ? AND {$h['employee_id']} = ? AND ISNULL({$h['deleted']}, 0) = 0";
    }

    /** @return list<int> */
    private function ownedRecordBindings(int $recordId, int $employeeId): array
    {
        return [$recordId, (int) config('managercontrol.company_id'), (int) config('managercontrol.exercise_id'), $employeeId];
    }

    private function assertEnabled(): void
    {
        if (! $this->enabled()) {
            throw new WriteDisabled('ManagerControl está en modo de solo lectura: la captura se activará cuando se den los permisos de escritura.');
        }
    }

    /**
     * @return array{date: string, customer_id: int, activity_id: int, hours: float, description: string}
     */
    public function validate(array $data): array
    {
        $date = (string) ($data['date'] ?? '');
        if (! Carbon::hasFormat($date, 'Y-m-d')) {
            throw new ManagerControlException('Elige una fecha válida.');
        }
        $customerId = (int) ($data['customer_id'] ?? 0);
        if (! in_array($customerId, array_column($this->repository->catalog('customers'), 'id'), true)) {
            throw new ManagerControlException('Elige un cliente de la lista.');
        }
        $activityId = (int) ($data['activity_id'] ?? 0);
        if (! in_array($activityId, array_column($this->repository->catalog('activities'), 'id'), true)) {
            throw new ManagerControlException('Elige una actividad de la lista.');
        }
        $raw = str_replace(',', '.', trim((string) ($data['hours'] ?? '')));
        $hours = is_numeric($raw) ? round((float) $raw, 1) : 0.0;
        $max = (float) config('managercontrol.write.max_hours', 24);
        if ($hours < 0.1 || $hours > $max) {
            throw new ManagerControlException('Las horas deben ir de 0.1 a '.rtrim(rtrim(number_format($max, 1), '0'), '.').' (en décimas de hora).');
        }
        $description = trim((string) ($data['description'] ?? ''));
        if (mb_strlen($description) > 2000) {
            throw new ManagerControlException('La descripción admite hasta 2000 caracteres.');
        }

        return ['date' => $date, 'customer_id' => $customerId, 'activity_id' => $activityId, 'hours' => $hours, 'description' => $description];
    }

    private function audit(string $action, int $recordId, int $employeeId, array $data, ?User $actor): void
    {
        DB::table('managercontrol_audits')->insert([
            'user_id' => $actor?->getKey(),
            'action' => $action,
            'legacy_record_id' => $recordId,
            'legacy_employee_id' => $employeeId,
            'payload' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
