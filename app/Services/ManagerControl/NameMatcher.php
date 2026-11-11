<?php

namespace App\Services\ManagerControl;

/**
 * Compara el nombre de un usuario del CRM con los colaboradores de ManagerControl.
 * Solo devuelve una coincidencia cuando es de al menos $threshold % y no hay empate con otra persona.
 */
class NameMatcher
{
    public function __construct(private readonly float $threshold = 70.0) {}

    /**
     * @param  list<array{id: int, name: string}>  $employees
     * @return array{status: 'match'|'ambiguous'|'none', employee: ?array{id: int, name: string}, score: float}
     */
    public function bestMatch(string $name, array $employees): array
    {
        $scored = [];
        foreach ($employees as $employee) {
            $scored[] = ['employee' => $employee, 'score' => $this->score($name, $employee['name'])];
        }
        usort($scored, fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        $best = $scored[0] ?? null;
        if ($best === null || $best['score'] < $this->threshold) {
            return ['status' => 'none', 'employee' => null, 'score' => $best['score'] ?? 0.0];
        }
        // Empate (o casi) con otra persona distinta: no se adivina, para no mostrar horas ajenas.
        foreach (array_slice($scored, 1) as $other) {
            if ($other['employee']['id'] !== $best['employee']['id'] && $other['score'] >= $best['score'] - 3.0 && $other['score'] >= $this->threshold) {
                return ['status' => 'ambiguous', 'employee' => null, 'score' => $best['score']];
            }
        }

        return ['status' => 'match', 'employee' => $best['employee'], 'score' => $best['score']];
    }

    /** Porcentaje 0-100: el mayor entre la similitud global y la cobertura de palabras del nombre más corto. */
    public function score(string $a, string $b): float
    {
        $a = $this->normalize($a);
        $b = $this->normalize($b);
        if ($a === '' || $b === '') {
            return 0.0;
        }

        $tokensA = array_values(array_filter(explode(' ', $a)));
        $tokensB = array_values(array_filter(explode(' ', $b)));
        sort($tokensA);
        sort($tokensB);
        similar_text(implode(' ', $tokensA), implode(' ', $tokensB), $overall);

        $coverage = 0.0;
        [$short, $long] = count($tokensA) <= count($tokensB) ? [$tokensA, $tokensB] : [$tokensB, $tokensA];
        if (count($short) >= 2) {
            $hits = 0;
            foreach ($short as $token) {
                foreach ($long as $candidate) {
                    similar_text($token, $candidate, $percent);
                    if ($percent >= 85.0) {
                        $hits++;
                        break;
                    }
                }
            }
            $coverage = $hits / count($short) * 100;
        }

        return round(max($overall, $coverage), 1);
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');
        $value = strtr($value, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);

        return trim(preg_replace('/[^a-z0-9]+/', ' ', $value) ?? '');
    }
}
