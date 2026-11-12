<?php

namespace App\Services\ManagerControl;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Agregar, editar y dar de baja actividades en ManagerControl, igual que lo hace la app de escritorio
 * (reglas confirmadas con los datos, ver docs/managercontrol-escritura.md):
 *  - FechaRegistro solo fecha; FechaCreacion = momento de la captura;
 *  - el tiempo se captura como en el escritorio (h:mm o decimal); HoraLaborada guarda las horas en décimas
 *    y SubTotal = Precio × horas exactas a 2 decimales (2:13 → 2.22 h), redondeado a 1 decimal;
 *  - Precio = precio por hora del colaborador;
 *  - NombreUsuario = usuario del colaborador; FechaCreacion, Baja (0) e IDSeccion (0) los pone la base por defecto, como en el escritorio;
 *  - «Eliminar» = Baja = 1 (baja lógica: nunca se borra la fila).
 * Todo va limitado a la Empresa/Ejercicio configurados y al colaborador dueño del registro.
 * Cada cambio queda en la bitácora del CRM (managercontrol_audits).
 * Al editar se exige que el registro siga como estaba al abrir el formulario: si alguien lo cambió o lo dio de baja
 * desde la app de escritorio mientras tanto, no se sobrescribe y se avisa.
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
        $this->assertSafeForDay($employeeId, $clean, null, true);

        $sql = "INSERT INTO {$h['table']} ({$h['company_id']}, {$h['exercise_id']}, {$h['username']}, {$h['employee_id']}, {$h['activity_id']}, {$h['customer_id']}, "
            ."{$h['hours']}, {$h['description']}, {$h['date']}, {$h['rate']}, {$h['subtotal']}) "
            // Igual que RegistroDiario_Ins del escritorio: FechaCreacion, Baja e IDSeccion los pone la base con sus valores por defecto.
            ."OUTPUT INSERTED.{$h['id']} VALUES (?, ?, ?, ?, ?, ?, ?, ?, CAST(? AS date), ?, ?)";
        $id = $this->db->insert($sql, [
            (int) config('managercontrol.company_id'), (int) config('managercontrol.exercise_id'), $capture['username'], $employeeId,
            $clean['activity_id'], $clean['customer_id'], $clean['hours'], $clean['description'], $clean['date'],
            $capture['rate'], round($capture['rate'] * $clean['exact_hours'], 1),
        ]);

        $this->audit('create', $id, $employeeId, $clean, $actor);

        return $id;
    }

    /**
     * @param  array{date: string, customer_id: int|string, activity_id: int|string, hours: float|string, description: ?string}  $data
     * @param  array{date: string, customer_id: int, activity_id: int, hours: float, description_raw: string}|null  $expected  cómo estaba el registro al abrir el formulario
     */
    public function update(int $recordId, int $employeeId, array $data, ?User $actor = null, ?array $expected = null): void
    {
        $this->assertEnabled();
        $clean = $this->validate($data);
        $h = $this->repository->identifiers('hours');
        $this->assertSafeForDay($employeeId, $clean, $recordId, false);

        // El precio se conserva el de la captura original; el subtotal se recalcula con las horas nuevas.
        $sql = "UPDATE {$h['table']} SET {$h['activity_id']} = ?, {$h['customer_id']} = ?, {$h['hours']} = ?, {$h['description']} = ?, "
            ."{$h['date']} = CAST(? AS date), {$h['subtotal']} = ROUND({$h['rate']} * ?, 1) "
            .$this->ownedRecordWhere($h);
        $bindings = [
            $clean['activity_id'], $clean['customer_id'], $clean['hours'], $clean['description'], $clean['date'], $clean['exact_hours'],
            ...$this->ownedRecordBindings($recordId, $employeeId),
        ];
        if ($expected !== null) {
            $sql .= " AND {$h['customer_id']} = ? AND {$h['activity_id']} = ? AND {$h['hours']} = ? AND {$h['date']} = CAST(? AS date) AND {$h['description']} = ?";
            array_push($bindings, (int) $expected['customer_id'], (int) $expected['activity_id'], number_format((float) $expected['hours'], 1, '.', ''), (string) $expected['date'], (string) $expected['description_raw']);
        }
        $this->db->update($sql, $bindings);

        $this->audit('update', $recordId, $employeeId, $clean, $actor);
    }

    public function delete(int $recordId, int $employeeId, ?User $actor = null): void
    {
        $this->assertEnabled();
        $h = $this->repository->identifiers('hours');

        $this->db->update("UPDATE {$h['table']} SET {$h['deleted']} = 1 ".$this->ownedRecordWhere($h), $this->ownedRecordBindings($recordId, $employeeId));

        $this->audit('delete', $recordId, $employeeId, [], $actor);
    }

    /**
     * Protecciones antes de escribir: el total del día del colaborador no puede pasar del máximo y no se permite repetir por
     * error (doble clic) una actividad idéntica del mismo día.
     */
    private function assertSafeForDay(int $employeeId, array $clean, ?int $ignoreRecordId, bool $checkDuplicate): void
    {
        $dayRows = array_values(array_filter(
            $this->repository->workedHours($employeeId, $clean['date'], $clean['date']),
            fn (array $row): bool => $row['id'] !== $ignoreRecordId,
        ));
        $max = (float) config('managercontrol.write.max_hours', 24);
        $total = round(array_sum(array_column($dayRows, 'hours')) + $clean['hours'], 1);
        if ($total > $max) {
            throw new ManagerControlException(sprintf('Ese día ya suma %s h y con esta actividad pasaría de las %s h permitidas por día.', $this->hoursText($total - $clean['hours']), $this->hoursText($max)));
        }
        if ($checkDuplicate) {
            foreach ($dayRows as $row) {
                if ($row['customer_id'] === $clean['customer_id'] && $row['activity_id'] === $clean['activity_id']
                    && abs($row['hours'] - $clean['hours']) < 0.05 && trim($row['description']) === $clean['description']) {
                    throw new ManagerControlException('Ya existe una actividad idéntica ese día (mismo cliente, actividad, tiempo y descripción). No se agregó para no duplicarla.');
                }
            }
        }
    }

    private function hoursText(float $hours): string
    {
        $minutes = (int) round($hours * 60);

        return intdiv($minutes, 60).':'.str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT);
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
        $today = Carbon::now('America/Mexico_City')->startOfDay();
        $day = Carbon::parse($date, 'America/Mexico_City')->startOfDay();
        if ($day->greaterThan($today)) {
            throw new ManagerControlException('No se pueden capturar actividades con fecha futura.');
        }
        $maxPast = (int) config('managercontrol.write.max_past_days', 400);
        if ($maxPast > 0 && $day->lessThan($today->copy()->subDays($maxPast))) {
            throw new ManagerControlException("La fecha es demasiado antigua: solo se permite capturar hasta {$maxPast} días hacia atrás.");
        }
        $customerId = (int) ($data['customer_id'] ?? 0);
        if (! in_array($customerId, array_column($this->repository->catalog('customers'), 'id'), true)) {
            throw new ManagerControlException('Elige un cliente de la lista.');
        }
        $activityId = (int) ($data['activity_id'] ?? 0);
        if (! in_array($activityId, array_column($this->repository->catalog('activities'), 'id'), true)) {
            throw new ManagerControlException('Elige una actividad de la lista.');
        }
        $exact = self::parseHours((string) ($data['hours'] ?? ''));
        $hours = round($exact, 1);
        $max = (float) config('managercontrol.write.max_hours', 24);
        if ($hours < 0.1 || $exact > $max) {
            throw new ManagerControlException('Escribe el tiempo como 2:30 o 2.5 (mínimo 0:06, máximo '.rtrim(rtrim(number_format($max, 1), '0'), '.').' horas).');
        }
        $description = trim((string) ($data['description'] ?? ''));
        if (mb_strlen($description) > 2000) {
            throw new ManagerControlException('La descripción admite hasta 2000 caracteres.');
        }

        return ['date' => $date, 'customer_id' => $customerId, 'activity_id' => $activityId, 'hours' => $hours, 'exact_hours' => $exact, 'description' => $description];
    }

    /**
     * Tiempo como lo captura ManagerControl: «2:13» (horas:minutos) o decimal «2.5». Devuelve horas con 2 decimales
     * (2:13 → 2.22), que es lo que usa el escritorio para el SubTotal. Devuelve 0 si no se entiende.
     */
    public static function parseHours(string $value): float
    {
        $value = str_replace(',', '.', trim($value));
        if (preg_match('/^(\d{1,2}):([0-5]\d)$/', $value, $m)) {
            return round(((int) $m[1] * 60 + (int) $m[2]) / 60, 2);
        }

        return is_numeric($value) && (float) $value > 0 ? round((float) $value, 2) : 0.0;
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
