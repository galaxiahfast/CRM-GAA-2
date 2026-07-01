<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Control de Asistencia Biométrico</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 10px; color: #1f2937; margin: 20px; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .generated { color: #6b7280; font-size: 9px; margin-bottom: 10px; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .meta td { padding: 2px 6px; font-size: 9px; }
        .meta td.label { color: #6b7280; width: 180px; }
        table.report { width: 100%; border-collapse: collapse; }
        table.report th, table.report td { border: 1px solid #e5e7eb; padding: 4px 5px; text-align: left; font-size: 9px; }
        table.report th { background: #f3f4f6; font-weight: bold; }
        table.report td.num { text-align: right; font-family: DejaVu Sans Mono, monospace; }
        tr.row-correcto { background: #f0fdf4; }
        tr.row-impar { background: #fef2f2; }
        tr.row-modified { background: #eff6ff; }
        tr.row-total { background: #f9fafb; font-weight: bold; }
        .badge-correcto { color: #166534; }
        .badge-impar { color: #991b1b; }
    </style>
</head>
<body>
    <h1>Control de Asistencia Biométrico (Checador)</h1>
    <div class="generated">Generado: {{ $generatedAt->format('d/m/Y H:i:s') }}</div>

    @if (! empty($meta))
        <table class="meta">
            @foreach ($meta as $label => $value)
                <tr>
                    <td class="label">{{ $label }}</td>
                    <td>{{ $value }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <table class="report">
        <thead>
            <tr>
                @foreach ($columns as $column)
                    <th>{{ $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                @php
                    $rowClass = 'row-correcto';
                    if ($row['modified']) {
                        $rowClass = 'row-modified';
                    } elseif ($row['estado'] === 'Impar / Revisar') {
                        $rowClass = 'row-impar';
                    }
                @endphp
                <tr class="{{ $rowClass }}">
                    @foreach ($row['cells'] as $i => $cell)
                        <td class="{{ $i >= 2 && $i <= 6 ? 'num' : '' }}">
                            @if ($i === 7)
                                <span class="{{ $row['estado'] === 'Correcto' ? 'badge-correcto' : 'badge-impar' }}">{{ $cell }}</span>
                            @else
                                {{ $cell }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
            <tr class="row-total">
                @foreach ($totalRow as $i => $cell)
                    <td class="{{ $i >= 2 && $i <= 6 ? 'num' : '' }}">{{ $cell }}</td>
                @endforeach
            </tr>
        </tbody>
    </table>
</body>
</html>
