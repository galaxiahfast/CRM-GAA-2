<?php

namespace App\Services\ManagerControl;

use App\Models\User;

/**
 * Cuadra el tiempo capturado en ManagerControl con el del reloj checador. Como ManagerControl guarda décimas de hora
 * (0.1 h = 6 min), la meta de cada día es el tiempo del reloj checador redondeado a la décima. La diferencia se reparte
 * entre las actividades del día en proporción a lo que ya tenía cada una (mínimo 0.1 h por actividad), así se respeta
 * cómo se distribuyó el trabajo. Para un periodo, el redondeo se arrastra de un día a otro para que el total del
 * periodo en horas decimales sea el mismo que el del reloj checador.
 */
class ClockBalancer
{
    public function __construct(private readonly ManagerControlRepository $repository, private readonly ManagerControlWriter $writer) {}

    /** Meta de un día en décimas de hora. */
    public static function dayTarget(int $clockSeconds): int
    {
        return (int) round($clockSeconds / 360);
    }

    /**
     * Metas de varios días (fecha => segundos del reloj) en décimas, arrastrando el redondeo: la suma de las metas es
     * siempre el total del periodo redondeado a la décima y cada día queda a menos de 0.1 h de su tiempo real.
     *
     * @param  array<string, int>  $clockSecondsByDay
     * @return array<string, int>
     */
    public static function periodTargets(array $clockSecondsByDay): array
    {
        ksort($clockSecondsByDay);
        $targets = [];
        $cumulative = 0;
        $assigned = 0;
        foreach ($clockSecondsByDay as $date => $seconds) {
            $cumulative += $seconds;
            $target = (int) round($cumulative / 360) - $assigned;
            $targets[$date] = $target;
            $assigned += $target;
        }

        return $targets;
    }

    /**
     * Nuevas horas de cada actividad del día para que sumen la meta.
     *
     * @param  list<array{id: int, hours: float}>  $rows
     * @return array<int, float> id del registro => horas nuevas (solo los que cambian)
     */
    public static function plan(array $rows, int $targetTenths): array
    {
        if ($rows === []) {
            throw new ManagerControlException('Ese día no tiene actividades en ManagerControl para cuadrar.');
        }
        $count = count($rows);
        if ($targetTenths < $count) {
            throw new ManagerControlException(sprintf(
                'El reloj checador marca %s h y ese día hay %d actividades: cada actividad necesita al menos 0.1 h (6 min). Da de baja alguna o ajústalas a mano.',
                number_format($targetTenths / 10, 1), $count,
            ));
        }
        if ($targetTenths > (int) round((float) config('managercontrol.write.max_hours', 24) * 10)) {
            throw new ManagerControlException('El reloj checador marca más de 24 h ese día; revisa sus marcas antes de cuadrar.');
        }

        $current = array_map(fn (array $row): int => (int) round(((float) $row['hours']) * 10), $rows);
        $sum = array_sum($current);
        if ($sum === $targetTenths) {
            return [];
        }

        // Reparto proporcional (por partes iguales si todas están en cero), mínimo una décima por actividad
        $weights = $sum > 0 ? $current : array_fill(0, $count, 1);
        $weightSum = array_sum($weights);
        $raw = array_map(fn (int $weight): float => $targetTenths * $weight / $weightSum, $weights);
        $new = array_map(fn (float $value): int => max(1, (int) floor($value)), $raw);

        // Se ajusta la diferencia que deja el redondeo: primero a las de mayor fracción
        $order = array_keys($raw);
        while (($left = $targetTenths - array_sum($new)) !== 0) {
            if ($left > 0) {
                usort($order, fn (int $a, int $b): int => ($raw[$b] - floor($raw[$b])) <=> ($raw[$a] - floor($raw[$a])) ?: $b <=> $a);
                $new[$order[0]]++;
            } else {
                $candidates = array_values(array_filter($order, fn (int $i): bool => $new[$i] > 1));
                usort($candidates, fn (int $a, int $b): int => ($new[$b] - $raw[$b]) <=> ($new[$a] - $raw[$a]) ?: $new[$b] <=> $new[$a]);
                $new[$candidates[0]]--;
            }
        }

        $changes = [];
        foreach ($rows as $index => $row) {
            if ($new[$index] !== $current[$index]) {
                $changes[(int) $row['id']] = $new[$index] / 10;
            }
        }

        return $changes;
    }

    /**
     * Vista previa de un día: actividades con sus horas antes y después.
     *
     * @return list<array{id: int, customer: string, activity: string, from: float, to: float}>
     */
    public function preview(int $employeeId, string $date, int $targetTenths): array
    {
        $rows = $this->dayRows($employeeId, $date);
        $changes = self::plan($rows, $targetTenths);

        return array_map(fn (array $row): array => [
            'id' => $row['id'], 'customer' => $row['customer'], 'activity' => $row['activity'],
            'from' => round($row['hours'], 1), 'to' => $changes[$row['id']] ?? round($row['hours'], 1),
        ], $rows);
    }

    /**
     * Aplica el cuadre de un día. Primero se bajan las que disminuyen y luego se suben las que aumentan, para que el día
     * nunca pase del máximo permitido a mitad del cambio. Cada registro solo se cambia si sigue como se leyó.
     *
     * @return int registros actualizados
     */
    public function apply(int $employeeId, string $date, int $targetTenths, ?User $actor = null): int
    {
        $rows = $this->dayRows($employeeId, $date);
        $changes = self::plan($rows, $targetTenths);
        $byId = collect($rows)->keyBy('id');
        uksort($changes, fn (int $a, int $b): int => ($changes[$a] - $byId[$a]['hours']) <=> ($changes[$b] - $byId[$b]['hours']));

        $data = fn (int $recordId): array => [
            'date' => $date, 'customer_id' => $byId[$recordId]['customer_id'], 'activity_id' => $byId[$recordId]['activity_id'],
            'hours' => number_format($changes[$recordId], 1, '.', ''), 'description' => $byId[$recordId]['description_raw'],
        ];
        // Se valida todo antes de escribir: si una actividad no es válida no se cambia ninguna (el día no queda a medias)
        foreach (array_keys($changes) as $recordId) {
            $this->writer->validate($data($recordId));
        }

        $updated = 0;
        foreach ($changes as $recordId => $hours) {
            $row = $byId[$recordId];
            $this->writer->update($recordId, $employeeId, $data($recordId), $actor, [
                'date' => $date, 'customer_id' => $row['customer_id'], 'activity_id' => $row['activity_id'],
                'hours' => $row['hours'], 'description_raw' => $row['description_raw'],
            ]);
            $updated++;
        }

        return $updated;
    }

    /** @return list<array<string, mixed>> */
    private function dayRows(int $employeeId, string $date): array
    {
        return array_values(array_filter(
            $this->repository->workedHours($employeeId, $date, $date),
            fn (array $row): bool => $row['date'] === $date && $row['id'] > 0,
        ));
    }
}
