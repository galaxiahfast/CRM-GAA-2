<?php

namespace App\Services\ManagerControl;

/**
 * Consultas SELECT (únicas, parametrizadas) sobre el legacy ManagerControl.
 * Los identificadores salen de config('managercontrol.schema') y se validan contra una lista blanca
 * de caracteres; los valores (fechas, ids) SIEMPRE van como parámetros "?".
 * Ver docs/managercontrol-readonly.md.
 */
class ManagerControlRepository
{
    public function __construct(private readonly ReadOnlyConnection $db) {}

    /** @return list<array{id: int, name: string}> */
    public function employees(): array
    {
        $e = $this->table('employees');
        $name = $this->nameExpression($e['name']);
        $sql = "SELECT TOP (500) {$e['id']} AS id, {$name} AS name FROM {$e['table']}"
            .(isset($e['deleted']) ? " WHERE ISNULL({$e['deleted']}, 0) = 0" : '')
            ." ORDER BY {$name}";

        return array_map(fn (array $r): array => ['id' => (int) $r['id'], 'name' => trim((string) $r['name'])], $this->db->select($sql));
    }

    /** @return array{id: int, name: string}|null */
    public function employee(int $id): ?array
    {
        $e = $this->table('employees');
        $sql = "SELECT TOP (1) {$e['id']} AS id, {$this->nameExpression($e['name'])} AS name FROM {$e['table']} WHERE {$e['id']} = ?";
        $row = $this->db->select($sql, [$id])[0] ?? null;

        return $row ? ['id' => (int) $row['id'], 'name' => trim((string) $row['name'])] : null;
    }

    /**
     * Horas laboradas de un colaborador entre dos fechas (ambas incluidas): fecha, cliente, actividad,
     * descripción y tiempo, ya en segundos.
     *
     * @return list<array{date: string, customer: string, activity: string, description: string, seconds: int}>
     */
    public function workedHours(int $employeeId, string $from, string $to): array
    {
        $h = $this->table('hours');
        $c = $this->table('customers');
        $a = $this->table('activities');
        $limit = max(1, (int) config('managercontrol.max_rows', 5000));

        $sql = "SELECT TOP ({$limit}) h.{$h['date']} AS work_date, c.{$c['name']} AS customer, a.{$a['name']} AS activity, "
            ."h.{$h['description']} AS description, h.{$h['hours']} AS hours "
            ."FROM {$h['table']} h "
            ."LEFT JOIN {$c['table']} c ON c.{$c['id']} = h.{$h['customer_id']} "
            ."LEFT JOIN {$a['table']} a ON a.{$a['id']} = h.{$h['activity_id']} "
            ."WHERE h.{$h['employee_id']} = ? AND h.{$h['date']} >= ? AND h.{$h['date']} < DATEADD(day, 1, CAST(? AS date))";
        $bindings = [$employeeId, $from, $to];

        if (isset($h['deleted'])) {
            $sql .= " AND ISNULL(h.{$h['deleted']}, 0) = 0";
        }
        $exerciseId = config('managercontrol.exercise_id');
        if (filled($exerciseId)) {
            $sql .= " AND h.{$h['exercise_id']} = ?";
            $bindings[] = (int) $exerciseId;
        }
        $sql .= " ORDER BY h.{$h['date']}";

        return array_map(fn (array $r): array => [
            'date' => substr((string) ($r['work_date'] instanceof \DateTimeInterface ? $r['work_date']->format('Y-m-d') : $r['work_date']), 0, 10),
            'customer' => trim((string) ($r['customer'] ?? '')) ?: 'Sin cliente',
            'activity' => trim((string) ($r['activity'] ?? '')) ?: 'Sin actividad',
            'description' => trim((string) ($r['description'] ?? '')),
            'seconds' => self::toSeconds($r['hours'] ?? 0),
        ], $this->db->select($sql, $bindings));
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
