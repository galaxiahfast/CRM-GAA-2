@props(['text' => ''])
@php
    // Inserta guiones suaves (invisibles hasta que hace falta cortar la línea) entre las sílabas de cada palabra,
    // para que el texto justificado se divida con "-" en lugar de dejar espacios grandes entre palabras.
    $hyphenate = function (string $word): string {
        $chars = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY);
        $n = count($chars);

        if ($n < 5) {
            return $word;
        }

        $lower = array_map(fn ($char) => mb_strtolower($char, 'UTF-8'), $chars);
        $strong = ['a', 'e', 'o', 'á', 'é', 'í', 'ó', 'ú'];
        $weak = ['i', 'u', 'ü'];

        // S = vocal fuerte, W = vocal débil, null = consonante (la "u" de qu, gue y gui es muda)
        $kind = [];
        for ($i = 0; $i < $n; $i++) {
            $c = $lower[$i];
            $kind[$i] = in_array($c, $strong, true) ? 'S' : (in_array($c, $weak, true) || ($c === 'y' && $i === $n - 1) ? 'W' : null);

            if ($c === 'u' && $i > 0 && ($lower[$i - 1] === 'q' || ($lower[$i - 1] === 'g' && in_array($lower[$i + 1] ?? '', ['e', 'i', 'é', 'í'], true)))) {
                $kind[$i] = null;
            }
        }

        // Núcleos de sílaba: diptongos juntos, dos vocales fuertes seguidas se separan (hiato)
        $nuclei = [];
        for ($i = 0; $i < $n; $i++) {
            if ($kind[$i] === null) {
                continue;
            }
            $start = $i;
            while ($i + 1 < $n && $kind[$i + 1] !== null && ! ($kind[$i] === 'S' && $kind[$i + 1] === 'S')) {
                $i++;
            }
            $nuclei[] = [$start, $i];
        }

        $inseparable = ['pr', 'pl', 'br', 'bl', 'fr', 'fl', 'gr', 'gl', 'cr', 'cl', 'tr', 'dr', 'kr', 'kl'];
        $breaks = [];

        for ($k = 0; $k + 1 < count($nuclei); $k++) {
            $from = $nuclei[$k][1] + 1;
            $to = $nuclei[$k + 1][0];

            // Unidades consonánticas (ch, ll, rr, qu y gu cuentan como una sola)
            $units = [];
            for ($j = $from; $j < $to; $j++) {
                $units[] = $j;
                $pair = ($lower[$j] ?? '').($lower[$j + 1] ?? '');
                if ($j + 1 < $to && (in_array($pair, ['ch', 'll', 'rr'], true) || $pair === 'qu' || ($lower[$j] === 'g' && $lower[$j + 1] === 'u'))) {
                    $j++;
                }
            }

            $m = count($units);
            $joins = fn ($a, $b) => in_array($lower[$units[$a]].$lower[$units[$b]], $inseparable, true);

            if ($m === 0) {
                $breaks[] = $to;
            } elseif ($m === 1) {
                $breaks[] = $units[0];
            } elseif ($m === 2) {
                $breaks[] = $joins(0, 1) ? $units[0] : $units[1];
            } elseif ($m === 3) {
                $breaks[] = $joins(1, 2) ? $units[1] : $units[2];
            } else {
                $breaks[] = $units[$m - 2];
            }
        }

        // No dejar menos de 2 letras al inicio ni al final de la palabra
        $breaks = array_filter($breaks, fn ($position) => $position >= 2 && $n - $position >= 2);

        $result = '';
        foreach ($chars as $i => $char) {
            $result .= (in_array($i, $breaks, true) ? "\u{00AD}" : '').$char;
        }

        return $result;
    };

    $hyphenated = preg_replace_callback('/\p{L}{5,}/u', fn ($match) => $hyphenate($match[0]), (string) $text);
@endphp
{{ $hyphenated }}
