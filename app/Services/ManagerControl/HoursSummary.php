<?php

namespace App\Services\ManagerControl;

/** Agrega las filas del legacy por día, cliente y actividad (todo en segundos). */
final class HoursSummary
{
    /**
     * @param  list<array{date: string, customer: string, activity: string, description: string, seconds: int}>  $rows
     * @param  array<string, int>  $byCustomer
     * @param  array<string, int>  $byActivity
     * @param  array<string, list<array{date: string, customer: string, activity: string, description: string, seconds: int}>>  $byDay
     */
    public function __construct(
        public readonly array $rows,
        public readonly int $totalSeconds,
        public readonly array $byCustomer,
        public readonly array $byActivity,
        public readonly array $byDay,
    ) {}

    /** @param list<array{date: string, customer: string, activity: string, description: string, seconds: int}> $rows */
    public static function fromRows(array $rows): self
    {
        $byCustomer = [];
        $byActivity = [];
        $byDay = [];
        $total = 0;
        foreach ($rows as $row) {
            $total += $row['seconds'];
            $byCustomer[$row['customer']] = ($byCustomer[$row['customer']] ?? 0) + $row['seconds'];
            $byActivity[$row['activity']] = ($byActivity[$row['activity']] ?? 0) + $row['seconds'];
            $byDay[$row['date']][] = $row;
        }
        arsort($byCustomer);
        arsort($byActivity);
        ksort($byDay);

        return new self($rows, $total, $byCustomer, $byActivity, $byDay);
    }

    public static function format(int $seconds): string
    {
        $seconds = max(0, $seconds);

        return sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
    }
}
