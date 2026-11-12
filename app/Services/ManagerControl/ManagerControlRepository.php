<?php

namespace App\Services\ManagerControl;

use Illuminate\Support\Facades\Cache;

/**
 * Consultas SELECT (únicas, parametrizadas) sobre el legacy ManagerControl.
 * Los identificadores salen de config('managercontrol.schema') y se validan contra una lista blanca
 * de caracteres; los valores (fechas, ids) SIEMPRE van como parámetros "?".
 * Ver docs/managercontrol-readonly.md.
 */
class ManagerControlRepository
{
    public const CATALOG_LIMIT = 2000;

    public function __construct(private readonly ReadOnlyConnection $db) {}

    /** @return list<array{id: int, name: string}> */
    public function employees(): array
    {
        // La lista casi no cambia: se recuerda 10 minutos para no consultar SQL Server en cada interacción de la pantalla.
        return Cache::remember($this->cacheKey('employees'), now()->addMinutes(10), fn (): array => $this->loadEmployees());
    }

    /** @return list<array{id: int, name: string}> */
    private function loadEmployees(): array
    {
        $e = $this->table('employees');
        $name = $this->nameExpression($e['name']);
        $bindings = [];
        $sql = "SELECT TOP (500) {$e['id']} AS id, {$name} AS name FROM {$e['table']} WHERE 1 = 1"
            .(isset($e['deleted']) ? " AND ISNULL({$e['deleted']}, 0) = 0" : '')
            .$this->scope('', $e, $bindings)
            ." ORDER BY {$name}";

        return array_map(fn (array $r): array => ['id' => (int) $r['id'], 'name' => trim((string) $r['name'])], $this->db->select($sql, $bindings));
    }

    /** @return array{id: int, name: string}|null */
    public function employee(int $id): ?array
    {
        $e = $this->table('employees');
        $bindings = [$id];
        $sql = "SELECT TOP (1) {$e['id']} AS id, {$this->nameExpression($e['name'])} AS name FROM {$e['table']} WHERE {$e['id']} = ?".$this->scope('', $e, $bindings);
        $row = $this->db->select($sql, $bindings)[0] ?? null;

        return $row ? ['id' => (int) $row['id'], 'name' => trim((string) $row['name'])] : null;
    }

    /**
     * Datos que ManagerControl copia al capturar horas: el usuario y el precio por hora del colaborador.
     *
     * @return array{username: string, rate: float}|null
     */
    public function employeeCaptureData(int $id): ?array
    {
        $e = $this->table('employees');
        if (! isset($e['username'], $e['rate'])) {
            return null;
        }
        $bindings = [$id];
        $sql = "SELECT TOP (1) {$e['username']} AS username, {$e['rate']} AS rate FROM {$e['table']} WHERE {$e['id']} = ?".$this->scope('', $e, $bindings);
        $row = $this->db->select($sql, $bindings)[0] ?? null;

        return $row ? ['username' => trim((string) $row['username']), 'rate' => (float) $row['rate']] : null;
    }

    /** Departamento del colaborador (para el reporte con el formato original de ManagerControl). */
    public function employeeDepartment(int $id): ?string
    {
        $e = $this->table('employees');
        if (! isset($e['department_id']) || config('managercontrol.schema.departments') === null) {
            return null;
        }
        $d = $this->table('departments');
        $bindings = [$id];
        $sql = "SELECT TOP (1) d.{$d['name']} AS name FROM {$e['table']} e LEFT JOIN {$d['table']} d ON d.{$d['id']} = e.{$e['department_id']}"
            .$this->joinScope('d', $d, 'e', $e)
            ." WHERE e.{$e['id']} = ?".$this->scope('e', $e, $bindings);
        $name = trim((string) ($this->db->select($sql, $bindings)[0]['name'] ?? ''));

        return $name !== '' ? $name : null;
    }

    /**
     * Clientes, cuentas/conceptos (secciones) o actividades activos de la empresa/ejercicio (para capturar o editar una actividad).
     * Las actividades traen además su cuenta/concepto (section_id).
     *
     * @return list<array{id: int, name: string, source: string, section_id?: int, department_id?: int, rfc?: string}>
     */
    public function catalog(string $key): array
    {
        if (! in_array($key, ['customers', 'activities', 'sections'], true)) {
            throw new ManagerControlException('Catálogo no permitido.');
        }

        return Cache::remember($this->cacheKey($key), now()->addMinutes(10), function () use ($key): array {
            $t = $this->table($key);
            $bindings = [];
            $extra = (isset($t['section_id']) ? ", {$t['section_id']} AS section_id" : '')
                .(isset($t['department_id']) ? ", {$t['department_id']} AS department_id" : '')
                .(isset($t['rfc']) ? ", {$t['rfc']} AS rfc" : '')
                .(isset($t['source_username']) ? ", {$t['source_username']} AS source_username" : '');
            $sql = 'SELECT TOP ('.self::CATALOG_LIMIT.") {$t['id']} AS id, {$t['name']} AS name{$extra} FROM {$t['table']} WHERE 1 = 1"
                .(isset($t['deleted']) ? " AND ISNULL({$t['deleted']}, 0) = 0" : '')
                .$this->scope('', $t, $bindings)
                ." ORDER BY {$t['name']}";

            return array_map(fn (array $r): array => ['id' => (int) $r['id'], 'name' => trim((string) $r['name']),
                'source' => trim((string) ($r['source_username'] ?? '')) === 'CRM-GAA' ? 'crm' : 'managercontrol']
                + (isset($t['section_id']) ? ['section_id' => (int) ($r['section_id'] ?? 0)] : [])
                + (isset($t['department_id']) ? ['department_id' => (int) ($r['department_id'] ?? 0)] : [])
                + (isset($t['rfc']) ? ['rfc' => trim((string) ($r['rfc'] ?? ''))] : []), $this->db->select($sql, $bindings));
        });
    }

    /** @return list<int> */
    public function departmentIds(): array
    {
        $d = $this->table('departments');
        $bindings = [];
        $sql = "SELECT {$d['id']} AS id FROM {$d['table']} WHERE 1 = 1".$this->scope('', $d, $bindings);

        return array_map(fn (array $row): int => (int) $row['id'], $this->db->select($sql, $bindings));
    }

    public function forgetCatalog(string $key): void
    {
        if (! in_array($key, ['customers', 'sections', 'activities'], true)) {
            throw new ManagerControlException('Catálogo no permitido.');
        }
        Cache::forget($this->cacheKey($key));
    }

    /**
     * Días (Y-m-d) en que el colaborador tiene al menos una actividad, entre dos fechas. Una sola consulta agregada.
     *
     * @return list<string>
     */
    public function activityDates(int $employeeId, string $from, string $to): array
    {
        $h = $this->table('hours');
        $bindings = [$employeeId, $from, $to];
        $sql = "SELECT DISTINCT CONVERT(varchar(10), CAST({$h['date']} AS date), 23) AS work_date FROM {$h['table']} "
            ."WHERE {$h['employee_id']} = ? AND {$h['date']} >= ? AND {$h['date']} < DATEADD(day, 1, CAST(? AS date))"
            .(isset($h['deleted']) ? " AND ISNULL({$h['deleted']}, 0) = 0" : '')
            .$this->scope('', $h, $bindings)
            .' ORDER BY work_date';

        return array_values(array_map(fn (array $r): string => substr((string) $r['work_date'], 0, 10), $this->db->select($sql, $bindings)));
    }

    /**
     * Cliente y actividad que el colaborador MÁS usa (la combinación más repetida en los últimos 90 días; si no hay, en toda su
     * historia; a igualdad, la más reciente), para dejarlos ya elegidos en la siguiente captura. Lee la base, así también cuenta
     * lo capturado desde la app de escritorio.
     *
     * @return array{customer_id: int, activity_id: int}|null
     */
    public function mostUsedCapture(int $employeeId): ?array
    {
        $h = $this->table('hours');
        if (! isset($h['id'])) {
            return null;
        }
        $since = \Illuminate\Support\Carbon::now('America/Mexico_City')->subDays(90)->toDateString();
        foreach ([$since, null] as $from) {
            $bindings = [$employeeId];
            $sql = "SELECT TOP (1) {$h['customer_id']} AS customer_id, {$h['activity_id']} AS activity_id, COUNT(*) AS uses, MAX({$h['id']}) AS last_id FROM {$h['table']} WHERE {$h['employee_id']} = ?"
                .(isset($h['deleted']) ? " AND ISNULL({$h['deleted']}, 0) = 0" : '');
            if ($from !== null) {
                $sql .= " AND {$h['date']} >= ?";
                $bindings[] = $from;
            }
            $sql .= $this->scope('', $h, $bindings)
                ." GROUP BY {$h['customer_id']}, {$h['activity_id']} ORDER BY COUNT(*) DESC, MAX({$h['id']}) DESC";
            $row = $this->db->select($sql, $bindings)[0] ?? null;
            if ($row) {
                return ['customer_id' => (int) $row['customer_id'], 'activity_id' => (int) $row['activity_id']];
            }
        }

        return null;
    }

    /**
     * Horas laboradas de un colaborador entre dos fechas (ambas incluidas): fecha, cliente, actividad,
     * descripción y tiempo (en segundos), más los ids que hacen falta para editar un registro.
     *
     * @return list<array{id: int, date: string, customer: string, activity: string, description: string, seconds: int, customer_id: int, activity_id: int, hours: float}>
     */
    public function workedHours(int $employeeId, string $from, string $to): array
    {
        $h = $this->table('hours');
        $c = $this->table('customers');
        $a = $this->table('activities');
        $limit = max(1, (int) config('managercontrol.max_rows', 5000));
        $id = isset($h['id']) ? "h.{$h['id']}" : '0';

        $sql = "SELECT TOP ({$limit}) {$id} AS record_id, h.{$h['date']} AS work_date, c.{$c['name']} AS customer, a.{$a['name']} AS activity, "
            ."h.{$h['description']} AS description, h.{$h['hours']} AS hours, h.{$h['customer_id']} AS customer_id, h.{$h['activity_id']} AS activity_id "
            ."FROM {$h['table']} h "
            ."LEFT JOIN {$c['table']} c ON c.{$c['id']} = h.{$h['customer_id']}".$this->joinScope('c', $c, 'h', $h).' '
            ."LEFT JOIN {$a['table']} a ON a.{$a['id']} = h.{$h['activity_id']}".$this->joinScope('a', $a, 'h', $h).' '
            ."WHERE h.{$h['employee_id']} = ? AND h.{$h['date']} >= ? AND h.{$h['date']} < DATEADD(day, 1, CAST(? AS date))";
        $bindings = [$employeeId, $from, $to];

        if (isset($h['deleted'])) {
            $sql .= " AND ISNULL(h.{$h['deleted']}, 0) = 0";
        }
        $sql .= $this->scope('h', $h, $bindings);
        $sql .= " ORDER BY h.{$h['date']}".(isset($h['id']) ? ", h.{$h['id']}" : '');

        return array_map(fn (array $r): array => [
            'id' => (int) ($r['record_id'] ?? 0),
            'date' => substr((string) ($r['work_date'] instanceof \DateTimeInterface ? $r['work_date']->format('Y-m-d') : $r['work_date']), 0, 10),
            'customer' => trim((string) ($r['customer'] ?? '')) ?: 'Sin cliente',
            'activity' => trim((string) ($r['activity'] ?? '')) ?: 'Sin actividad',
            'description' => trim((string) ($r['description'] ?? '')),
            'description_raw' => (string) ($r['description'] ?? ''),
            'seconds' => self::toSeconds($r['hours'] ?? 0),
            'customer_id' => (int) ($r['customer_id'] ?? 0),
            'activity_id' => (int) ($r['activity_id'] ?? 0),
            'hours' => (float) ($r['hours'] ?? 0),
        ], $this->db->select($sql, $bindings));
    }

    /**
     * Colaboradores con más registros (actividades) en el periodo, de mayor a menor. Una sola consulta agregada;
     * el resultado se recuerda 5 minutos para que la pantalla abra al instante.
     *
     * @return list<int>
     */
    public function topEmployeeIds(string $from, string $to, int $limit = 5): array
    {
        return Cache::remember($this->cacheKey('top:'.$from.$to.$limit), now()->addMinutes(5), function () use ($from, $to, $limit): array {
            $h = $this->table('hours');
            $limit = max(1, min(20, $limit));
            $sql = "SELECT TOP ({$limit}) h.{$h['employee_id']} AS id, COUNT(*) AS total FROM {$h['table']} h "
                ."WHERE h.{$h['date']} >= ? AND h.{$h['date']} < DATEADD(day, 1, CAST(? AS date))";
            $bindings = [$from, $to];
            if (isset($h['deleted'])) {
                $sql .= " AND ISNULL(h.{$h['deleted']}, 0) = 0";
            }
            $sql .= $this->scope('h', $h, $bindings);
            $sql .= " GROUP BY h.{$h['employee_id']} ORDER BY COUNT(*) DESC, h.{$h['employee_id']}";

            return array_map(fn (array $r): int => (int) $r['id'], $this->db->select($sql, $bindings));
        });
    }

    /** Identificadores validados de una tabla del mapa (para el servicio de escritura). */
    public function identifiers(string $key): array
    {
        return $this->table($key);
    }

    /** Condición de Empresa y Ejercicio (los valores van como parámetros). */
    private function scope(string $alias, array $map, array &$bindings): string
    {
        $prefix = $alias !== '' ? $alias.'.' : '';
        $sql = '';
        foreach (['company_id', 'exercise_id'] as $key) {
            if (isset($map[$key]) && filled(config("managercontrol.{$key}"))) {
                $sql .= " AND {$prefix}{$map[$key]} = ?";
                $bindings[] = (int) config("managercontrol.{$key}");
            }
        }

        return $sql;
    }

    /** En los JOIN, el catálogo debe ser de la misma Empresa y Ejercicio que el registro. */
    private function joinScope(string $alias, array $map, string $otherAlias, array $other): string
    {
        $sql = '';
        foreach (['company_id', 'exercise_id'] as $key) {
            if (isset($map[$key], $other[$key])) {
                $sql .= " AND {$alias}.{$map[$key]} = {$otherAlias}.{$other[$key]}";
            }
        }

        return $sql;
    }

    private function cacheKey(string $name): string
    {
        return 'managercontrol:'.$name.':'.md5(serialize(config('managercontrol.schema')).config('managercontrol.host').config('managercontrol.database').config('managercontrol.company_id').'/'.config('managercontrol.exercise_id'));
    }

    /** Convierte el valor de horas del legacy a segundos según MANAGERCONTROL_HOURS_UNIT. */
    public static function toSeconds(mixed $value): int
    {
        $unit = (string) config('managercontrol.hours_unit', 'hours');
        if ($value instanceof \DateTimeInterface) {
            return ((int) $value->format('G')) * 3600 + ((int) $value->format('i')) * 60 + (int) $value->format('s');
        }
        $text = trim((string) $value);
        if ($unit === 'time' || preg_match('/^\d{1,3}:\d{2}(:\d{2})?(\.\d+)?$/', $text)) {
            $parts = array_map('intval', explode(':', $text.(substr_count($text, ':') === 1 ? ':0' : '')));

            return max(0, ($parts[0] ?? 0) * 3600 + ($parts[1] ?? 0) * 60 + ($parts[2] ?? 0));
        }
        $number = is_numeric($text) ? (float) $text : 0.0;

        return max(0, (int) round(match ($unit) {
            'minutes' => $number * 60,
            'seconds' => $number,
            default => $number * 3600,
        }));
    }

    /**
     * Tablas y columnas del esquema (solo INFORMATION_SCHEMA) para confirmar el mapa del legacy.
     *
     * @return list<array{table: string, column: string, type: string}>
     */
    public function schemaColumns(?string $tableLike = null): array
    {
        $sql = 'SELECT TOP (5000) TABLE_NAME AS tbl, COLUMN_NAME AS col, DATA_TYPE AS dtype FROM INFORMATION_SCHEMA.COLUMNS '
            .'WHERE TABLE_NAME LIKE ? ORDER BY TABLE_NAME, ORDINAL_POSITION';

        return array_map(fn (array $r): array => ['table' => (string) $r['tbl'], 'column' => (string) $r['col'], 'type' => (string) $r['dtype']],
            $this->db->select($sql, ['%'.str_replace(['%', '_', '['], ['[%]', '[_]', '[[]'], (string) $tableLike).'%']));
    }

    /** @param string|list<string> $columns */
    private function nameExpression(string|array $columns): string
    {
        $columns = (array) $columns;
        if (count($columns) === 1) {
            return $columns[0];
        }

        return 'LTRIM(RTRIM('.implode(" + ' ' + ", array_map(fn (string $c): string => "ISNULL({$c}, '')", $columns)).'))';
    }

    /**
     * @return array<string, string|list<string>> identificadores ya validados y entre corchetes
     */
    private function table(string $key): array
    {
        $map = (array) config("managercontrol.schema.{$key}");
        $out = [];
        if (isset($map['db_schema']) && ! preg_match('/^[A-Za-z0-9_]{1,128}$/', (string) $map['db_schema'])) {
            throw new ManagerControlException("Esquema inválido en managercontrol.schema.{$key}.");
        }
        foreach ($map as $name => $identifier) {
            // 'name' puede ser una lista de columnas; se conserva como lista de identificadores ya entre corchetes.
            $isList = is_array($identifier) && $identifier !== [];
            foreach ($isList ? $identifier : [$identifier] as $item) {
                if (! is_string($item) || ! preg_match('/^[A-Za-z0-9_]{1,128}$/', $item)) {
                    throw new ManagerControlException("Identificador inválido en managercontrol.schema.{$key}.{$name}.");
                }
            }
            if ($name === 'db_schema') {
                continue;
            }
            $out[$name] = $isList
                ? array_map(fn (string $i): string => "[{$i}]", $identifier)
                : ($name === 'table' ? '['.($map['db_schema'] ?? 'dbo').'].'."[{$identifier}]" : "[{$identifier}]");
        }

        return $out;
    }
}
